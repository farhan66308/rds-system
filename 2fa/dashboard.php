<?php
session_start();
if (!isset($_SESSION['UserID']) || !isset($_SESSION['2FA_Verified'])) {
    header("Location: login.php");
    exit();
}
?>
<h1>Welcome to your dashboard</h1>
