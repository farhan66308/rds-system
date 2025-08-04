<?php
require_once '../libs/GoogleAuthenticator.php';
$g = new PHPGangsta_GoogleAuthenticator();
session_start();
require_once '../conn.php';

$g = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();

$userID = $_SESSION['UserID'] ?? null;

if (!$userID) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT TwoFASecret FROM 2fa WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$secret = $row['TwoFASecret'];
$stmt->close();

$secret = $user['TwoFASecret'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code']);

    if ($g->checkCode($secret, $code)) {
        // 2FA success
        $_SESSION['2FA_Verified'] = true;
        header("Location: dashboard.php");  // Or wherever you want
        exit();
    } else {
        $error = "Invalid code. Try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>Verify 2FA</title></head>
<body>
    <h2>Enter your 6-digit 2FA code</h2>
    <?php if (!empty($error)): ?>
        <p style="color:red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="text" name="code" placeholder="123456" required>
        <button type="submit">Verify</button>
    </form>
</body>
</html>