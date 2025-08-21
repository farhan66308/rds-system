<?php
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}
else {
    $userID = $_SESSION['UserID'];
    header("dash.php");
    exit();
}
?>