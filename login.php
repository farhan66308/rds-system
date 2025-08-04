<?php
require_once 'conn.php';
require_once 'libs/GoogleAuthenticator.php';
$g = new PHPGangsta_GoogleAuthenticator();

session_start();

$errors = [];
$db = new Database(); 
$conn = $db->getConnection();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = $_POST['email'];
    $password = $_POST['password'];

    if (empty($identifier) || empty($password)) {
        $errors['login'] = "Please enter both username/email and password.";
    } else {
        if ($identifier === 'admin' && $password === 'admin') {
            $_SESSION['isAdmin'] = true;
            header('Location: admin/adminpanel.php');
            exit();
        }

        // Check credentials
        $stmt = $conn->prepare("SELECT UserID, UserFlag, password FROM users WHERE Email = ? AND password = ?");
        if (!$stmt) {
        die("Prepare failed: " . $conn->error);
        }
$stmt->bind_param("ss", $identifier, $password); // both are strings
$stmt->execute();
$result = $stmt->get_result();


        if ($user = $result->fetch_assoc()) {
            if ((int)$user['UserFlag'] === 0) {
                echo '
                <div style="display: flex; justify-content: center; align-items: center; height: 100vh; flex-direction: column;">
                <img src="https://media.giphy.com/media/3o7TKP9hD3sUUAW3dK/giphy.gif" alt="Oops GIF" style="max-width: 400px; border-radius: 10px;">
                <h2 style="margin-top: 20px; color: #e74c3c; font-family: Arial, sans-serif;">Oops! You are cooked, your account has been boomed. Talk to Farhan the owner</h2>
                </div>';
                exit();
            }

            // Check if 2FA secret exists in 2fa table
            $stmt2 = $conn->prepare("SELECT TwoFASecret FROM 2fa WHERE UserID = ?");
            $stmt2->bind_param("i", $user['UserID']);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $fa = $result2->fetch_assoc();
            $stmt2->close();

            $_SESSION['UserID'] = $user['UserID'];

            if (!$fa) {
                // No 2FA setup yet
                $secret = $g->createSecret();;

                // Save temp secret in session
                $_SESSION['TempSecret'] = $secret;

                header("Location: 2fa/setup-2fa.php");
                exit();
            } else {
                // 2FA already set, go to verification
                header("Location: 2fa/verify-2fa.php");
                exit();
            }
        } else {
            $errors['login'] = "Invalid username/email or password.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Eduor - Login</title>
    <style>
        body {
            background: linear-gradient(120deg, #f093fb, #f5576c);
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            width: 350px;
            text-align: center;
        }

        .login-container h2 {
            margin-bottom: 25px;
            color: #333;
        }

        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .login-container button {
            width: 100%;
            padding: 12px;
            background-color: #2c3e50;
            color: white;
            font-size: 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .login-container button:hover {
            background-color: #34495e;
        }

        .error {
            color: red;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .branding {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .back-link {
            margin-top: 15px;
            display: block;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>

<body>

    <div class="login-container">
        <div class="branding">Student Portal</div>
        <h2>Login</h2>

        <?php if (!empty($errors['login'])): ?>
            <div class="error"><?php echo $errors['login']; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="text" name="email" placeholder="Username or Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <a class="back-link" href="index.php">← Back to Home</a>
    </div>

</body>

</html>