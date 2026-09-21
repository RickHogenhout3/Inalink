<?php
include_once 'config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$currentUserId = (int) $_SESSION['unique_id'];
$toUserId = (int) ($_GET['to_user_id'] ?? 0);
$afterId = max(0, (int) ($_GET['after_id'] ?? 0));

// We only need the user id after this point. Releasing the PHP session lock lets
// polling continue while a slower AI reply is being generated in another request.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if ($toUserId <= 0 || $toUserId === $currentUserId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid chat recipient']);
    exit;
}

try {
    $stmt = $connect->prepare("
        SELECT
            messages.id,
            messages.from_user_id,
            messages.to_user_id,
            messages.message,
            messages.timestamp,
            user.username AS from_username,
            user.avatar AS from_avatar
        FROM messages
        JOIN user ON messages.from_user_id = user.unique_id
        WHERE (
                (messages.from_user_id = ? AND messages.to_user_id = ?)
             OR (messages.from_user_id = ? AND messages.to_user_id = ?)
        )
        AND messages.id > ?
        ORDER BY messages.id ASC
    ");
    $stmt->execute([$currentUserId, $toUserId, $toUserId, $currentUserId, $afterId]);

    echo json_encode([
        'success' => true,
        'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
