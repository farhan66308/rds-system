<?php
require_once 'conn.php';
require_once 'libs/GoogleAuthenticator.php';

session_start();

$errors = [];
$db = new Database();
$conn = $db->getConnection();

// Interface class
interface AuthStrategy {
    public function authenticate($conn, $identifier, $password, $code = null): bool;
    public function getUserId(): ?int;
}

// Password-only authentication strategy
class PasswordOnlyAuth implements AuthStrategy {
    private $userID = null;

    public function authenticate($conn, $identifier, $password, $code = null): bool {
        $stmt = $conn->prepare("SELECT UserID, UserFlag, password FROM users WHERE Email = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ((int)$user['UserFlag'] === 0) {
                $stmt->close();
                return false; // user banned/blocked
            }

            // Verify password (hashed or plain)
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $this->userID = $user['UserID'];
                $stmt->close();
                return true;
            }
        }

        $stmt->close();
        return false;
    }

    public function getUserId(): ?int {
        return $this->userID;
    }
}

// 2fa strategy
class TwoFactorAuth implements AuthStrategy {
    private $userID = null;
    private $g;

    public function __construct() {
        $this->g = new PHPGangsta_GoogleAuthenticator();
    }

    public function authenticate($conn, $identifier, $password, $code = null): bool {
        $stmt = $conn->prepare("SELECT UserID, UserFlag, password FROM users WHERE Email = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ((int)$user['UserFlag'] === 0) {
                $stmt->close();
                return false;
            }

            // Verify password
            if (!password_verify($password, $user['password']) && $password !== $user['password']) {
                $stmt->close();
                return false;
            }

            $userID = $user['UserID'];
            $stmt->close();

            // Fetch user's 2FA secret
            $stmt2 = $conn->prepare("SELECT TwoFASecret FROM 2fa WHERE UserID = ?");
            if (!$stmt2) {
                die("Prepare failed: " . $conn->error);
            }

            $stmt2->bind_param("i", $userID);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $fa = $result2->fetch_assoc();
            $stmt2->close();

            if (!$fa) {
                // No 2FA setup yet - redirect to setup
                $_SESSION['UserID'] = $userID;
                $_SESSION['TempSecret'] = $this->g->createSecret();
                header("Location: 2fa/setup-2fa.php");
                exit();
            } else {
                if ($code === null) {
                    // Redirect to 2FA verification page to enter code
                    $_SESSION['UserEmail'] = $identifier;
                    $_SESSION['UserPassword'] = $password;
                    header("Location: 2fa/verify-2fa.php");
                    exit();
                }
                if ($this->g->checkCode($fa['TwoFASecret'], $code)) {
                    $this->userID = $userID;
                    return true;
                } else {
                    return false;
                }
            }
        }

        $stmt->close();
        return false;
    }

    public function getUserId(): ?int {
        return $this->userID;
    }
}

// Context class
class AuthContext {
    private $strategy;

    public function __construct(AuthStrategy $strategy) {
        $this->strategy = $strategy;
    }

    public function authenticate($conn, $identifier, $password, $code = null): bool {
        return $this->strategy->authenticate($conn, $identifier, $password, $code);
    }

    public function getUserId(): ?int {
        return $this->strategy->getUserId();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifier = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $code = isset($_POST['code']) ? trim($_POST['code']) : null;

    if ($identifier === '' || $password === '') {
        $errors['login'] = "Please enter both username/email and password.";
    } else {
        if ($identifier === 'admin' && $password === 'admin') {
            $_SESSION['isAdmin'] = true;
            header('Location: admin/adminpanel.php');
            exit();
        }

        // Check if user has 2FA enabled
        $stmtCheck2FA = $conn->prepare("SELECT 2fa FROM users WHERE Email = ?");
        if (!$stmtCheck2FA) {
            die("Prepare failed: " . $conn->error);
        }

        $stmtCheck2FA->bind_param("s", $identifier);
        $stmtCheck2FA->execute();
        $resultCheck2FA = $stmtCheck2FA->get_result();
        $user2FAData = $resultCheck2FA->fetch_assoc();
        $stmtCheck2FA->close();

        if (!$user2FAData) {
            $errors['login'] = "Invalid username/email or password.";
        } else {
            $useTwoFactor = ((int)$user2FAData['2fa'] === 1);

            if ($useTwoFactor) {
                $auth = new AuthContext(new TwoFactorAuth());
            } else {
                $auth = new AuthContext(new PasswordOnlyAuth());
            }

            $authSuccess = $auth->authenticate($conn, $identifier, $password, $code);

            if ($authSuccess) {
                $_SESSION['UserID'] = $auth->getUserId();
                header("Location: dashboard.php");
                exit();
            } else {
                $errors['login'] = "Invalid username/email, password or 2FA code.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Eduor - Login</title>
    <style>
        body {
            background-color: #f0f4f8; /* Light gray-blue background */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .navbar {
            background-color: #ffffff;
            padding: 15px 50px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            text-decoration: none;
        }
        .navbar .links a {
            color: #555;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        .navbar .links a:hover {
            color: #2c3e50;
        }

        .main-content {
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 70px); /* Adjust height to account for navbar */
            padding: 20px;
        }
        
        .login-box {
            display: flex;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }

        .login-container {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .login-container h2 {
            margin-bottom: 25px;
            color: #333;
            font-size: 28px;
        }
        .login-container form {
            width: 100%;
        }
        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 14px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
        }
        .login-container button {
            width: 100%;
            padding: 14px;
            background-color: #34495e;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .login-container button:hover {
            background-color: #2c3e50;
        }
        .error {
            color: #e74c3c;
            margin-bottom: 15px;
            font-size: 14px;
        }
        .back-link {
            margin-top: 20px;
            display: block;
            font-size: 14px;
            color: #666;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }

        .image-container {
            flex: 1;
            background-color: #f0f4f8;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .image-container img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
        }
        @media (max-width: 768px) {
            .login-box {
                flex-direction: column;
            }
            .image-container {
                display: none; /* Hide image on smaller screens */
            }
        }
    </style>
</head>
<body>

<div class="navbar">
    <a href="#" class="logo">Eduor System</a>
    <div class="links">
        <a href="#">Home</a>
        <a href="#">About</a>
        <a href="#">Contact</a>
    </div>
</div>

<div class="main-content">
    <div class="login-box">
        <div class="login-container">
            <h2>Login</h2>

            <?php if (!empty($errors['login'])): ?>
                <div class="error"><?= htmlspecialchars($errors['login']) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="text" name="email" placeholder="Username or Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
                <input type="password" name="password" placeholder="Password" required />
                <button type="submit">Login</button>
            </form>

            <a class="back-link" href="index.php">← Back to Home</a>
        </div>
        <div class="image-container">
            <img src="resources/login.png" alt="Login Illustration" />
        </div>
    </div>
</div>

</body>
</html>