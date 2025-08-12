<?php
class Password OnlyAuth implements AuthStrategy {
    private $conn;
    private $user;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function authenticate($username, $password, $code = null): bool {
        $stmt = $this->conn->prepare("SELECT UserID, Password FROM users WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['Password'])) {
                $_SESSION['UserID'] = $user['UserID'];
                return true;
            }
        }
        return false;
    }
}
// 2fa auth for the login

require_once 'libs/GoogleAuthenticator.php';

class TwoFactorAuth implements AuthStrategy {
    private $conn;
    private $gAuth;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->gAuth = new PHPGangsta_GoogleAuthenticator();
    }

    public function authenticate($username, $password, $code = null): bool {
        $stmt = $this->conn->prepare("SELECT UserID, Password FROM users WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['Password'])) {
                // Check if 2FA is enabled
                $userID = $user['UserID'];
                $stmt2 = $this->conn->prepare("SELECT TwoFASecret FROM 2fa WHERE UserID = ?");
                $stmt2->bind_param("i", $userID);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                $row2 = $res2->fetch_assoc();

                if ($row2 && $code !== null) {
                    // Validate 2FA code
                    if ($this->gAuth->checkCode($row2['TwoFASecret'], $code)) {
                        $_SESSION['UserID'] = $userID;
                        $_SESSION['2FA_Verified'] = true;
                        return true;
                    }
                }
            }
        }
        return false;
    }
}

?>