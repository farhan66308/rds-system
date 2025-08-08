<?php
require_once 'conn.php';
$db = new Database();
$conn = $db->getConnection();
session_start();

// Block access if not logged in + 2FA verified
if (!isset($_SESSION['UserID']) || !isset($_SESSION['2FA_Verified'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

// Handle form submissions
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle password reset
    if (isset($_POST['reset_password'])) {
        $newPassword = trim($_POST['new_password']);
        $confirmPassword = trim($_POST['confirm_password']);

        if ($newPassword === '' || $confirmPassword === '') {
            $message = "Please fill in all password fields.";
        } elseif ($newPassword !== $confirmPassword) {
            $message = "Passwords do not match.";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET Password = ? WHERE UserID = ?");
            $stmt->bind_param("si", $hashedPassword, $userID);
            if ($stmt->execute()) {
                $message = "Password successfully updated.";
            } else {
                $message = "Failed to update password.";
            }
            $stmt->close();
        }
    }

    // Handle 2FA toggle
    if (isset($_POST['toggle_2fa'])) {
        $enable2FA = ($_POST['enable_2fa'] == '1') ? 1 : 0;
        $stmt = $conn->prepare("UPDATE users SET 2fa = ? WHERE UserID = ?");
        $stmt->bind_param("ii", $enable2FA, $userID);
        if ($stmt->execute()) {
            $message = $enable2FA ? "2FA enabled." : "2FA disabled.";

            // Optional: if disabling 2FA, also delete from 2fa table (removes secret)
            if (!$enable2FA) {
                $stmtDel = $conn->prepare("DELETE FROM 2fa WHERE UserID = ?");
                $stmtDel->bind_param("i", $userID);
                $stmtDel->execute();
                $stmtDel->close();
            }
        } else {
            $message = "Failed to update 2FA status.";
        }
        $stmt->close();
    }
}

// Fetch user info and 2FA status
$stmt = $conn->prepare("SELECT 2fa FROM users WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$twoFAEnabled = (int)$user['2fa'] === 1;

// Check if 2FA setup exists in 2fa table
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM 2fa WHERE UserID = ?");
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$twoFASetupExists = ($row['cnt'] > 0);

?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Settings - Eduor System</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 to-yellow-200">
  <!-- Navbar -->
  <section>
    <div class="navbar bg-base-100 h-[100px] bg-slate-300 m-3 rounded-lg shadow-md">
      <div class="navbar-start">
        <div class="dropdown">
          <div tabindex="0" role="button" class="btn btn-ghost btn-circle">
            <i class="fas fa-bars text-xl"></i>
          </div>
          <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52">
            <li><a href="dashboard.php">Home</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="settings.php">Settings</a></li>
          </ul>
        </div>
      </div>
      <div class="navbar-center">
        <a class="btn btn-ghost text-2xl font-bold">Eduor System</a>
      </div>
      <div class="navbar-end">
        <button class="btn btn-error text-white mr-4" onclick="logout()">Logout</button>
      </div>
    </div>
  </section>

  <main class="m-5 max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
    <h1 class="text-2xl font-bold mb-6">Settings</h1>

    <?php if ($message): ?>
      <div class="alert alert-info mb-4">
        <?php echo htmlspecialchars($message); ?>
      </div>
    <?php endif; ?>

    <!-- Password Reset Form -->
    <form method="POST" class="mb-8">
      <h2 class="text-xl font-semibold mb-3">Reset Password</h2>
      <input type="password" name="new_password" placeholder="New Password" class="input input-bordered w-full mb-3" required />
      <input type="password" name="confirm_password" placeholder="Confirm Password" class="input input-bordered w-full mb-3" required />
      <button type="submit" name="reset_password" class="btn btn-primary w-full">Update Password</button>
    </form>

    <!-- 2FA Settings -->
    <form method="POST" class="mb-4">
      <h2 class="text-xl font-semibold mb-3">Two-Factor Authentication (2FA)</h2>
      <label class="flex items-center space-x-3">
        <input type="radio" name="enable_2fa" value="1" <?php if($twoFAEnabled) echo 'checked'; ?> />
        <span>Enable 2FA</span>
      </label>
      <label class="flex items-center space-x-3">
        <input type="radio" name="enable_2fa" value="0" <?php if(!$twoFAEnabled) echo 'checked'; ?> />
        <span>Disable 2FA</span>
      </label>

      <?php if ($twoFAEnabled && !$twoFASetupExists): ?>
        <p class="mt-3 text-sm text-yellow-600">
          You have enabled 2FA but have not set it up yet.
          <a href="2fa/setup-2fa.php" class="link link-primary">Click here to set up 2FA</a>.
        </p>
      <?php endif; ?>

      <button type="submit" name="toggle_2fa" class="btn btn-secondary mt-4">Save 2FA Settings</button>
    </form>
  </main>

  <script>
    function logout() {
      fetch('logout.php').then(() => {
        window.location.href = "login.php";
      });
    }
  </script>
</body>
</html>
