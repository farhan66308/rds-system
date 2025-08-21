<?php

function displayMessage($message) {
    // Escape HTML to prevent XSS attacks
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Message</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .message-box {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
            border: 1px solid #e0e0e0;
        }
        h1 {
            color: #333;
            font-size: 2em;
            margin-top: 0;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            font-size: 1.2em;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="message-box">
        <h1>Notification</h1>
        <p>{$safeMessage}</p>
    </div>
</body>
</html>
HTML;
}

// Example usage:
// To display a message from another file, you would call this function.
// For example:
// require_once 'message_display.php';
// displayMessage('Your request has been successfully processed!');
?>