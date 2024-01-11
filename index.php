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
?>

<?php
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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css"/>
    <link rel="icon" type="image-x-icon" href="img/Screenshot_2023-11-15_124317-removebg-preview.png">
    <title>Inalink</title>
</head>
<body background="img/inalink.png" style="background-size: cover; background-attachment: fixed;">
    <?php include 'header.php' ?> <br>
    <section class="users form signup container">
        <header class="d-flex">
            <img class="profilepic" src="<?php echo $row['avatar']; ?>" alt="">
            <div class="details">
                <span style="font-weight: bold;"><?php echo $row['username']; ?></span>
                <p>active now</p>
            </div>
            <div class="ml-auto">
                <a href="logout.php">
                    <button class="btn btn-dark btn-lg">Logout</button>
                </a>
            </div>
        </header>
        <div class="search d-flex">
            <div class="input-group">
                <input class="form-control mr-sm-2 search-input" type="text" placeholder="Enter a name..." id="searchInput">
                <button class="btn btn-success" type="button" onclick="searchUsers()"><i class="fas fa-search"></i></button>
            </div>
        </div>

        <div class="chats-container" style="max-height: 450px; overflow-y: auto;">
            <?php
            try {
                // Fetch all users from the database except the logged-in user
                $stmt = $connect->prepare("SELECT * FROM user WHERE unique_id != ?");
                $stmt->execute([$_SESSION['unique_id']]);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo "Error fetching user details: " . $e->getMessage();
                exit;
            }

             foreach ($users as $user): ?>
                <div class="user-list d-flex align-items-center" id="user-<?php echo $user['unique_id']; ?>">
                <a href="chat.php?username=<?php echo $user['username']; ?>&status=<?php echo $user['status']; ?>&avatar=<?php echo urlencode($user['avatar']); ?>&to_user_id=<?php echo $user['unique_id']; ?>" class="d-flex align-items-center w-100">
                        <img src="<?php echo $user['avatar']; ?>" alt="">
                        <div class="details ml-2 flex-grow-1">
                            <span><?php echo $user['username']; ?></span>
                            <p><?php echo $user['status']; ?></p>
                        </div>
                        <div>
                            <div class="status-dot" style="color: <?php echo ($user['status'] == 'active now') ? 'green' : 'red'; ?>">
                                <i class="fas fa-circle"></i>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
                        
        </div>
    </section>
    <script src="script/users.js"></script>
    <script>
        function searchUsers() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const users = <?php echo json_encode($users); ?>;
            const container = document.querySelector('.chats-container');

            users.forEach(user => {
                const userElement = document.getElementById('user-' + user['unique_id']);
                if (user['username'].toLowerCase().includes(input)) {
                    userElement.style.display = 'flex';
                } else {
                    userElement.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
