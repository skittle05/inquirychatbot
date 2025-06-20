<?php
session_start();
include("connection.php");
include("functions.php");

$user_data = check_login($con);

// Handle chat messages
if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['message'])) {
    $message = $_POST['message'];
    $user_id = $user_data['user_id'];
    $api_key = 'your-openai-api-key-here'; // Replace with your OpenAI API key

    // Save user message to database
    $query = "INSERT INTO chat_messages (user_id, role, content) VALUES (?, 'user', ?)";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ss", $user_id, $message);
    mysqli_stmt_execute($stmt);

    // Call OpenAI API
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key
    ]);

    $data = [
        'model' => 'gpt-3.5-turbo',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful AI assistant.'],
            ['role' => 'user', 'content' => $message]
        ],
        'temperature' => 0.7
    ];

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    curl_close($ch);

    $response_data = json_decode($response, true);
    $ai_message = $response_data['choices'][0]['message']['content'] ?? 'Sorry, I could not process your request.';

    // Save AI response to database
    $query = "INSERT INTO chat_messages (user_id, role, content) VALUES (?, 'assistant', ?)";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "ss", $user_id, $ai_message);
    mysqli_stmt_execute($stmt);

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode(['response' => $ai_message]);
    exit;
}

// Get chat history
$chat_history = [];
if (isset($user_data['user_id'])) {
    $query = "SELECT * FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, "s", $user_data['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $chat_history[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPT Clone</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .chat-container {
            height: calc(100vh - 2rem);
            display: grid;
            grid-template-rows: auto 1fr auto;
        }

        .messages {
            overflow-y: auto;
            padding: 1rem;
            scroll-behavior: smooth;
        }

        .message {
            max-width: 80%;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            animation: fadeIn 0.3s ease;
        }

        .user-message {
            margin-left: auto;
            background-color: #1a73e8;
            color: white;
        }

        .ai-message {
            margin-right: auto;
            background-color: #f1f3f4;
            color: #202124;
        }

        .input-container {
            position: relative;
            padding: 1rem;
            background-color: white;
            border-top: 1px solid #e0e0e0;
        }

        .input-box {
            width: 100%;
            padding: 0.75rem;
            padding-right: 3rem;
            border: 1px solid #e0e0e0;
            border-radius: 0.5rem;
            resize: none;
            max-height: 200px;
            overflow-y: auto;
        }

        .send-button {
            position: absolute;
            right: 1.5rem;
            bottom: 1.5rem;
            background-color: #1a73e8;
            color: white;
            padding: 0.5rem;
            border-radius: 50%;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .send-button:hover {
            background-color: #1557b0;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .typing-indicator {
            display: flex;
            gap: 0.5rem;
            padding: 1rem;
            background-color: #f1f3f4;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            width: fit-content;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            background-color: #202124;
            border-radius: 50%;
            animation: typing 1s infinite ease-in-out;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="chat-container">
        <!-- Header -->
        <header class="bg-white shadow-sm p-4">
            <div class="max-w-7xl mx-auto flex justify-between items-center">
                <h1 class="text-xl font-semibold text-gray-800">GPT Clone</h1>
                <div class="flex items-center gap-4">
                    <span class="text-gray-600">Welcome, <?php echo htmlspecialchars($user_data['first_name']); ?></span>
                    <a href="logout.php" class="text-red-500 hover:text-red-600">Logout</a>
                </div>
            </div>
        </header>

        <!-- Messages Container -->
        <div class="messages" id="messages">
            <?php foreach ($chat_history as $message): ?>
                <div class="message <?php echo $message['role'] === 'user' ? 'user-message' : 'ai-message'; ?>">
                    <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Input Container -->
        <div class="input-container">
            <textarea
                id="messageInput"
                class="input-box"
                placeholder="Type your message here..."
                rows="1"
                onkeydown="if(event.keyCode == 13 && !event.shiftKey) { event.preventDefault(); sendMessage(); }">
            </textarea>
            <button onclick="sendMessage()" class="send-button">
                <span class="material-icons">send</span>
            </button>
        </div>
    </div>

    <script>
        const messagesContainer = document.getElementById('messages');
        const messageInput = document.getElementById('messageInput');

        // Scroll to bottom of messages
        function scrollToBottom() {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Add message to chat
        function addMessage(content, isUser = true) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user-message' : 'ai-message'}`;
            messageDiv.innerHTML = content.replace(/\n/g, '<br>');
            messagesContainer.appendChild(messageDiv);
            scrollToBottom();
        }

        // Show typing indicator
        function showTypingIndicator() {
            const indicator = document.createElement('div');
            indicator.className = 'typing-indicator';
            indicator.innerHTML = `
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            `;
            messagesContainer.appendChild(indicator);
            scrollToBottom();
            return indicator;
        }

        // Send message
        function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            // Add user message
            addMessage(message, true);
            messageInput.value = '';

            // Show typing indicator
            const typingIndicator = showTypingIndicator();

            // Send to server
            fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `message=${encodeURIComponent(message)}`
                })
                .then(response => response.json())
                .then(data => {
                    // Remove typing indicator
                    typingIndicator.remove();
                    // Add AI response
                    addMessage(data.response, false);
                })
                .catch(error => {
                    console.error('Error:', error);
                    typingIndicator.remove();
                    addMessage('Sorry, there was an error processing your request.', false);
                });
        }

        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Initial scroll to bottom
        scrollToBottom();
    </script>
</body>

</html>