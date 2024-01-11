<?php
include_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('unieke_sessie_naam');
    session_start();
}

$errors = []; // Array to store errors

// Functie om veilig gebruikersinvoer te verkrijgen
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Controleren of het formulier is ingediend
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Gebruikersinvoer verkrijgen
    $emailOrUsername = test_input($_POST['emailOrUsername']);
    $password = test_input($_POST['password']);

    try {
        // Controleren of de gebruiker bestaat in de database
        $stmt = $connect->prepare("SELECT * FROM user WHERE email = ? OR username = ?");
        $stmt->execute([$emailOrUsername, $emailOrUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verifieer het verstrekte wachtwoord tegen het gehashte wachtwoord in de database
            if (password_verify($password, $user['password'])) {
                // Wachtwoord is correct, log de gebruiker in

                // Vernieuw de sessie-id
                session_regenerate_id(true);

                // Andere sessievariabelen instellen indien nodig
                $_SESSION['loggedin'] = true;
                $_SESSION['unique_id'] = $user['unique_id'];
                $_SESSION['username'] = $user['username'];

                // Sla de sessievariabelen onmiddellijk op
                session_write_close();

                // Update de status naar "actief" voor de ingelogde gebruiker
                $stmt = $connect->prepare("UPDATE user SET status = 'active now' WHERE unique_id = ?");
                $stmt->execute([$_SESSION['unique_id']]);

                header("Location: inalink.php");
                exit();
            } else {
                $errors[] = "Incorrect wachtwoord. Probeer het opnieuw.";
            }
        } else {
            $errors[] = "Gebruiker niet gevonden. Controleer uw e-mail/gebruikersnaam en probeer het opnieuw.";
        }
    } catch (PDOException $e) {
        $errors[] = "Fout tijdens het inloggen: " . $e->getMessage();
    }
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
            
            <form action="#" method="post">
                <div class="field input">
                    <label for="emailOrUsername">Email or Username</label>
                    <input type="text" name="emailOrUsername" placeholder="Email or Username" required>
                </div>
                <div class="field input">
                    <label for="password">Password</label>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="field button">
                    <input type="submit" value="Continue to Chat">
                </div>
                <p class="welcome">No account yet? Then please <a href="signup.php">signup</a></p>
            </form>
        </section>
    </div>
</body>
</html>
