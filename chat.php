<?php
include_once 'config.php';
include_once 'chatbot.php';

if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header("location: login.php");
    exit;
}

$currentUserId = (int) $_SESSION['unique_id'];
$toUserId = isset($_GET['to_user_id']) ? (int) $_GET['to_user_id'] : 0;

if ($toUserId <= 0 || $toUserId === $currentUserId) {
    http_response_code(400);
    exit('Invalid chat recipient.');
}

try {
    $stmt = $connect->prepare("SELECT * FROM user WHERE unique_id = ?");
    $stmt->execute([$currentUserId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $recipientStmt = $connect->prepare("SELECT unique_id, username, avatar, status FROM user WHERE unique_id = ?");
    $recipientStmt->execute([$toUserId]);
    $recipient = $recipientStmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || !$recipient) {
        http_response_code(404);
        exit('User not found.');
    }
} catch (PDOException $e) {
    echo "Error fetching user details: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    exit;
}

/**
 * Render the messages already in the database for the first page load and
 * return the highest message id that was rendered. JavaScript continues from
 * that id, so it only has to request newer messages afterwards.
 */
function displayMessages(PDO $connect, int $currentUserId, int $otherUserId): int
{
    $stmt = $connect->prepare("
        SELECT messages.*, user.avatar AS from_avatar, user.username AS from_username
        FROM messages
        JOIN user ON messages.from_user_id = user.unique_id
        WHERE (from_user_id = ? AND to_user_id = ?)
           OR (from_user_id = ? AND to_user_id = ?)
        ORDER BY messages.id ASC
    ");
    $stmt->execute([$currentUserId, $otherUserId, $otherUserId, $currentUserId]);

    $lastMessageId = 0;

    while ($message = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $lastMessageId = max($lastMessageId, (int) $message['id']);
        $isOutgoing = (int) $message['from_user_id'] === $currentUserId;
        $messageClass = $isOutgoing ? 'chat-outgoing' : 'chat-incoming';
        $avatarClass = $isOutgoing ? 'chat-profilepic-left' : 'chat-profilepic-right';

        $safeMessage = nl2br(htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8'));
        $safeAvatar = htmlspecialchars($message['from_avatar'] ?? '', ENT_QUOTES, 'UTF-8');
        $safeUsername = htmlspecialchars($message['from_username'] ?? '', ENT_QUOTES, 'UTF-8');

        echo '<div class="chat-message ' . $messageClass . '" data-message-id="' . (int) $message['id'] . '">';

        if ($isOutgoing) {
            echo '<div class="details-outgoing"><p class="mb-0">' . $safeMessage . '</p></div>';
            echo '<img class="' . $avatarClass . '" src="' . $safeAvatar . '" alt="' . $safeUsername . '">';
        } else {
            echo '<img class="' . $avatarClass . '" src="' . $safeAvatar . '" alt="' . $safeUsername . '">';
            echo '<div class="details-incoming"><p class="mb-0">' . $safeMessage . '</p></div>';
        }

        echo '</div>';
    }

    return $lastMessageId;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$isBot = $toUserId === MARK_BOT_UNIQUE_ID;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.2/css/all.min.css" />
    <link rel="icon" type="image/x-icon" href="img/Screenshot_2023-11-15_124317-removebg-preview.png">
    <style>
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
            overflow-wrap: anywhere;
        }

        .details-outgoing p,
        .details-incoming p {
            white-space: pre-wrap;
        }

        .chat-profilepic-left,
        .chat-profilepic-right {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-top: 26px;
        }

        .chat-profilepic-left { margin-right: 10px; }
        .chat-profilepic-right { margin-left: 10px; }

        .bot-badge {
            display: inline-block;
            margin-left: 8px;
            padding: 2px 8px;
            border-radius: 999px;
            background: #198754;
            color: white;
            font-size: 11px;
            vertical-align: middle;
        }

        .chat-connection-status {
            min-height: 18px;
            padding: 0 30px;
            font-size: 12px;
            color: #6c757d;
        }

        .chat-connection-status.error {
            color: #dc3545;
        }

        .typing-area button:disabled,
        .typing-area input:disabled {
            opacity: .65;
            cursor: not-allowed;
        }
    </style>
    <title>Inalink</title>
</head>
<body background="img/inalink.png" style="background-size: cover; background-attachment: fixed;">
    <?php include 'header.php'; ?> <br>
    <section class="form signup container">
        <header class="which-chat d-flex align-items-center">
            <a href="index.php" style="color: green;"><i class="fas fa-arrow-left"></i></a>
            <img class="chat-profilepic" src="<?php echo htmlspecialchars($recipient['avatar'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($recipient['username'], ENT_QUOTES, 'UTF-8'); ?>">
            <div class="details ml-3">
                <span class="font-weight-bold">
                    <?php echo htmlspecialchars($recipient['username'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($isBot): ?><span class="bot-badge">AI</span><?php endif; ?>
                </span>
                <div class="d-flex align-items-center">
                    <p class="mb-0"><?php echo $isBot ? 'always ready to play!' : htmlspecialchars($recipient['status'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <div class="status-dot ml-2 fa-sm">
                        <i class="fas fa-circle" style="color: <?php echo ($isBot || $recipient['status'] === 'active now') ? 'green' : 'red'; ?>"></i>
                    </div>
                </div>
            </div>
        </header>

        <div class="chat-area">
            <div class="chatbox" id="chatbox">
                <?php $lastMessageId = displayMessages($connect, $currentUserId, $toUserId); ?>
            </div>
            <div id="chatConnectionStatus" class="chat-connection-status" aria-live="polite"></div>
            <form class="typing-area" id="messageForm" autocomplete="off">
                <input id="messageInput" type="text" name="message" placeholder="Message" autocomplete="off" maxlength="5000" required>
                <button id="sendButton" type="submit" aria-label="Send message"><i class="fab fa-telegram-plane"></i></button>
            </form>
        </div>
    </section>

    <script>
        (() => {
            const currentUserId = <?php echo json_encode($currentUserId); ?>;
            const toUserId = <?php echo json_encode($toUserId); ?>;
            const isBotChat = <?php echo json_encode($isBot); ?>;
            let lastMessageId = <?php echo json_encode($lastMessageId); ?>;

            const chatbox = document.getElementById('chatbox');
            const messageForm = document.getElementById('messageForm');
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');
            const connectionStatus = document.getElementById('chatConnectionStatus');

            let pollTimer = null;
            let pollInProgress = false;
            let sendInProgress = false;
            let consecutivePollErrors = 0;

            const isNearBottom = () => {
                const distanceFromBottom = chatbox.scrollHeight - chatbox.scrollTop - chatbox.clientHeight;
                return distanceFromBottom < 120;
            };

            const scrollToBottom = (behavior = 'auto') => {
                chatbox.scrollTo({ top: chatbox.scrollHeight, behavior });
            };

            const setStatus = (text = '', isError = false) => {
                connectionStatus.textContent = text;
                connectionStatus.classList.toggle('error', isError);
            };

            const createMessageElement = (message) => {
                const isOutgoing = Number(message.from_user_id) === Number(currentUserId);
                const wrapper = document.createElement('div');
                wrapper.className = `chat-message ${isOutgoing ? 'chat-outgoing' : 'chat-incoming'}`;
                wrapper.dataset.messageId = message.id;

                const details = document.createElement('div');
                details.className = isOutgoing ? 'details-outgoing' : 'details-incoming';

                const paragraph = document.createElement('p');
                paragraph.className = 'mb-0';
                paragraph.textContent = message.message;
                details.appendChild(paragraph);

                const avatar = document.createElement('img');
                avatar.className = isOutgoing ? 'chat-profilepic-left' : 'chat-profilepic-right';
                avatar.src = message.from_avatar || '';
                avatar.alt = message.from_username || '';

                if (isOutgoing) {
                    wrapper.appendChild(details);
                    wrapper.appendChild(avatar);
                } else {
                    wrapper.appendChild(avatar);
                    wrapper.appendChild(details);
                }

                return wrapper;
            };

            const fetchNewMessages = async () => {
                if (pollInProgress) return;
                pollInProgress = true;

                try {
                    const wasNearBottom = isNearBottom();
                    const response = await fetch(
                        `get_messages.php?to_user_id=${encodeURIComponent(toUserId)}&after_id=${encodeURIComponent(lastMessageId)}`,
                        { cache: 'no-store' }
                    );

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const data = await response.json();
                    if (!data.success || !Array.isArray(data.messages)) {
                        throw new Error(data.error || 'Could not load messages');
                    }

                    let receivedIncomingMessage = false;

                    data.messages.forEach((message) => {
                        const id = Number(message.id);
                        if (!Number.isFinite(id) || id <= lastMessageId) return;

                        chatbox.appendChild(createMessageElement(message));
                        lastMessageId = id;

                        if (Number(message.from_user_id) !== Number(currentUserId)) {
                            receivedIncomingMessage = true;
                        }
                    });

                    consecutivePollErrors = 0;
                    // While PHP is waiting for the local AI response, keep the
                    // thinking status visible instead of clearing it after polling.
                    if (sendInProgress && isBotChat) {
                        setStatus('Mark typt…');
                    } else {
                        setStatus('');
                    }

                    // Keep following the conversation if the user is already at the
                    // bottom. New incoming messages also scroll into view automatically.
                    if (data.messages.length > 0 && (wasNearBottom || receivedIncomingMessage)) {
                        scrollToBottom('smooth');
                    }
                } catch (error) {
                    consecutivePollErrors++;
                    if (consecutivePollErrors >= 2) {
                        setStatus('Verbinding met de chat wordt opnieuw geprobeerd…', true);
                    }
                    console.error('Message polling failed:', error);
                } finally {
                    pollInProgress = false;
                }
            };

            const schedulePolling = () => {
                clearInterval(pollTimer);
                // One second feels close to real-time while staying simple for PHP/XAMPP.
                pollTimer = setInterval(fetchNewMessages, 1000);
            };

            messageForm.addEventListener('submit', async (event) => {
                event.preventDefault();

                const message = messageInput.value.trim();
                if (!message || sendInProgress) return;

                sendInProgress = true;

                // Clear immediately so chatting feels responsive. The one-second poll
                // will display the stored message even while Mark/Ollama is still thinking.
                messageInput.value = '';
                sendButton.disabled = true;
                setStatus(isBotChat ? 'Mark typt…' : 'Bericht versturen…');

                const body = new URLSearchParams();
                body.set('to_user_id', toUserId);
                body.set('message', message);

                try {
                    // Start polling shortly after the send request. send_message.php
                    // releases the PHP session lock, so this also works for slower AI replies.
                    setTimeout(fetchNewMessages, 100);

                    const response = await fetch('send_message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        body: body.toString()
                    });

                    const data = await response.json().catch(() => null);
                    if (!response.ok || !data || !data.success) {
                        throw new Error(data?.error || `HTTP ${response.status}`);
                    }

                    await fetchNewMessages();
                    setStatus('');
                } catch (error) {
                    // Put the text back if sending failed, as long as the user hasn't
                    // started typing another message in the meantime.
                    if (!messageInput.value) {
                        messageInput.value = message;
                    }
                    setStatus('Bericht kon niet worden verstuurd. Probeer het opnieuw.', true);
                    console.error('Sending message failed:', error);
                } finally {
                    sendInProgress = false;
                    sendButton.disabled = false;
                    messageInput.focus();
                }
            });

            // The server rendered existing messages, so start at the bottom once.
            scrollToBottom();
            schedulePolling();

            // When returning to the tab, immediately catch up instead of waiting for
            // the next interval tick.
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden) fetchNewMessages();
            });

            window.addEventListener('focus', fetchNewMessages);
        })();
    </script>
</body>
</html>
