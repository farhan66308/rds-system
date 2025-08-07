<?php
session_start();
require_once '../conn.php';
require_once '../libs/GoogleAuthenticator.php';
$g = new PHPGangsta_GoogleAuthenticator();

$userID = $_SESSION['UserID'] ?? null;

if (!$userID) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Check if 2FA secret already exists
$stmt = $conn->prepare("SELECT TwoFASecret FROM 2fa WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ($row && !empty($row['TwoFASecret'])) {
    // Secret already exists, show QR again
    $secret = $row['TwoFASecret'];
} else {
    // Generate new secret and insert into DB
    $secret = $g->createSecret();

    $stmt = $conn->prepare("INSERT INTO 2fa (UserID, TwoFASecret) VALUES (?, ?)");
    $stmt->bind_param("is", $userID, $secret);
    $stmt->execute();
    $stmt->close();
}

// Generate QR Code URL for Google Authenticator
$appName = "YourAppName"; // Change this to your app/site name
$qrCodeUrl = $g->getQRCodeGoogleUrl($appName, $secret);

// Optional: manual code display
$manualCode = $secret;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Setup 2FA</title>
</head>
<body>
    <h2>Set Up Two-Factor Authentication</h2>
    <p>Scan the QR code below with your <strong>Google Authenticator</strong> app:</p>
    <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="QR Code"><br><br>

    <p>If you can't scan, enter this secret manually into your app:</p>
    <code><?php echo htmlspecialchars($manualCode); ?></code><br><br>

    <form method="post" action="verify-2fa.php">
        <button type="submit">Continue to Verify</button>
    </form>
</body>
</html>
