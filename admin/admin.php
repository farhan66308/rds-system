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

// Initialize variables for fullname, email, tickets solved, and pending tickets
$fullname = 'User';
$userEmail = 'N/A';
$ticketsSolvedCount = 0;
$pendingTickets = [];
$errorMessage = '';

try {
    // Fetch FirstName and LastName from studentinfo for the navbar and profile section
    $sql_name = "SELECT s.FirstName, s.LastName
                 FROM studentinfo s
                 WHERE s.UserID = ?";
    $stmt_name = $conn->prepare($sql_name);
    if ($stmt_name) {
        $stmt_name->bind_param("i", $userID);
        $stmt_name->execute();
        $result_name = $stmt_name->get_result();
        $row_name = $result_name->fetch_assoc();
        if ($row_name && ($row_name['FirstName'] || $row_name['LastName'])) {
            $fullname = htmlspecialchars(trim($row_name['FirstName'] . ' ' . $row_name['LastName']));
        }
        $stmt_name->close();
    } else {
        $errorMessage .= "Error preparing name fetch: " . $conn->error . "<br>";
    }

    // Fetch user's email from the 'users' table
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
    } else {
        $errorMessage .= "Error preparing email fetch: " . $conn->error . "<br>";
    }

    // Fetch total solved tickets for the current user (if applicable for admin)
    // Counting ALL closed tickets.
    $sql_solved_tickets = "SELECT COUNT(*) AS solved_count FROM tickets WHERE Status = 'Closed'";
    $stmt_solved_tickets = $conn->prepare($sql_solved_tickets);
    if ($stmt_solved_tickets) {
        $stmt_solved_tickets->execute();
        $result_solved_tickets = $stmt_solved_tickets->get_result();
        $solved_row = $result_solved_tickets->fetch_assoc();
        if ($solved_row) {
            $ticketsSolvedCount = $solved_row['solved_count'];
        }
        $stmt_solved_tickets->close();
    } else {
        $errorMessage .= "Error preparing solved tickets fetch: " . $conn->error . "<br>";
    }

    // Fetch pending tickets for the Alerts table
    // Updated SELECT statement to match the provided 'tickets' table schema: (TicketID, Description, FromUserID, Status, Feedback)
    $sql_pending_tickets = "SELECT TicketID, Description 
                            FROM tickets 
                            WHERE Status = 'Pending' 
                            ORDER BY TicketID DESC LIMIT 10"; // Order by TicketID or another relevant column if 'DateSubmitted' is missing
    $stmt_pending_tickets = $conn->prepare($sql_pending_tickets);
    if ($stmt_pending_tickets) {
        $stmt_pending_tickets->execute();
        $result_pending_tickets = $stmt_pending_tickets->get_result();
        while ($row_ticket = $result_pending_tickets->fetch_assoc()) {
            $pendingTickets[] = $row_ticket;
        }
        $stmt_pending_tickets->close();
    } else {
        $errorMessage .= "Error preparing pending tickets fetch: " . $conn->error . "<br>";
    }

} catch (Exception $e) {
    error_log("Error in admin.php: " . $e->getMessage());
    $errorMessage .= "An unexpected error occurred: " . $e->getMessage();
} finally {
    $conn->close(); // Ensure connection is closed
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eduor System | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../dash.css">
    <style>
        /* General Layout */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f4f7f6;
            color: #333;
        }

        .navbar {
            background-color: #333;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .navbar-left {
            display: flex;
            align-items: center;
        }

        .navbar-left .menu-btn {
            font-size: 24px;
            margin-right: 20px;
            cursor: pointer;
        }

        .navbar .logo {
            height: 40px;
            margin-left: 10px;
        }

        /* Added style for navbar-right h2 to match other dashboards */
        .navbar-right h2 {
            font-size: 1.2rem;
            margin: 0;
            color: white;
        }

        .sidebar {
            width: 200px;
            background-color: #2c3e50;
            color: white;
            position: fixed;
            top: 60px; /* Below the navbar */
            left: -200px;
            height: calc(100% - 60px);
            padding-top: 20px;
            transition: left 0.3s ease;
            z-index: 999;
            box-shadow: 2px 0 5px rgba(0,0,0,0.2);
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin-bottom: 5px;
        }

        .sidebar ul li a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
        }

        .sidebar ul li a:hover, .sidebar ul li.active a {
            background-color: #34495e;
        }

        .sidebar .submenu {
            list-style: none;
            padding-left: 30px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            background-color: #3c526a;
        }

        .sidebar .menu-item.has-submenu.open .submenu {
            max-height: 200px; /* Adjust as needed */
        }

        .sidebar .submenu li a {
            padding: 8px 20px;
            font-size: 0.9em;
        }

        .main-content {
            margin-left: 0;
            padding: 80px 20px 20px 20px; /* Adjust padding-top to account for navbar */
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 80px); /* Adjust height for navbar */
        }

        .main-content.shift {
            margin-left: 200px;
        }

        /* Page-specific styles */
        .page-header {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .page-header h1 {
            margin: 0;
            font-size: 28px;
            color: #333;
        }

        .profile-section {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }

        .profile-pic {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
        }

        .profile-left h2 {
            margin: 0 0 5px 0;
            font-size: 24px;
            color: #2c3e50;
        }

        .profile-left p {
            margin: 0 0 5px 0;
            color: #555;
        }

        .activity-status {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .activity-status h3 {
            margin-top: 0;
            font-size: 22px;
            color: #333;
            margin-bottom: 15px;
        }

        .activity-status table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .activity-status th, .activity-status td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .activity-status th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .activity-status tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .alert-message {
            background-color: #ffe6e6;
            color: #cc0000;
            padding: 10px;
            border: 1px solid #cc0000;
            border-radius: 5px;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>

    <div class="navbar">
        <div class="navbar-left">
            <span class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></span>
            <img src="https://dummyimage.com/200x40/004080/ffffff&text=Eduor+System" alt="Eduor Logo" class="logo">
        </div>
        <div class="navbar-right"> <!-- Changed from profile-section to navbar-right for consistency -->
            <h2><?= $fullname; ?></h2>
        </div>
    </div>

    <div class="sidebar" id="sidebar">
        <ul>
            <li class="active"><a href="#"><i class="fa fa-home"></i> Dashboard</a></li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-cog"></i> Manage</a>
                <ul class="submenu">
                    <li><a href="managestudent.php">Students</a></li>
                    <li><a href="SeeFaculty.php">Faculty</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <ul class="submenu">
                    <li><a href="#">Check Log</a></li>
                </ul>
            </li>
            <li><a href="signup.php"><i class="fa fa-plus-circle"></i> Sign Up</a></li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-envelope"></i> Manage Tickets</a>
                <ul class="submenu">
                    <li><a href="tickethistory.php">Ticket History</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-book-open"></i> Courses</a> <!-- Changed icon for courses -->
                <ul class="submenu">
                    <li><a href="newcourse.php">Create Course</a></li>
                    <li><a href="createcourse.php">Create Structure</a></li>
                    <li><a href="viewcourselayout.php">View Structure</a></li>
                </ul>
            </li>
            <li><a href="../settings.php"><i class="fa fa-wrench"></i> Settings</a></li> <!-- Changed icon for settings -->
            <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li> <!-- Changed icon for logout -->
        </ul>
    </div>

    <div class="main-content" id="main-content">
        <div class="page-header">
            <h1>Admin Panel</h1>
            <p>UserID: <?= $_SESSION['UserID'] ?? "N/A"; ?></p>
        </div>

        <?php if ($errorMessage): ?>
            <div class="alert-message">
                <?= $errorMessage; ?>
            </div>
        <?php endif; ?>

        <div class="profile-section">
            <div class="profile-left">
                <img src="https://rds3.northsouth.edu/assets/images/avatars/profile-pic.jpg" class="profile-pic">
                <h2><?= $fullname; ?></h2>
                <p>Email: <?= $userEmail; ?></p>
                <p>Clock: <span id="real-time-clock"></span></p>
            </div>
        </div>

        <div class="activity-status">
            <h3>Alerts (Pending Tickets)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pendingTickets)): ?>
                        <?php foreach ($pendingTickets as $ticket): ?>
                            <tr>
                                <td><?= htmlspecialchars($ticket['Description']); ?></td>
                                <td>N/A</td> 
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2">No pending tickets found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("active");
            document.getElementById("main-content").classList.toggle("shift");
        }

        function updateClock() {
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const timeString = `${hours}:${minutes}:${seconds}`;

            document.getElementById('real-time-clock').textContent = timeString;
        }

        updateClock();
        setInterval(updateClock, 1000);

        // Script to handle submenu toggle
        document.querySelectorAll('.sidebar .has-submenu > a').forEach(item => {
            item.addEventListener('click', function(event) {
                event.preventDefault();
                const parentLi = this.closest('.has-submenu');
                parentLi.classList.toggle('open');
                const submenu = parentLi.querySelector('.submenu');
                if (submenu) {
                    if (parentLi.classList.contains('open')) {
                        submenu.style.maxHeight = submenu.scrollHeight + "px";
                    } else {
                        submenu.style.maxHeight = null;
                    }
                }
            });
        });

        // Highlight active menu item on load
        document.addEventListener('DOMContentLoaded', () => {
            const currentPath = window.location.pathname.split('/').pop();
            document.querySelectorAll('.sidebar ul li a').forEach(link => {
                if (link.getAttribute('href') && link.getAttribute('href').endsWith(currentPath)) {
                    link.closest('li').classList.add('active');
                    const parentSubmenu = link.closest('.submenu');
                    if (parentSubmenu) {
                        const parentMenuItem = parentSubmenu.closest('.has-submenu');
                        if (parentMenuItem) {
                            parentMenuItem.classList.add('open');
                            parentSubmenu.style.maxHeight = parentSubmenu.scrollHeight + "px";
                        }
                    }
                }
            });
        });
    </script>
</body>

</html>
