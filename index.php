<?php
include_once 'config.php';

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header("location: login.php");
    exit;
}

try {
    $stmt = $connect->prepare("SELECT * FROM user WHERE unique_id = ?");
    $stmt->execute([$_SESSION['unique_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching user details: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
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

    <link rel="stylesheet" href="style.css">

    <link
        rel="stylesheet"
        href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css"
    />

    <link
        rel="icon"
        type="image/x-icon"
        href="img/Screenshot_2023-11-15_124317-removebg-preview.png"
    >

    <title>Inalink</title>
</head>

<body
    background="img/inalink.png"
    style="background-size: cover; background-attachment: fixed;"
>

    <?php include 'header.php'; ?>

    <br>

    <section class="users form signup container">

        <!-- Ingelogde gebruiker -->
        <header class="d-flex">

            <img
                class="profilepic"
                src="<?php echo htmlspecialchars($row['avatar'], ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'); ?>"
            >

            <div class="details">

                <span style="font-weight: bold;">
                    <?php
                    echo htmlspecialchars(
                        $row['username'],
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>
                </span>

                <p>active now</p>

            </div>

            <div class="ml-auto">

                <a href="logout.php">
                    <button class="btn btn-dark btn-lg">
                        Logout
                    </button>
                </a>

            </div>

        </header>


        <!-- Zoekbalk -->
        <div class="search d-flex">

            <div class="input-group">

                <input
                    class="form-control mr-sm-2 search-input"
                    type="text"
                    placeholder="Enter a name..."
                    id="searchInput"
                >

                <button
                    class="btn btn-success"
                    type="button"
                    onclick="searchUsers()"
                >
                    <i class="fas fa-search"></i>
                </button>

            </div>

        </div>


        <!-- Chat gebruikers -->
        <div
            class="chats-container"
            style="max-height: 450px; overflow-y: auto;"
        >

            <?php

            try {

                /*
                 * Haalt alle gebruikers op behalve jezelf.
                 *
                 * Ook wordt per gebruiker het laatste bericht opgehaald
                 * tussen jou en die gebruiker.
                 *
                 * last_message:
                 *      Het laatste bericht.
                 *
                 * last_message_from:
                 *      De unique_id van degene die het laatste bericht
                 *      heeft gestuurd.
                 */

                $stmt = $connect->prepare("
                    SELECT
                        u.*,

                        (
                            SELECT m.message
                            FROM messages m

                            WHERE
                                (
                                    m.from_user_id = ?
                                    AND
                                    m.to_user_id = u.unique_id
                                )

                                OR

                                (
                                    m.from_user_id = u.unique_id
                                    AND
                                    m.to_user_id = ?
                                )

                            ORDER BY
                                m.timestamp DESC,
                                m.id DESC

                            LIMIT 1

                        ) AS last_message,


                        (
                            SELECT m.from_user_id
                            FROM messages m

                            WHERE
                                (
                                    m.from_user_id = ?
                                    AND
                                    m.to_user_id = u.unique_id
                                )

                                OR

                                (
                                    m.from_user_id = u.unique_id
                                    AND
                                    m.to_user_id = ?
                                )

                            ORDER BY
                                m.timestamp DESC,
                                m.id DESC

                            LIMIT 1

                        ) AS last_message_from


                    FROM user u

                    WHERE u.unique_id != ?

                    ORDER BY
                        (u.unique_id = ?) DESC,
                        u.username ASC
                ");


                $stmt->execute([

                    // Voor last_message
                    $_SESSION['unique_id'],
                    $_SESSION['unique_id'],

                    // Voor last_message_from
                    $_SESSION['unique_id'],
                    $_SESSION['unique_id'],

                    // Jezelf niet tonen
                    $_SESSION['unique_id'],

                    // AI bot altijd bovenaan
                    MARK_BOT_UNIQUE_ID

                ]);


                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);


            } catch (PDOException $e) {

                echo "Error fetching user details: "
                    . htmlspecialchars(
                        $e->getMessage(),
                        ENT_QUOTES,
                        'UTF-8'
                    );

                exit;
            }


            foreach ($users as $user):

                /*
                 * Controleren of deze gebruiker
                 * de vaste chatbot is.
                 */
                $isBot =
                    (int)$user['unique_id'] ===
                    (int)MARK_BOT_UNIQUE_ID;


                /*
                 * Laatste bericht ophalen.
                 */
                $lastMessage = $user['last_message'] ?? '';


                /*
                 * Controleren of jij het laatste
                 * bericht hebt gestuurd.
                 */
                $lastMessageIsMine =
                    !empty($user['last_message_from'])
                    &&
                    (int)$user['last_message_from'] ===
                    (int)$_SESSION['unique_id'];


                /*
                 * Lang bericht afkappen zodat
                 * de gebruikerslijst netjes blijft.
                 */
                if (mb_strlen($lastMessage) > 45) {

                    $lastMessage =
                        mb_substr(
                            $lastMessage,
                            0,
                            45
                        ) . '...';

                }

            ?>

                <div
                    class="user-list d-flex align-items-center"
                    id="user-<?php echo (int)$user['unique_id']; ?>"
                >

                    <a
                        href="chat.php?to_user_id=<?php echo (int)$user['unique_id']; ?>"
                        class="d-flex align-items-center w-100"
                    >

                        <!-- Profielfoto -->
                        <img
                            src="<?php
                            echo htmlspecialchars(
                                $user['avatar'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>"
                            alt="<?php
                            echo htmlspecialchars(
                                $user['username'],
                                ENT_QUOTES,
                                'UTF-8'
                            );
                            ?>"
                        >


                        <!-- Gebruikersinformatie -->
                        <div class="details ml-2 flex-grow-1">


                            <!-- Gebruikersnaam -->
                            <span>

                                <?php
                                echo htmlspecialchars(
                                    $user['username'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>


                                <!-- AI badge -->
                                <?php if ($isBot): ?>

                                    <small class="badge badge-success ml-1">
                                        AI Chatbot
                                    </small>

                                <?php endif; ?>

                            </span>


                            <!-- Status -->
                            <p>

                                <?php

                                if ($isBot) {

                                    echo 'Your standard Inalink teammate';

                                } else {

                                    echo htmlspecialchars(
                                        $user['status'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                }

                                ?>

                            </p>


                            <!-- Laatste bericht -->
                            <p class="last-message">

                                <?php

if (!empty($lastMessage)) {

    if ($lastMessageIsMine) {

        // Jij hebt het laatste bericht gestuurd
        echo '<strong>You: </strong>';

    } else {

        // De andere gebruiker heeft het laatste bericht gestuurd
        echo '<strong>'
            . htmlspecialchars(
                $user['username'],
                ENT_QUOTES,
                'UTF-8'
            )
            . ': </strong>';
    }

    echo htmlspecialchars(
        $lastMessage,
        ENT_QUOTES,
        'UTF-8'
    );

} else {

    echo 'Nog geen berichten';

}

?>
                            </p>
                        </div>

                        <div>

                            <div
                                class="status-dot"
                                style="color:
                                <?php

                                echo (
                                    $isBot ||
                                    $user['status'] === 'active now'
                                )
                                    ? 'green'
                                    : 'red';

                                ?>
                                "
                            >

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

            const input = document
                .getElementById('searchInput')
                .value
                .toLowerCase();


            const users = <?php
                echo json_encode(
                    $users,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                );
            ?>;


            users.forEach(user => {

                const userElement =
                    document.getElementById(
                        'user-' + user.unique_id
                    );


                if (userElement) {

                    userElement.style.display =
                        user.username
                            .toLowerCase()
                            .includes(input)
                            ? 'flex'
                            : 'none';

                }

            });

        }

    </script>

</body>

</html>