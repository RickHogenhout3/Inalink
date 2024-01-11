<?php
include_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('unieke_sessie_naam');
    session_start();
}

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header("location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['to_user_id'])) {
    $from_user_id = $_SESSION['unique_id'];
    $to_user_id = $_GET['to_user_id'];

    try {
        $stmt = $connect->prepare("SELECT * FROM messages WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?) ORDER BY timestamp ASC");
        $stmt->execute([$from_user_id, $to_user_id, $to_user_id, $from_user_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Return $messages as JSON or process as needed
        echo json_encode($messages);
    } catch (PDOException $e) {
        echo "Error retrieving messages: " . $e->getMessage();
        exit;
    }
}
