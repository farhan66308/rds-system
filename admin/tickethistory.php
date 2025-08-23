<?php
session_start();

require_once '../conn.php';
$db = new Database();
$conn = $db->getConnection();

// --- Admin Authentication Check ---
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php"); // Redirect to login if not authenticated
    exit();
}

$adminUserID = $_SESSION['UserID'];

// Verify if the logged-in user is an admin (UserFlag = 3)
$sql_check_admin = "SELECT UserFlag FROM users WHERE UserID = ?";
$stmt_check_admin = $conn->prepare($sql_check_admin);
if (!$stmt_check_admin) {
    die("Prepare failed: " . $conn->error);
}
$stmt_check_admin->bind_param("i", $adminUserID);
$stmt_check_admin->execute();
$result_check_admin = $stmt_check_admin->get_result();
$user_info = $result_check_admin->fetch_assoc();
$stmt_check_admin->close();


$fullname = 'User'; // Default name for the navbar
$message = '';
$message_type = ''; // 'success' or 'error'

$sql_user_name = "SELECT FirstName, LastName FROM studentinfo WHERE UserID = ?";
$stmt_user_name = $conn->prepare($sql_user_name);
if ($stmt_user_name) {
    $stmt_user_name->bind_param("i", $adminUserID);
    $stmt_user_name->execute();
    $result_user_name = $stmt_user_name->get_result();
    $row_name = $result_user_name->fetch_assoc();
    if ($row_name && ($row_name['FirstName'] || $row_name['LastName'])) {
        $fullname = htmlspecialchars(trim($row_name['FirstName'] . ' ' . $row_name['LastName']));
    }
    $stmt_user_name->close();
}

// --- Handle Ticket Status Update (AJAX Toggle) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_ticket_status') {
    $ticketID = $_POST['ticket_id'] ?? null;
    $newStatus = $_POST['new_status'] ?? null; // 'Solved' or 'Pending'

    if ($ticketID === null || $newStatus === null || !in_array($newStatus, ['Solved', 'Pending'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
        $conn->close();
        exit();
    }

    $sql_update_status = "UPDATE tickets SET Status = ? WHERE TicketID = ?";
    $stmt_update_status = $conn->prepare($sql_update_status);
    if ($stmt_update_status) {
        $stmt_update_status->bind_param("si", $newStatus, $ticketID);
        if ($stmt_update_status->execute()) {
            echo json_encode(['success' => true, 'message' => "Ticket ID " . htmlspecialchars($ticketID) . " status updated to " . htmlspecialchars($newStatus) . "."]);
        } else {
            echo json_encode(['success' => false, 'message' => "Error updating ticket status: " . $stmt_update_status->error]);
        }
        $stmt_update_status->close();
    } else {
        echo json_encode(['success' => false, 'message' => "Failed to prepare update statement: " . $conn->error]);
    }
    $conn->close();
    exit(); // Important: Exit after AJAX response
}


// --- Fetch All Tickets ---
$allTickets = [];
$sql_all_tickets = "SELECT TicketID, Description, FromUserID, Status, Feedback FROM tickets ORDER BY TicketID DESC";
$stmt_all_tickets = $conn->prepare($sql_all_tickets);
if ($stmt_all_tickets) {
    $stmt_all_tickets->execute();
    $result_all_tickets = $stmt_all_tickets->get_result();
    while ($row = $result_all_tickets->fetch_assoc()) {
        $allTickets[] = $row;
    }
    $stmt_all_tickets->close();
} else {
    $message = (empty($message) ? '' : $message . '<br>') . "Error fetching tickets: " . $conn->error;
    $message_type = (empty($message_type) ? 'error' : $message_type); // Set to error if not already
}

$conn->close(); // Ensure main connection is closed if not an AJAX request
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../dash.css">
    <style>
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

        .table-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 20px auto;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table-container th, .table-container td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            vertical-align: top;
        }

        .table-container th {
            background-color: #e9ecef;
            font-weight: bold;
            color: #495057;
        }

        .table-container tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .status-solved {
            color: #28a745; /* Green for solved */
            font-weight: bold;
        }

        .status-pending {
            color: #ffc107; /* Yellow/Orange for pending */
            font-weight: bold;
        }

        /* Styles for the AJAX message area */
        #ajaxMessageContainer {
            margin: 20px auto;
            max-width: 900px;
        }

        .ajax-message-success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            text-align: center;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }

        .ajax-message-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            text-align: center;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="navbar-left">
            <span class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></span>
            <img src="https://dummyimage.com/200x40/004080/ffffff&text=Eduor+System" alt="Eduor Logo" class="logo">
        </div>
        <div class="navbar-right">
            <h2><?= $fullname; ?></h2>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="admin.php"><i class="fa fa-home"></i> Dashboard</a></li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-cog"></i> Manage</a>
                <ul class="submenu">
                    <li><a href="managestudents.php">Students</a></li>
                    <li><a href="managefaculty.php">Faculty</a></li>
                    <li><a href="manageaccount.php">Account</a></li>
                </ul>
            </li>
            <li><a href="signup.php"><i class="fa fa-user-plus"></i> Sign Up</a></li>
            <li class="menu-item has-submenu open">
                <a href="#"><i class="fa fa-envelope"></i> Manage Tickets</a>
                <ul class="submenu" style="max-height: 200px;">
                    <li class="active"><a href="tickethistory.php">Ticket History</a></li> <!-- Active link for this page -->
                    <li><a href="pendingrequests.php">Pending Requests</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-book-open"></i> Courses</a>
                <ul class="submenu">
                    <li><a href="newcourse.php">Create Course</a></li>
                    <li><a href="createcourse.php">Create Structure</a></li>
                    <li><a href="viewcourselayout.php">View Structure</a></li>
                </ul>
            </li>
            <li><a href="../settings.php"><i class="fa fa-wrench"></i> Settings</a></li>
            <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <div class="page-header">
            <h1>Ticket History</h1>
            <p>Admin UserID: <?= $adminUserID; ?></p>
        </div>

        <?php if ($message): ?>
            <div class="message-<?= $message_type; ?>">
                <?= $message; ?>
            </div>
        <?php endif; ?>

        <div id="ajaxMessageContainer"></div> <!-- Container for AJAX messages -->

        <div class="table-container">
            <h2>All Support Tickets</h2>
            <?php if (!empty($allTickets)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Description</th>
                            <th>From User ID</th>
                            <th>Status</th>
                            <th>Feedback</th>
                            <th>Mark Solved</th> <!-- Updated header for the tick mark input -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allTickets as $ticket): ?>
                            <tr>
                                <td><?= htmlspecialchars($ticket['TicketID']); ?></td>
                                <td><?= nl2br(htmlspecialchars($ticket['Description'])); ?></td>
                                <td><?= htmlspecialchars($ticket['FromUserID']); ?></td>
                                <td>
                                    <?php if ($ticket['Status'] === 'Solved'): ?>
                                        <span class="status-solved"><i class="fa fa-check-circle"></i> Solved</span>
                                    <?php else: ?>
                                        <span class="status-pending"><i class="fa fa-clock"></i> Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= nl2br(htmlspecialchars($ticket['Feedback'] ?? 'N/A')); ?></td>
                                <td>
                                    <label class="switch">
                                        <input type="checkbox" 
                                               id="ticket-<?= htmlspecialchars($ticket['TicketID']); ?>" 
                                               data-ticket-id="<?= htmlspecialchars($ticket['TicketID']); ?>" 
                                               onchange="toggleTicketStatus(this)"
                                               <?= ($ticket['Status'] === 'Solved') ? 'checked' : ''; ?>>
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No tickets found in the system.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("active");
            document.getElementById("main-content").classList.toggle("shift");
        }

        // Function to display AJAX messages
        function displayAjaxMessage(message, type) {
            const container = document.getElementById('ajaxMessageContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = `ajax-message-${type}`;
            messageDiv.textContent = message;
            container.innerHTML = ''; // Clear previous messages
            container.appendChild(messageDiv);

            // Animate visibility
            setTimeout(() => {
                messageDiv.style.opacity = '1';
            }, 50);

            // Automatically hide after 5 seconds
            setTimeout(() => {
                messageDiv.style.opacity = '0';
                messageDiv.addEventListener('transitionend', () => messageDiv.remove());
            }, 5000);
        }

        // Script to handle ticket status toggle via AJAX
        async function toggleTicketStatus(checkboxElement) {
            const ticketId = checkboxElement.dataset.ticketId;
            const newStatus = checkboxElement.checked ? 'Solved' : 'Pending';

            try {
                const response = await fetch('tickethistory.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_ticket_status&ticket_id=${encodeURIComponent(ticketId)}&new_status=${encodeURIComponent(newStatus)}`
                });
                const data = await response.json();

                if (data.success) {
                    displayAjaxMessage(data.message, 'success');
                    // Optionally update the status text next to the checkbox immediately
                    const statusSpan = checkboxElement.closest('tr').querySelector('.status-solved, .status-pending');
                    if (statusSpan) {
                        if (newStatus === 'Solved') {
                            statusSpan.className = 'status-solved';
                            statusSpan.innerHTML = '<i class="fa fa-check-circle"></i> Solved';
                        } else {
                            statusSpan.className = 'status-pending';
                            statusSpan.innerHTML = '<i class="fa fa-clock"></i> Pending';
                        }
                    }
                } else {
                    displayAjaxMessage(data.message, 'error');
                    // Revert checkbox state if update failed
                    checkboxElement.checked = !checkboxElement.checked; 
                }
            } catch (error) {
                console.error('Error toggling ticket status:', error);
                displayAjaxMessage('An unexpected error occurred while updating the ticket.', 'error');
                // Revert checkbox state on network error
                checkboxElement.checked = !checkboxElement.checked;
            }
        }

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
    <style>
        /* Custom Toggle Switch (Tick Mark) CSS */
        .switch {
          position: relative;
          display: inline-block;
          width: 40px;
          height: 24px;
        }

        .switch input {
          opacity: 0;
          width: 0;
          height: 0;
        }

        .slider {
          position: absolute;
          cursor: pointer;
          top: 0;
          left: 0;
          right: 0;
          bottom: 0;
          background-color: #ccc;
          -webkit-transition: .4s;
          transition: .4s;
        }

        .slider:before {
          position: absolute;
          content: "";
          height: 16px;
          width: 16px;
          left: 4px;
          bottom: 4px;
          background-color: white;
          -webkit-transition: .4s;
          transition: .4s;
        }

        input:checked + .slider {
          background-color: #2196F3;
        }

        input:focus + .slider {
          box-shadow: 0 0 1px #2196F3;
        }

        input:checked + .slider:before {
          -webkit-transform: translateX(16px);
          -ms-transform: translateX(16px);
          transform: translateX(16px);
        }

        /* Rounded sliders */
        .slider.round {
          border-radius: 24px;
        }

        .slider.round:before {
          border-radius: 50%;
        }
    </style>
</body>
</html>
