<?php
include 'config.php';

// Function to safely get user input
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

$errors = []; // Array to store errors

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Generate a unique ID
    $unique_id = generateUniqueID();

    // Retrieve form data
    $fname = test_input($_POST['fname']);
    $lname = test_input($_POST['lname']);
    $username = test_input($_POST['username']);
    $password = test_input($_POST['password']);
    $email = test_input($_POST['email']);
    $avatar = test_input($_POST['selected_avatar']);

    // Hash the password using BCRYPT
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        // Check if the username or email already exists
        $stmt = $connect->prepare("SELECT * FROM user WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            $errors[] = "Username or email already exists. Please choose a different one.";
        } else {
            // Your SQL query using prepared statements to prevent SQL injection
            $stmt = $connect->prepare("INSERT INTO user (unique_id, firstname, lastname, username, password, email, avatar) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?)");

            // Execute the query with the provided values
            $stmt->execute([$unique_id, $fname, $lname, $username, $hashed_password, $email, $avatar]);

            // Optionally, you can redirect the user to the login page after successful registration
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        $errors[] = "Error during registration: " . $e->getMessage();
    }

    // Close the connection (optional, as PDO closes automatically when the script ends)
    $connect = null;
}

// Function to generate a unique ID as a random number
function generateUniqueID() {
    // You can customize the logic for generating a unique ID based on your requirements
    return mt_rand(10000000, 99999999); // Adjust the range as needed
}
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
    <div class="wrapper">
        <section class="form signup container">
            <header class="welcome text-center mb-4">Welcome to Inalink</header>

            <?php
            // Display errors in red text
            if (!empty($errors)) {
                echo '<div class="errors text-center" style="color: red; font-weight: bold;">';
                foreach ($errors as $error) {
                    echo $error . '<br>';
                }
                echo '</div>';
            }
            ?>

            <form method="post" onsubmit="return validateForm();">
                <div class="row">
                    <div class="col-md-6">
                        <div class="field input">
                            <label>First Name</label>
                            <input type="text" name="fname" class="form-control" placeholder="First Name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="field input">
                            <label>Last Name</label>
                            <input type="text" name="lname" class="form-control" placeholder="Last Name" required>
                        </div>
                    </div>
                </div>
                <div class="field input">
                    <label for="username">Username</label>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="field input">
                    <label for="password">Password</label>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="field input">
                    <label for="email">Email</label>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="field input">
                        <label>Select Avatar</label>
                        <div class="avatar-container">
                            <img src="avatars/Endou_Mamoru_avatar.png" alt="Avatar 1" class="avatar" onclick="selectAvatar('avatars/Endou_Mamoru_avatar.png', this)">
                            <img src="avatars/Gouenji_Shuuya_avatar.png" alt="Avatar 3" class="avatar" onclick="selectAvatar('avatars/Gouenji_Shuuya_avatar.png', this)">
                            <img src="avatars/Kazemaru_Ichirouta_avatar.png" alt="Avatar 5" class="avatar" onclick="selectAvatar('avatars/Kazemaru_Ichirouta_avatar.png', this)">
                            <img src="avatars/Kabeyama_Heigorou_avatar.png" alt="Avatar 12" class="avatar" onclick="selectAvatar('avatars/Kabeyama_Heigorou_avatar.png', this)">
                            <img src="avatars/Someoka_Ryugo_avatar.png" alt="Avatar 13" class="avatar" onclick="selectAvatar('avatars/Someoka_Ryugo_avatar.png', this)">
                            <img src="avatars/Kidou_Yuuto_avatar.png" alt="Avatar 6" class="avatar" onclick="selectAvatar('avatars/Kidou_Yuuto_avatar.png', this)">
                            <img src="avatars/Fubuki_Shirou_avatar.png" alt="Avatar 2" class="avatar" onclick="selectAvatar('avatars/Fubuki_Shirou_avatar.png', this)"><img src="avatars/Tachimukai_Yuuki_avatar.png" alt="Avatar 4" class="avatar" onclick="selectAvatar('avatars/Tachimukai_Yuuki_avatar.png', this)">
                            <img src="avatars/Tsunami_Jousuke_avatar.png" alt="Avatar 14" class="avatar" onclick="selectAvatar('avatars/Tsunami_Jousuke_avatar.png', this)">
                            <img src="avatars/Zaizen_Touko_avatar.png" alt="Avatar 15" class="avatar" onclick="selectAvatar('avatars/Zaizen_Touko_avatar.png', this)">
                            <img src="avatars/Kiyama_Hiroto_avatar.png" alt="Avatar 7" class="avatar" onclick="selectAvatar('avatars/Kiyama_Hiroto_avatar.png', this)">
                            <img src="avatars/Utsunomiya_Toramaru_avatar.png" alt="Avatar 16" class="avatar" onclick="selectAvatar('avatars/Utsunomiya_Toramaru_avatar.png', this)">
                            <img src="avatars/Fudou_Akio_avatar.png" alt="Avatar 17" class="avatar" onclick="selectAvatar('avatars/Fudou_Akio_avatar.png', this)">
                            <img src="avatars/Sakuma_Jirou_avatar.png" alt="Avatar 18" class="avatar" onclick="selectAvatar('avatars/Sakuma_Jirou_avatar.png', this)"> 
                            <img src="avatars/Genda_Koujirou_avatar.png" alt="Avatar 19" class="avatar" onclick="selectAvatar('avatars/Genda_Koujirou_avatar.png', this)">
                            <img src="avatars/Afuro_Terumi_avatar.png" alt="Avatar 22" class="avatar" onclick="selectAvatar('avatars/Afuro_Terumi_avatar.png', this)"><br>
                            <img src="avatars/Matsukaze_Tenma_avatar.png" alt="Avatar 8" class="avatar" onclick="selectAvatar('avatars/Matsukaze_Tenma_avatar.png', this)">
                            <img src="avatars/Shindou_Takuto_avatar.png" alt="Avatar 9" class="avatar" onclick="selectAvatar('avatars/Shindou_Takuto_avatar.png', this)">
                            <img src="avatars/Tsurugi_Kyousuke_avatar.png" alt="Avatar 10" class="avatar" onclick="selectAvatar('avatars/Tsurugi_Kyousuke_avatar.png', this)">
                            <img src="avatars/Nishizono_Shinsuke_avatar.png" alt="Avatar 20" class="avatar" onclick="selectAvatar('avatars/Nishizono_Shinsuke_avatar.png', this)">
                            <img src="avatars/Kirino_Ranmaru_avatar.png" alt="Avatar 21" class="avatar" onclick="selectAvatar('avatars/Kirino_Ranmaru_avatar.png', this)">
                            <img src="avatars/Nishiki_Ryouma_avatar.png" alt="Avatar 23" class="avatar" onclick="selectAvatar('avatars/Nishiki_Ryouma_avatar.png', this)">
                            <img src="avatars/Sangoku_Taichi_avatar.png" alt="Avatar 24" class="avatar" onclick="selectAvatar('avatars/Sangoku_Taichi_avatar.png', this)">
                            <img src="avatars/Tsurugi_Yuuichi_avatar.png" alt="avatar 11" class="avatar" onclick="selectAvatar('avatars/Tsurugi_Yuuichi_avatar.png', this)">
                        </div>
                        <input type="hidden" name="selected_avatar" id="selected_avatar" required>
                    </div>
                    <div class="field button">
                        <input type="submit" value="Continue to Chat">
                    </div>
                <p class="welcome">already have an account? then please <a href="login.php">login</a></p>
                </div>
            </form>
        </section>
    </div>

    <script>
    function validateForm() {
        // Check if an avatar is selected
        var selectedAvatar = document.getElementById('selected_avatar').value;

        if (!selectedAvatar) {
            alert('Please select an avatar before continuing.');
            return false; // Prevent form submission
        }

        return true; // Allow form submission
    }

    function selectAvatar(avatar, element) {
        // Reset all avatars to remove the selection indicator
        var avatars = document.querySelectorAll('.avatar');
        avatars.forEach(function (avatarElement) {
            avatarElement.classList.remove('avatar-selected');
        });

        // Set the selected avatar
        document.getElementById('selected_avatar').value = avatar;

        // Add the selection indicator to the clicked avatar
        element.classList.add('avatar-selected');
    }
</script>

    <script src="script/signup.js"></script>
</body>
</html>
