<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inalink";
$charset = "utf8mb4";

// The chatbot always uses this database user id.
define('MARK_BOT_UNIQUE_ID', 50000001);
define('MARK_BOT_NAME', 'Mark Evans');
define('MARK_BOT_AVATAR', 'avatars/Endou_Mamoru_avatar.png');

// Optional local configuration. Keep secrets/settings out of Git.
$localConfig = [];
$localConfigPath = __DIR__ . '/config.local.php';
if (is_file($localConfigPath)) {
    $loadedConfig = require $localConfigPath;
    if (is_array($loadedConfig)) {
        $localConfig = $loadedConfig;
    }
}

// AI provider. Ollama is the default and costs no API money because it runs locally.
define('AI_PROVIDER', strtolower((string)(getenv('AI_PROVIDER') ?: ($localConfig['ai_provider'] ?? 'ollama'))));

// Local Ollama settings.
define('OLLAMA_BASE_URL', rtrim((string)(getenv('OLLAMA_BASE_URL') ?: ($localConfig['ollama_base_url'] ?? 'http://127.0.0.1:11434')), '/'));
define('OLLAMA_MODEL', (string)(getenv('OLLAMA_MODEL') ?: ($localConfig['ollama_model'] ?? 'qwen3:1.7b')));

// Optional OpenAI fallback/provider. Not needed when AI_PROVIDER is "ollama".
define('OPENAI_API_KEY', (string)(getenv('OPENAI_API_KEY') ?: ($localConfig['openai_api_key'] ?? '')));
define('OPENAI_BASE_URL', rtrim((string)(getenv('OPENAI_BASE_URL') ?: ($localConfig['openai_base_url'] ?? 'https://api.openai.com/v1')), '/'));
define('OPENAI_MODEL', (string)(getenv('OPENAI_MODEL') ?: ($localConfig['openai_model'] ?? 'gpt-5-mini')));

try {
    $connect = new PDO(
        "mysql:host=$servername;dbname=$dbname;charset=$charset",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Make sure the standard chatbot is always available, even for an
    // already existing Inalink database that was created before this feature.
    $botPassword = '!BOT_ACCOUNT_DISABLED!';
    $stmt = $connect->prepare("
        INSERT INTO user (unique_id, username, password, email, avatar, status)
        VALUES (?, ?, ?, ?, ?, 'active now')
        ON DUPLICATE KEY UPDATE
            username = VALUES(username),
            avatar = VALUES(avatar),
            status = 'active now'
    ");
    $stmt->execute([
        MARK_BOT_UNIQUE_ID,
        MARK_BOT_NAME,
        $botPassword,
        'mark.evans.bot@inalink.local',
        MARK_BOT_AVATAR
    ]);
} catch (PDOException $error) {
    $message = $error->getMessage();
    echo "Connection failed: " . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    die();
}
