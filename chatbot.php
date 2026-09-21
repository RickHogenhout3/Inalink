<?php

/**
 * Generate an in-character Mark Evans response.
 *
 * Default provider: Ollama on this PC. This is the free Day 4 route:
 * browser -> send_message.php -> chatbot.php -> Ollama API -> MySQL -> browser.
 *
 * OpenAI remains optional for later, but is not needed for the assignment.
 */
function generateMarkEvansReply(PDO $connect, int $userId): string
{
    if (!function_exists('curl_init')) {
        error_log('Mark bot: PHP cURL extension is not enabled.');
        return "Hé! Mijn verbinding met het speelveld ontbreekt nog. Zet eerst de PHP cURL-extensie aan in XAMPP/PHP.";
    }

    $provider = AI_PROVIDER;
    if (!in_array($provider, ['ollama', 'openai'], true)) {
        error_log('Mark bot: unsupported AI provider: ' . $provider);
        return "Hé! Mijn AI-opstelling klopt nog niet. Zet ai_provider op 'ollama' of 'openai' in config.local.php.";
    }

    if ($provider === 'openai' && (OPENAI_API_KEY === '' || str_starts_with(OPENAI_API_KEY, 'sk-your-'))) {
        return "Hé! OpenAI staat als provider ingesteld, maar er is geen API-key ingevuld. Zet ai_provider op 'ollama' als je gratis lokaal wilt draaien.";
    }

    $userStmt = $connect->prepare("SELECT username FROM user WHERE unique_id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);
    $username = $user['username'] ?? 'vriend';

    // Acht recente berichten geven genoeg context en houden de lokale prompt snel.
    $historyStmt = $connect->prepare("
        SELECT from_user_id, to_user_id, message
        FROM (
            SELECT id, from_user_id, to_user_id, message, timestamp
            FROM messages
            WHERE (from_user_id = ? AND to_user_id = ?)
               OR (from_user_id = ? AND to_user_id = ?)
            ORDER BY timestamp DESC, id DESC
            LIMIT 8
        ) recent_messages
        ORDER BY timestamp ASC, id ASC
    ");
    $historyStmt->execute([
        $userId,
        MARK_BOT_UNIQUE_ID,
        MARK_BOT_UNIQUE_ID,
        $userId
    ]);

    $history = [];
    while ($message = $historyStmt->fetch(PDO::FETCH_ASSOC)) {
        $history[] = [
            'role' => ((int)$message['from_user_id'] === MARK_BOT_UNIQUE_ID) ? 'assistant' : 'user',
            'content' => $message['message']
        ];
    }

    // Dit is de vaste system prompt/persoonlijkheid van de chatbot.
    $instructions = <<<PROMPT
Je bent Mark Evans (Endou Mamoru) uit Inazuma Eleven in zijn jonge Raimon-periode.
Praat natuurlijk en standaard in het Nederlands. Je bent energiek, warm, optimistisch,
direct en gelooft in teamwork en nooit opgeven. Blijf in karakter, maar geef wel echt
bruikbare hulp. Gebruik af en toe een korte voetbalvergelijking, zonder te overdrijven.
Je gesprekspartner heet {$username}; gebruik de naam alleen als dat natuurlijk voelt.
Geef meestal een kort chatantwoord van 1 tot 4 zinnen. Alleen bij een duidelijke vraag
om uitleg mag het iets langer. Geef geen intern denkproces of chain-of-thought.
Als iemand naar je techniek vraagt, zeg eerlijk dat je een AI-chatbot bent die Mark roleplayt.
/no_think
PROMPT;

    if ($provider === 'ollama') {
        /*
         * GRATIS LOKALE API-REQUEST
         *
         * Ollama draait op localhost. Er is dus geen geheime API-key nodig.
         * We gebruiken Ollama's eigen POST /api/chat endpoint. De system prompt
         * en databasegeschiedenis worden als messages meegestuurd.
         */
        $url = OLLAMA_BASE_URL . '/api/chat';
        $headers = ['Content-Type: application/json'];
        $payload = [
            'model' => OLLAMA_MODEL,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $instructions]],
                $history
            ),
            'stream' => false,
            'think' => false,
            // Houd het model warm, zodat alleen het eerste antwoord hoeft op te starten.
            'keep_alive' => '30m',
            'options' => [
                // Korte antwoorden en een kleine context maken lokaal chatten veel sneller.
                'num_predict' => 140,
                'num_ctx' => 2048,
                'temperature' => 0.7,
                'top_p' => 0.9
            ]
        ];
    } else {
        // Optionele betaalde/cloud-route voor later.
        $url = OPENAI_BASE_URL . '/responses';
        $headers = [
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'Content-Type: application/json'
        ];
        $payload = [
            'model' => OPENAI_MODEL,
            'instructions' => $instructions,
            'input' => $history,
            'max_output_tokens' => 1250
        ];
    }

    $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($jsonPayload === false) {
        error_log('Mark bot: JSON encoding failed: ' . json_last_error_msg());
        return "Oeps! Ik kon het bericht niet goed voorbereiden. Probeer het nog een keer!";
    }

    // PHP stuurt hier het echte HTTP API-request naar de gekozen AI-provider.
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => $provider === 'ollama' ? 60 : 45,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $jsonPayload
    ]);

    $rawResponse = curl_exec($curl);
    $curlError = curl_error($curl);
    $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($rawResponse === false || $curlError) {
        error_log('Mark bot cURL error (' . $provider . '): ' . $curlError);

        if ($provider === 'ollama') {
            return "Hé! Ik kan Ollama nog niet bereiken. Controleer of Ollama draait en voer eventueel 'ollama pull " . OLLAMA_MODEL . "' uit.";
        }

        return "Oeps! Zelfs de beste keeper mist weleens een bal. Mijn verbinding met OpenAI ging net mis. Probeer het nog een keer!";
    }

    $response = json_decode($rawResponse, true);
    if (!is_array($response)) {
        error_log('Mark bot: invalid JSON response (' . $provider . '): ' . $rawResponse);
        return $provider === 'ollama'
            ? "Ai! Ollama gaf geen geldig antwoord terug. Controleer of Ollama goed draait."
            : "Ai! De AI gaf geen geldig antwoord terug. Probeer het nog eens.";
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        $apiMessage = $response['error']['message'] ?? ($response['error'] ?? 'Unknown AI API error');
        if (is_array($apiMessage)) {
            $apiMessage = json_encode($apiMessage, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        error_log('Mark bot API error (' . $provider . ', ' . $statusCode . '): ' . $apiMessage);

        if ($provider === 'ollama') {
            $lowerMessage = strtolower((string)$apiMessage);
            if (str_contains($lowerMessage, 'model') && (str_contains($lowerMessage, 'not found') || str_contains($lowerMessage, 'pull'))) {
                return "Ik ben bijna klaar voor de wedstrijd! Het lokale model ontbreekt nog. Open PowerShell en voer uit: ollama pull " . OLLAMA_MODEL;
            }

            return "Ai! Ollama gaf een fout terug. Controleer of Ollama draait en of model '" . OLLAMA_MODEL . "' is geïnstalleerd.";
        }

        return "Ai, die aanval kwam onverwacht! OpenAI kon nu geen antwoord maken. Probeer het zo nog eens.";
    }

    // Ollama /api/chat: het antwoord staat in message.content.
    if ($provider === 'ollama') {
        $text = trim((string)($response['message']['content'] ?? ''));
    } else {
        $text = extractAiResponseText($response);
    }

    if ($text !== '') {
        return $text;
    }

    error_log('MARK RAW RESPONSE: ' . $rawResponse);
    error_log('Mark bot: AI response contained no text. Provider: ' . $provider);

    return "Hmm... ik kreeg de bal niet goed onder controle. Kun je dat nog een keer sturen?";
}

/**
 * Extract text from an OpenAI Responses API response.
 * Only used when the optional OpenAI provider is enabled.
 */
function extractAiResponseText(array $response): string
{
    if (isset($response['output_text']) && is_string($response['output_text'])) {
        $text = trim($response['output_text']);
        if ($text !== '') {
            return $text;
        }
    }

    if (!empty($response['output']) && is_array($response['output'])) {
        foreach ($response['output'] as $outputItem) {
            if (($outputItem['type'] ?? '') !== 'message' || empty($outputItem['content']) || !is_array($outputItem['content'])) {
                continue;
            }

            foreach ($outputItem['content'] as $contentItem) {
                if (($contentItem['type'] ?? '') === 'output_text' && isset($contentItem['text'])) {
                    $text = trim((string)$contentItem['text']);
                    if ($text !== '') {
                        return $text;
                    }
                }
            }
        }
    }

    $fallback = $response['choices'][0]['message']['content'] ?? '';
    return is_string($fallback) ? trim($fallback) : '';
}
