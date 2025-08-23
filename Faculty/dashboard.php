<?php
require_once '../conn.php';
$db = new Database();
$conn = $db->getConnection();
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

// Fetch FirstName and LastName from DB
$sql = "SELECT s.FirstName, s.LastName
        FROM users u
        JOIN studentinfo s ON s.UserID = u.UserID
        WHERE u.UserID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$fullname = ($row && ($row['FirstName'] || $row['LastName']))
    ? htmlspecialchars(trim($row['FirstName'] . ' ' . $row['LastName']))
    : 'User';

// --- START: Email Fetching Logic ---
$userEmail = 'N/A'; // Default value
$sql_email = "SELECT Email FROM users WHERE UserID = ?";
$stmt_email = $conn->prepare($sql_email);
if ($stmt_email) {
    $stmt_email->bind_param("i", $userID);
    $stmt_email->execute();
    $result_email = $stmt_email->get_result();
    $email_row = $result_email->fetch_assoc();
    if ($email_row) {
        $userEmail = htmlspecialchars($email_row['Email']);
    }
    $stmt_email->close();
}
// --- END: Email Fetching Logic ---

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eduor System | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../dash.css">
</head>

<body>

    <div class="navbar">
        <div class="navbar-left">
            <span class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></span>
            <img src="https://dummyimage.com/200x40/004080/ffffff&text=Eduor+System" alt="Eduor Logo" class="logo">
        </div>
    </div>

    <div class="sidebar" id="sidebar">
        <ul>
            <li class="active"><a href="#"><i class="fa fa-home"></i> Home</a></li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-check-circle"></i> Info</a>
                <ul class="submenu">
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../editprofile.php">Edit</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-book"></i> Courses</a>
                <ul class="submenu">
                    <li><a href="viewcourse.php">Manage</a></li>
                    <li><a href="takeattendance.php">Take Attendance</a></li>
                    <li><a href="publishgrades.php">Publish Grades</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-graduation-cap"></i> Learning</a>
                <ul class="submenu">
                    <li><a href="#">Course Modules</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-calendar"></i> Announcements</a>
                <ul class="submenu">
                    <li><a href="../create-announce.php">Create Annoucements</a></li>
                    <li><a href="#">Show Annoucement</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-envelope"></i> Support</a>
                <ul class="submenu">
                    <li><a href="createticket.php">Create Ticket</a></li>
                    <li><a href="trackticket.php">Track your support</a></li>
                </ul>
            </li>
            <li><a href="../settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php"><i class="fa fa-cog"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content" id="main-content">
        <div class="page-header">
            <h1>Faculty</h1>
            <p>UserID: <?= $_SESSION['UserID'] ?? "N/A"; ?></p>
        </div>

        <div class="profile-section">
            <div class="profile-left">
                <img src="https://rds3.northsouth.edu/assets/images/avatars/profile-pic.jpg" class="profile-pic">
                <h2><?= $_SESSION['UserName'] ?? "Guest"; ?></h2>
                <p>Email: <?= $userEmail; ?></p> <!-- Dynamically display the fetched email -->
            </div>
        </div>

        <div class="activity-status">
            <h3>Good day!</h3>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("active");
            document.getElementById("main-content").classList.toggle("shift");
        }
    </script>
</body>

</html>
