# Dag 4 — Je eerste echte AI-antwoord met Ollama

In Inalink praat je met **Mark Evans** (`unique_id = 50000001`).
Voor Dag 4 gebruikt het project standaard **Ollama**, zodat de AI lokaal op je eigen computer draait en je geen API-kosten per bericht hebt.

## Wat is de verwachte uitkomst?

Na deze stap werkt de chat als volgt:

```text
Bezoeker typt een bericht in chat.php
        ↓
JavaScript verstuurt het bericht met fetch()
        ↓
send_message.php ontvangt het bericht
        ↓
PHP slaat het bericht op in MySQL
        ↓
chatbot.php maakt een AI API-request
        ↓
POST http://127.0.0.1:11434/api/chat
        ↓
Ollama + qwen3:1.7b maken Marks antwoord
        ↓
PHP leest message.content uit de JSON-response
        ↓
Marks antwoord wordt opgeslagen in MySQL
        ↓
get_messages.php haalt het nieuwe bericht op
        ↓
Het AI-antwoord verschijnt in hetzelfde chatvenster
```

Daarmee voldoe je aan het belangrijkste leerdoel van Dag 4: **frontend → PHP-backend → AI-API → PHP → chatvenster**.

---

## Waarom Ollama gratis is

Met deze instelling:

```php
'ai_provider' => 'ollama',
```

stuurt Inalink geen berichten naar OpenAI. Ollama draait op je eigen pc en de API is lokaal bereikbaar via:

```text
http://127.0.0.1:11434
```

Je hebt daarom geen API-key en geen betaling per token/request nodig. Je gebruikt alleen je eigen opslag, RAM/CPU/GPU en stroom.

OpenAI staat nog als optionele mogelijkheid in de code, maar is voor deze opdracht niet nodig.

---

## Stap 1 — Installeer Ollama

Installeer Ollama via de officiële Ollama-website.

Controleer daarna in PowerShell of het programma bereikbaar is:

```bat
ollama --version
```

---

## Stap 2 — Download het lokale AI-model

Voer eenmalig uit:

```bat
ollama pull qwen3:1.7b
```

Of dubbelklik op:

```text
setup-ollama.bat
```

Het model wordt lokaal opgeslagen. Daarna hoeft het niet voor ieder bericht opnieuw gedownload te worden.

Je kunt controleren welke modellen aanwezig zijn met:

```bat
ollama list
```

---

## Stap 3 — Controleer config.local.php

De gratis standaardconfiguratie is:

```php
<?php
return [
    'ai_provider' => 'ollama',
    'ollama_base_url' => 'http://127.0.0.1:11434',
    'ollama_model' => 'qwen3:1.7b',

    // Alleen voor later, niet nodig voor Ollama.
    'openai_api_key' => '',
    'openai_base_url' => 'https://api.openai.com/v1',
    'openai_model' => 'gpt-5-mini',
];
```

`config.local.php` staat in `.gitignore`. Dat is belangrijk als je later wel een geheime API-key gebruikt.

Bij Ollama is er geen geheime key, maar de architectuur blijft goed: **JavaScript praat alleen met je eigen PHP-backend en niet rechtstreeks met de AI-provider.**

---

## Stap 4 — Het API-request in chatbot.php

Voor Ollama maakt PHP dit request:

```php
$url = OLLAMA_BASE_URL . '/api/chat';

$payload = [
    'model' => OLLAMA_MODEL,
    'messages' => array_merge(
        [['role' => 'system', 'content' => $instructions]],
        $history
    ),
    'stream' => false,
    'options' => [
        'temperature' => 0.8
    ]
];
```

Daarna verstuurt PHP de JSON met cURL:

```php
$curl = curl_init($url);

curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($payload)
]);

$rawResponse = curl_exec($curl);
```

Dit is dus een echt HTTP **POST API-request** vanuit je PHP-backend.

Een vereenvoudigde JSON-body ziet er zo uit:

```json
{
    "model": "qwen3:1.7b",
    "messages": [
        {
            "role": "system",
            "content": "Je speelt de rol van Mark Evans..."
        },
        {
            "role": "user",
            "content": "Hoi Mark, hoe gaat het?"
        }
    ],
    "stream": false,
    "options": {
        "temperature": 0.8
    }
}
```

---

## Stap 5 — Het AI-antwoord uitlezen

Ollama geeft JSON terug. Het gegenereerde antwoord staat bij:

```text
message.content
```

In `chatbot.php` gebeurt daarom:

```php
$text = trim((string)($response['message']['content'] ?? ''));
```

Die tekst wordt teruggegeven aan `send_message.php`.

---

## Stap 6 — Antwoord opslaan in MySQL

`send_message.php` roept de chatbot aan wanneer de ontvanger Mark is:

```php
if ($toUserId === MARK_BOT_UNIQUE_ID) {
    $botReply = generateMarkEvansReply($connect, $fromUserId);

    $stmt = $connect->prepare(
        "INSERT INTO messages (from_user_id, to_user_id, message)
         VALUES (?, ?, ?)"
    );

    $stmt->execute([
        MARK_BOT_UNIQUE_ID,
        $fromUserId,
        $botReply
    ]);
}
```

Daardoor behandelt de rest van Inalink het AI-antwoord gewoon als een normaal chatbericht.

---

## Stap 7 — Antwoord terug in het chatvenster

In `chat.php` verstuurt JavaScript het bericht naar je PHP-backend:

```javascript
const response = await fetch('send_message.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
    },
    body: body.toString()
});
```

Tijdens een AI-request staat in de chat:

```text
Mark typt…
```

`get_messages.php` haalt ondertussen nieuwe berichten op. Zodra PHP Marks antwoord heeft opgeslagen, verschijnt dit automatisch in de chat.

---

# Testen

Start eerst in XAMPP:

- Apache
- MySQL

Controleer daarna Ollama:

```bat
ollama list
```

Als `qwen3:1.7b` ontbreekt:

```bat
ollama pull qwen3:1.7b
```

Je kunt Ollama ook rechtstreeks testen:

```bat
ollama run qwen3:1.7b
```

Open daarna Inalink, log in, open de chat met **Mark Evans** en stuur bijvoorbeeld:

```text
Hoi Mark! Wat doe jij als een wedstrijd heel moeilijk wordt?
```

## Verwachte uitkomst

1. Je eigen bericht verschijnt in de chat.
2. Onder de chat verschijnt tijdelijk `Mark typt…`.
3. PHP stuurt de chatgeschiedenis naar de lokale Ollama API.
4. `qwen3:1.7b` genereert een antwoord in Marks persoonlijkheid.
5. Het antwoord wordt als bericht in MySQL opgeslagen.
6. Het antwoord verschijnt automatisch in het chatvenster.
7. Er is geen OpenAI API-key nodig en er zijn geen API-kosten per bericht.

---

# Wat je bij je uitleg kunt vertellen

**API:** een afgesproken manier waarop twee programma's gegevens met elkaar uitwisselen.

**Request:** Inalink stuurt met PHP een HTTP POST-request met JSON naar Ollama.

**Endpoint:**

```text
POST http://127.0.0.1:11434/api/chat
```

**Model:**

```text
qwen3:1.7b
```

**Input:** Marks system prompt + de laatste chatberichten uit MySQL.

**Response:** JSON van Ollama met het gegenereerde antwoord in `message.content`.

**Waarom server-side:** de browser praat met `send_message.php`. Als je later een provider gebruikt die wel een geheime API-key nodig heeft, blijft die key op de server en komt hij niet in JavaScript terecht.

---

# Problemen oplossen

## Mark zegt dat Ollama niet bereikbaar is

Controleer:

```bat
ollama list
```

Probeer daarna:

```bat
ollama run qwen3:1.7b
```

## Het model ontbreekt

Voer uit:

```bat
ollama pull qwen3:1.7b
```

## PHP meldt een cURL-probleem

Open de actieve `php.ini` van XAMPP en controleer of de cURL-extensie is ingeschakeld. Herstart daarna Apache.

## Antwoorden duren lang

Deze versie gebruikt al de snelle instellingen:

- `qwen3:1.7b` in plaats van het zwaardere 4B-model;
- `think: false`, zodat het model niet uitgebreid redeneert;
- maximaal 140 antwoordtokens;
- alleen de laatste 8 chatberichten als context;
- een contextvenster van 2048 tokens;
- `keep_alive: 30m`, zodat het model na het eerste antwoord warm blijft.

Het eerste antwoord na het starten van Ollama kan nog iets langer duren doordat het
model eerst in het geheugen moet worden geladen. De antwoorden daarna horen sneller
te komen. Sluit andere zware programma's als je computer weinig vrij geheugen heeft.
