# Realtime chat

`chat.php` no longer refreshes the whole page when messages are sent or received.

## How it works

- `chat.php` renders the existing conversation once.
- JavaScript calls `get_messages.php` every 1 second.
- It sends `after_id=<last message id>`, so only new messages are returned.
- `send_message.php` sends messages through `fetch()`, so sending does not reload the page.
- Messages from another logged-in person appear automatically while the chat is open.
- Mark Evans / Ollama still uses the same chatbot logic.

No database changes are required for this feature.
