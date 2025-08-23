<?php
session_start();

//http://localhost/RDS/admin/SeeFaculty.php?UserID=2022
$error = null;

$_SESSION['UserID'] = 1; 

require_once '..\conn.php'; // Adjust path as necessary

// Check if a faculty UserID is provided in the URL
if (!isset($_GET['UserID']) || !is_numeric($_GET['UserID'])) {
    // header("Location: viewcourse.php");
    // exit();
    $facultyUserID = null; // Set to null to trigger error handling later
    $error = "No faculty member ID provided or the ID is invalid.";
} else {
    $facultyUserID = $_GET['UserID'];
}


$db = new Database();
$conn = $db->getConnection();

$faculty = null;
$fields = [];
// $error is initialized above if UserID is missing/invalid, otherwise it's null

try {
    if ($facultyUserID !== null) { // Only proceed if a valid faculty UserID was provided
        // 1. Fetch faculty information and verify the UserFlag
        // Joining with users table to ensure only UserFlag = 2 (Faculty) are displayed
        $sql_faculty = "SELECT fi.FacultyCode, fi.UserID, fi.Name, fi.Avatar, fi.Bio, fi.Department, fi.Role, fi.Office, fi.Website, fi.EducationInfo, fi.SkillSet 
                        FROM facultyinfo fi
                        JOIN users u ON fi.UserID = u.UserID
                        WHERE fi.UserID = ? AND u.UserFlag = 2";
        $stmt_faculty = $conn->prepare($sql_faculty);
        if (!$stmt_faculty) {
            throw new Exception("Failed to prepare facultyinfo statement: " . $conn->error);
        }
        $stmt_faculty->bind_param("i", $facultyUserID);
        $stmt_faculty->execute();
        $result_faculty = $stmt_faculty->get_result();
        if ($result_faculty->num_rows > 0) {
            $faculty = $result_faculty->fetch_assoc();
        } else {
            $error = "Faculty member not found or is not a faculty user.";
        }
        $stmt_faculty->close();

        // 2. Fetch fields of study for the faculty member
        if ($faculty) {
            $sql_fields = "SELECT FieldName, FieldSubTitle, Date, SetField1, SetField2, SetField3, Description 
                           FROM field 
                           WHERE UserID = ? 
                           ORDER BY Date DESC";
            $stmt_fields = $conn->prepare($sql_fields);
            if (!$stmt_fields) {
                throw new Exception("Failed to prepare field statement: " . $conn->error);
            }
            $stmt_fields->bind_param("i", $facultyUserID);
            $stmt_fields->execute();
            $result_fields = $stmt_fields->get_result();
            while ($row_field = $result_fields->fetch_assoc()) {
                $fields[] = $row_field;
            }
            $stmt_fields->close();
            // Attach fields directly to the faculty array for easier access in HTML
            $faculty['Fields'] = $fields; 
        }
    }

} catch (Exception $e) {
    error_log("Error in SeeFaculty.php: " . $e->getMessage());
    $error = "An error occurred: " . $e->getMessage();
}

$conn->close();

// Fetching user's full name for the navbar/sidebar (for the *currently logged-in* dummy user)
$fullname = 'User';
if (isset($_SESSION['UserID'])) {
    $db_user = new Database();
    $conn_user = $db_user->getConnection();
    $sql_user = "SELECT FirstName, LastName FROM studentinfo WHERE UserID = ?";
    $stmt_user = $conn_user->prepare($sql_user);
    if ($stmt_user) {
        $stmt_user->bind_param("i", $_SESSION['UserID']);
        $stmt_user->execute();
        $result_user = $stmt_user->get_result();
        $row_user = $result_user->fetch_assoc();
        if ($row_user) {
            $fullname = htmlspecialchars(trim($row_user['FirstName'] . ' ' . $row_user['LastName']));
        }
        $stmt_user->close();
    }
    $conn_user->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Profile: <?php echo htmlspecialchars($faculty['Name'] ?? 'Not Found'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../dash.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f4f7f6;
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
            top: 60px;
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
        .sidebar ul li a i {
            margin-right: 10px;
            font-size: 18px;
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
            max-height: 200px;
        }
        .sidebar .submenu li a {
            padding: 8px 20px;
            font-size: 0.9em;
        }
        .main-content {
            margin-left: 0;
            padding: 80px 20px 20px 20px;
            transition: margin-left 0.3s ease;
            min-height: calc(100vh - 80px);
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
        .profile-section {
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .profile-section h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .profile-section p {
            color: #666;
            margin-bottom: 5px;
        }
        .card {
            background-color: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-top: 20px;
        }
        .card h2 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .badge {
            display: inline-block;
            background-color: #334155;
            color: white;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 14px;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        .avatar img {
            border-radius: 50%;
            width: 128px;
            height: 128px;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .profile-info {
            margin-left: 20px;
        }
        .profile-info h1 {
            font-size: 3rem;
            font-weight: bold;
            color: #1a202c;
        }
        .profile-info p {
            color: #4a5568;
            margin-top: 5px;
        }
        .field-item {
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        .field-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .field-title {
            font-weight: bold;
            font-size: 1.25rem;
            color: #2d3748;
        }
        .field-subtitle {
            font-style: italic;
            color: #4a5568;
            margin-top: 5px;
        }
        .field-description {
            margin-top: 10px;
            color: #2d3748;
        }
        .error-message {
            color: #e74c3c;
            background-color: #fdeded;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e74c3c;
            margin-top: 20px;
            text-align: center;
            font-size: 1.1em;
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
            <li><a href="dashboard.php"><i class="fa fa-home"></i> Dashboard</a></li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-cog"></i> Manage</a>
                <ul class="submenu">
                    <li><a href="managestudents.php">Students</a></li>
                    <li><a href="managefaculty.php">Faculty</a></li> 
                    <li><a href="manageaccount.php">Account</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-scroll"></i> Logs</a>
                <ul class="submenu">
                    <li><a href="checklog.php">Check Log</a></li>
                </ul>
            </li>
            <li><a href="signup.php"><i class="fa fa-user-plus"></i> Sign Up</a></li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-ticket-alt"></i> Manage Tickets</a>
                <ul class="submenu">
                    <li><a href="tickethistory.php">Ticket History</a></li>
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
    <main class="main-content" id="main-content">
        <div class="page-header">
            <h1>Faculty Profile</h1>
        </div>

        <div class="profile-section">
            <?php if ($error): ?>
                <div class="error-message">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php elseif ($faculty): ?>
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="avatar">
                        <img src="<?= htmlspecialchars($faculty['Avatar'] ?? 'https://via.placeholder.com/150'); ?>" alt="Faculty Avatar" />
                    </div>
                    <div class="profile-info">
                        <h1><?= htmlspecialchars($faculty['Name']); ?></h1>
                        <p style="font-size: 1.2rem; color: #4b5563; font-weight: 500;"><?= htmlspecialchars($faculty['Role'] ?? 'Faculty'); ?></p>
                        <p style="font-size: 1rem; color: #6b7280;"><?= htmlspecialchars($faculty['Department'] ?? 'N/A'); ?></p>
                        <a href="<?= htmlspecialchars($faculty['Website'] ?? '#'); ?>" target="_blank" style="color: #3b82f6; text-decoration: underline;">
                            <?= htmlspecialchars($faculty['Website'] ?? 'Website'); ?>
                        </a>
                    </div>
                </div>

                <!-- Profile Sections -->
                <div class="space-y-8">
                    <!-- Bio Section -->
                    <div class="card">
                        <h2>Bio</h2>
                        <p><?= nl2br(htmlspecialchars($faculty['Bio'] ?? 'No bio provided.')); ?></p>
                    </div>

                    <!-- Education Section -->
                    <div class="card">
                        <h2>Education</h2>
                        <p><?= nl2br(htmlspecialchars($faculty['EducationInfo'] ?? 'No education information provided.')); ?></p>
                    </div>
                    
                    <!-- Skills Section -->
                    <div class="card">
                        <h2>Skills</h2>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                            <?php
                            $skills = explode(',', $faculty['SkillSet'] ?? '');
                            foreach ($skills as $skill) {
                                $skill = trim($skill);
                                if (!empty($skill)) {
                                    echo '<span class="badge">' . htmlspecialchars($skill) . '</span>';
                                }
                            }
                            ?>
                            <?php if (empty($skills[0])) echo '<p style="color: #6b7280;">No skills listed.</p>'; ?>
                        </div>
                    </div>

                    <!-- Fields of Study Section -->
                    <div class="card">
                        <h2>Fields of Study</h2>
                        <?php if (!empty($faculty['Fields'])): ?>
                            <ul style="list-style: none; padding: 0;">
                                <?php foreach ($faculty['Fields'] as $field): ?>
                                    <li class="field-item">
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <h3 class="field-title"><?= htmlspecialchars($field['FieldName']); ?></h3>
                                            <span style="font-size: 0.875rem; color: #6b7280;"><?= htmlspecialchars($field['Date']); ?></span>
                                        </div>
                                        <p class="field-subtitle"><?= htmlspecialchars($field['FieldSubTitle']); ?></p>
                                        <p class="field-description"><?= nl2br(htmlspecialchars($field['Description'])); ?></p>
                                        <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-top: 8px;">
                                            <?php if (!empty($field['SetField1'])) echo '<span class="badge">' . htmlspecialchars($field['SetField1']) . '</span>'; ?>
                                            <?php if (!empty($field['SetField2'])) echo '<span class="badge">' . htmlspecialchars($field['SetField2']) . '</span>'; ?>
                                            <?php if (!empty($field['SetField3'])) echo '<span class="badge">' . htmlspecialchars($field['SetField3']) . '</span>'; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="color: #6b7280;">No fields of study have been added yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("active");
            document.getElementById("main-content").classList.toggle("shift");
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

        // Ensure current active menu item is highlighted on load
        document.addEventListener('DOMContentLoaded', () => {
            const currentPath = window.location.pathname.split('/').pop();
            document.querySelectorAll('.sidebar ul li a').forEach(link => {
                if (link.getAttribute('href') && link.getAttribute('href').endsWith(currentPath)) {
                    link.closest('li').classList.add('active');
                    // For submenus, also open the parent
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
