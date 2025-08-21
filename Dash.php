<?php
require_once 'conn.php';
$db = new Database();
$conn = $db->getConnection();
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

// Fetch FirstName, LastName, and Email from DB
$sql = "SELECT s.FirstName, s.LastName, u.Email
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

$userEmail = $row['Email'] ?? 'N/A';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eduor System | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dash.css">
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
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="#">Edit</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-book"></i> Courses</a>
                <ul class="submenu">
                    <li><a href="#">Manage</a></li>
                    <li><a href="#">Advising</a></li>
                    <li><a href="#">Grade History</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-graduation-cap"></i> Learning</a>
                <ul class="submenu">
                    <li><a href="#">Courses</a></li>
                    <li><a href="#">Payment History</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-calendar"></i> Announcements</a>
                <ul class="submenu">
                    <li><a href="#">Create Announcements</a></li>
                    <li><a href="#">Show Announcement</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-money-bill"></i> Transactions</a>
                <ul class="submenu">
                    <li><a href="#">Bill</a></li>
                    <li><a href="#">Payment History</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-envelope"></i> Support</a>
                <ul class="submenu">
                    <li><a href="#">Create Ticket</a></li>
                    <li><a href="#">Track your support</a></li>
                </ul>
            </li>
            <li><a href="#"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="#"><i class="fa fa-power-off"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content" id="main-content">
        <div class="page-header">
            <h1>Welcome, <?= $fullname; ?></h1>
            <p>UserID: <?= $_SESSION['UserID'] ?? "N/A"; ?></p>
        </div>

        <div class="profile-section">
            <div class="profile-left">
                <img src="https://rds3.northsouth.edu/assets/images/avatars/profile-pic.jpg" class="profile-pic">
                <h2><?= $fullname; ?></h2>
                <p>Email: <?= $userEmail; ?></p>
                <p>Program: BS in Computer Science</p>
                <p>Curriculum: 140 Credit</p>
            </div>
        </div>

        <div class="activity-status">
            <h3>Activity Status</h3>
            <table>
                <thead>
                    <tr>
                        <th>Activity</th>
                        <th>Semester</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Faculty Evaluation</td>
                        <td>Fall 2025</td>
                        <td class="done">Done</td>
                    </tr>
                    <tr>
                        <td>Preadvising</td>
                        <td>Fall 2025</td>
                        <td class="not-done">Not Done</td>
                    </tr>
                    <tr>
                        <td>Payment</td>
                        <td>Summer 2025</td>
                        <td><button class="btn-view">View</button></td>
                    </tr>
                </tbody>
            </table>
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