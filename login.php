<?php
require_once 'conn.php';
require_once 'libs/GoogleAuthenticator.php';

session_start();

$errors = [];
$db = new Database();
$conn = $db->getConnection();

// Interface for authentication strategy
interface AuthStrategy {
    public function authenticate($conn, $identifier, $password, $code = null): bool;
    public function getUserId(): ?int;
}

// Password-only authentication strategy
class PasswordOnlyAuth implements AuthStrategy {
    private $userID = null;

    public function authenticate($conn, $identifier, $password, $code = null): bool {
        $stmt = $conn->prepare("SELECT UserID, UserFlag, password FROM users WHERE Email = ? AND password = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $identifier, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ((int)$user['UserFlag'] === 0) {
                $stmt->close();
                return false; // user banned/blocked
            }
            $this->userID = $user['UserID'];
            $stmt->close();
            return true;
        }

        $stmt->close();
        return false;
    }

    public function getUserId(): ?int {
        return $this->userID;
    }
}

// Two-factor authentication strategy
class TwoFactorAuth implements AuthStrategy {
    private $userID = null;
    private $g;

    public function __construct() {
        $this->g = new PHPGangsta_GoogleAuthenticator();
    }

    public function authenticate($conn, $identifier, $password, $code = null): bool {
        // First verify username/password
        $stmt = $conn->prepare("SELECT UserID, UserFlag, password FROM users WHERE Email = ? AND password = ?");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $identifier, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if ((int)$user['UserFlag'] === 0) {
                $stmt->close();
                return false; // user banned/blocked
            }

            $userID = $user['UserID'];
            $stmt->close();

            // Fetch user's 2FA secret from 2fa table
            $stmt2 = $conn->prepare("SELECT TwoFASecret FROM 2fa WHERE UserID = ?");
            $stmt2->bind_param("i", $userID);
            $stmt2->execute();
            $result2 = $stmt2->get_result();
            $fa = $result2->fetch_assoc();
            $stmt2->close();

            if (!$fa) {
                // No 2FA setup yet
                $_SESSION['UserID'] = $userID;
                $_SESSION['TempSecret'] = $this->g->createSecret();
                header("Location: 2fa/setup-2fa.php");
                exit();
            } else {
                // 2FA is setup, check if code is provided
                if ($code === null) {
                    // Need to ask for code
                    return false; // we'll handle redirect below
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

// Context class for the loginauth (test phjase)
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $code = $_POST['code'] ?? null; // for 2FA code input if any

    if (empty($identifier) || empty($password)) {
        $errors['login'] = "Please enter both username/email and password.";
    } else {
        if ($identifier === 'admin' && $password === 'admin') {
            $_SESSION['isAdmin'] = true;
            header('Location: admin/adminpanel.php');
            exit();
        }

        // Determine if user has 2FA enabled
        $stmtCheck2FA = $conn->prepare("SELECT 2fa FROM users WHERE Email = ?");
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
                if ($useTwoFactor) {
                    // If 2FA was used, mark verified
                    $_SESSION['2FA_Verified'] = true;
                }
                header("Location: dashboard.php");
                exit();
            } else {
                if ($useTwoFactor && $code === null) {
                    // Show 2FA code form
                    $_SESSION['UserEmail'] = $identifier;
                    $_SESSION['UserPassword'] = $password;
                    header("Location: 2fa/verify-2fa.php"); // You can modify this page to accept POST login data or session
                    exit();
                }
                $errors['login'] = "Invalid username/email, password or 2FA code.";
            }
        }
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
            <input type="text" name="email" placeholder="Username or Email" required value="<?php echo htmlspecialchars($_POST['email'] ?? '') ?>">
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>

        <a class="back-link" href="index.php">← Back to Home</a>
    </div>

</body>

</html>