<?php
include_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('unieke_sessie_naam');
    session_start();
}

// Function to securely obtain user input
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Check if the user is logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    try {
        // Update the status to "inactive" for the logged-in user when logging out
        $stmt = $connect->prepare("UPDATE user SET status = 'inactive' WHERE unique_id = ?");
        $stmt->execute([$_SESSION['unique_id']]);
    } catch (PDOException $e) {
        // Handle the error as needed
        echo "Error updating status: " . $e->getMessage();
    }
}

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to the login page
header("location: inalink.php");
exit();
?>
