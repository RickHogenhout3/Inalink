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

try {
    // Fetch user details from the database based on unique_id
    $stmt = $connect->prepare("SELECT * FROM user WHERE unique_id = ?");
    $stmt->execute([$_SESSION['unique_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching user details: " . $e->getMessage();
    exit;
}

// Function to retrieve and display messages
function displayMessages($connect, $fromUserId, $toUserId)
{
    $stmt = $connect->prepare("
        SELECT messages.*, user.avatar AS from_avatar, user.username AS from_username
        FROM messages
        JOIN user ON messages.from_user_id = user.unique_id
        WHERE (from_user_id = ? AND to_user_id = ?) OR (from_user_id = ? AND to_user_id = ?)
        ORDER BY timestamp
    ");
    $stmt->execute([$fromUserId, $toUserId, $toUserId, $fromUserId]);

    while ($message = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fromAvatar = isset($message['from_avatar']) ? $message['from_avatar'] : '';
        $fromUsername = isset($message['from_username']) ? $message['from_username'] : '';

        // Determine the class based on the sender
        $messageClass = ($message['from_user_id'] == $fromUserId) ? 'chat-outgoing' : 'chat-incoming';
        $avatarClass = ($message['from_user_id'] == $fromUserId) ? 'chat-profilepic-left' : 'chat-profilepic-right';

        echo '<div class="chat-message ' . $messageClass . '">';
        if ($messageClass == 'chat-outgoing') {
            // Structure for outgoing messages
            echo '<div class="details-outgoing">';
            echo $message['message'] . '</p>';
            echo '</div>';
            echo '<img class="' . $avatarClass . '" src="' . $fromAvatar . '" alt="' . $fromUsername . '">';
        } else {
            // Structure for incoming messages
            echo '<img class="' . $avatarClass . '" src="' . $fromAvatar . '" alt="' . $fromUsername . '">';
            echo '<div class="details-incoming">';
            echo $message['message'] . '</p>';
            echo '</div>';
        }
        echo '</div>';
    }
}

// Function to send a message
function sendMessage($connect, $fromUserId, $toUserId, $message)
{
    $stmt = $connect->prepare("
        INSERT INTO messages (from_user_id, to_user_id, message) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$fromUserId, $toUserId, $message]);
}

// Handle form submission to send a message
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['message'])) {
    $toUserId = isset($_GET['to_user_id']) ? $_GET['to_user_id'] : null;

    if ($toUserId) {
        sendMessage($connect, $_SESSION['unique_id'], $toUserId, $_POST['message']);
        header("Location: chat.php?username=" . $_GET['username'] . "&status=" . $_GET['status'] . "&avatar=" . $_GET['avatar'] . "&to_user_id=" . $toUserId);
    } else {
        echo "Invalid parameters.";
    }
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600l;700&display=swap">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css" />
    <link rel="icon" type="image-x-icon" href="img/Screenshot_2023-11-15_124317-removebg-preview.png">
    <style>
        /* Add your additional styles for chat boxes here */
        .chat-message {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .details-outgoing,
        .details-incoming {
            background-color: aliceblue;
            color: blue;
            padding: 10px;
            border-radius: 8px;
            max-width: 70%;
            word-wrap: break-word;
        }

        .chat-profilepic-left {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 10px;
            margin-top: 26px;
        }

        .chat-profilepic-right {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-left: 10px;
            margin-top: 26px;
        }

        /* Your existing styles can be included here */
    </style>
    <title>Inalink</title>
</head>

<body background="img/inalink.png" style="background-size: cover; background-attachment: fixed;">
    <?php include 'header.php' ?> <br>
    <section class="form signup container">
        <?php
        if (isset($_GET['username']) && isset($_GET['status']) && isset($_GET['avatar'])) {
            $username = $_GET['username'];
            $status = $_GET['status'];
            $avatar = urldecode($_GET['avatar']);
            $toUserId = $_GET['to_user_id']; // Assuming you have this parameter in your URL
        ?>
            <header class="which-chat d-flex align-items-center">
                <a href="index.php" style="color: green;"><i class="fas fa-arrow-left"></i></a>
                <img class="chat-profilepic" src="<?php echo $avatar; ?>" alt="<?php echo $username; ?>">
                <div class="details ml-3">
                    <span class="font-weight-bold"><?php echo $username; ?></span>
                    <div class="d-flex align-items-center">
                        <p class="mb-0"><?php echo $status; ?></p>
                        <div class="status-dot ml-2 fa-sm">
    <i class="fas fa-circle" style="color: <?php echo ($status === 'active now') ? 'green' : 'red'; ?>"></i>
</div>

                    </div>
                </div>
            </header>

            <div class="chat-area">
                <div class="chatbox">
                    <?php
                    // Display messages
                    displayMessages($connect, $_SESSION['unique_id'], $toUserId, $row['username'], $row['avatar']);
                    ?>
                </div>
                <form action="#" method="post" class="typing-area">
                    <input type="text" name="message" placeholder="Message">
                    <button type="submit"><i class="fab fa-telegram-plane"></i></button>
                </form>
            </div>
        <?php
        } else {
            echo "Invalid parameters.";
        }
        ?>
    </section>
</body>

</html>
