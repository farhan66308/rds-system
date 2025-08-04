<?php
require_once '../conn.php';
require_once '../libs/GoogleAuthenticator.php';
$g = new PHPGangsta_GoogleAuthenticator();
$db = new Database(); 
$conn = $db->getConnection();
session_start();

if (!isset($_SESSION['TempSecret'])) {
    header("Location: login.php");
    exit();
}


$secret = $_SESSION['TempSecret'];
$qrCodeUrl = $g->getQRCodeGoogleUrl('StudentPortal:User' . $_SESSION['UserID'], $secret);

$secret = $_SESSION['TempSecret'];
$userID = $_SESSION['UserID'];

// Save to `2fa` table
$stmt = $conn->prepare("INSERT INTO 2fa (UserID, TwoFASecret) VALUES (?, ?)");
$stmt->bind_param("is", $userID, $secret);
$stmt->execute();
$stmt->close();

?>

<!DOCTYPE html>
<html>
<head><title>2FA Setup</title></head>
<body>
    <h2>Scan this QR code with Google Authenticator:</h2>
    <img src="https://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=<?php echo urlencode($qrCodeUrl); ?>" />
    <form method="post" action="verify-2fa.php">
        <input type="text" name="code" placeholder="Enter 6-digit code">
        <button type="submit">Verify</button>
    </form>
</body>
</html>