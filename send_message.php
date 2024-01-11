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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_user_id = $_SESSION['unique_id'];
    $to_user_id = $_POST['to_user_id'];
    $message = $_POST['message'];

    try {
        $stmt = $connect->prepare("INSERT INTO messages (from_user_id, to_user_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$from_user_id, $to_user_id, $message]);
        // Add any additional handling or validation if needed
    } catch (PDOException $e) {
        echo "Error sending message: " . $e->getMessage();
        exit;
    }
}
