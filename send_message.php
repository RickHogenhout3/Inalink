<?php
include_once 'config.php';
include_once 'chatbot.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$fromUserId = (int) $_SESSION['unique_id'];
$toUserId = (int) ($_POST['to_user_id'] ?? 0);
$message = trim($_POST['message'] ?? '');

// Avoid locking all of this user's polling requests while Ollama is thinking.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if ($toUserId <= 0 || $toUserId === $fromUserId || $message === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid message']);
    exit;
}

// Een compacte botprompt reageert sneller. Gewone gesprekken houden de ruimere limiet.
$maximumMessageLength = $toUserId === MARK_BOT_UNIQUE_ID ? 2000 : 20000;
if (strlen($message) > $maximumMessageLength) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message is too long']);
    exit;
}

try {
    $recipientStmt = $connect->prepare("SELECT unique_id FROM user WHERE unique_id = ? LIMIT 1");
    $recipientStmt->execute([$toUserId]);
    if (!$recipientStmt->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Recipient not found']);
        exit;
    }

    $stmt = $connect->prepare("INSERT INTO messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$fromUserId, $toUserId, $message]);
    $sentMessageId = (int) $connect->lastInsertId();

    $botReply = null;
    if ($toUserId === MARK_BOT_UNIQUE_ID) {
        $botReply = generateMarkEvansReply($connect, $fromUserId);
        $stmt = $connect->prepare("INSERT INTO messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
        $stmt->execute([MARK_BOT_UNIQUE_ID, $fromUserId, $botReply]);
    }

    echo json_encode([
        'success' => true,
        'message_id' => $sentMessageId,
        'bot_reply' => $botReply
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
