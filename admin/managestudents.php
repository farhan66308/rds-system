<?php
session_start();

require_once '../conn.php';
$db = new Database();
$conn = $db->getConnection();

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


$fullname = 'User'; 
$message = '';
$message_type = ''; // 'success' or 'error'
$selectedStudent = null; 

// Fetch admin's full name for the navbar/sidebar
$sql_admin_name = "SELECT FirstName, LastName FROM studentinfo WHERE UserID = ?";
$stmt_admin_name = $conn->prepare($sql_admin_name);
if ($stmt_admin_name) {
    $stmt_admin_name->bind_param("i", $adminUserID);
    $stmt_admin_name->execute();
    $result_admin_name = $stmt_admin_name->get_result();
    $row_admin_name = $result_admin_name->fetch_assoc();
    if ($row_admin_name && ($row_admin_name['FirstName'] || $row_admin_name['LastName'])) {
        $fullname = htmlspecialchars(trim($row_admin_name['FirstName'] . ' ' . $row_admin_name['LastName']));
    }
    $stmt_admin_name->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_student'])) {
    $studentUserID = $_POST['UserID'] ?? null;
    if ($studentUserID === null || !is_numeric($studentUserID)) {
        $message = "Invalid student ID for update.";
        $message_type = 'error';
    } else {
        // Data for studentinfo table
        $StudentID = $_POST['StudentID'] ?? '';
        $FirstName = $_POST['FirstName'] ?? '';
        $LastName = $_POST['LastName'] ?? '';
        $Gender = $_POST['Gender'] ?? '';
        $DateOfBirth = $_POST['DateOfBirth'] ?? '';
        $CitizenID = $_POST['CitizenID'] ?? '';
        $Passport = $_POST['Passport'] ?? '';
        $Nationality = $_POST['Nationality'] ?? '';
        $Blood = $_POST['Blood'] ?? '';
        $Addresss = $_POST['Addresss'] ?? ''; // Note: Typo 'Addresss' as per your schema
        $EmergencyContact = $_POST['EmergencyContact'] ?? '';
        $Program = $_POST['Program'] ?? '';
        $MotherName = $_POST['MotherName'] ?? '';
        $FatherName = $_POST['FatherName'] ?? '';
        $ParentNationality = $_POST['ParentNationality'] ?? '';
        $MotherOccupation = $_POST['MotherOccupation'] ?? '';
        $FatherOccupation = $_POST['FatherOccupation'] ?? '';

        // Data for users table
        $Phone = $_POST['Phone'] ?? '';
        $Email = $_POST['Email'] ?? '';
        $Password = $_POST['Password'] ?? ''; // Only update if provided
        $TwoFA = isset($_POST['2fa']) ? 1 : 0;

        // Update studentinfo table
        $sql_update_studentinfo = "UPDATE studentinfo SET 
                                    StudentID = ?, FirstName = ?, LastName = ?, Gender = ?, DateOfBirth = ?, 
                                    CitizenID = ?, Passport = ?, Nationality = ?, Blood = ?, Addresss = ?, 
                                    EmergencyContact = ?, Program = ?, MotherName = ?, FatherName = ?, 
                                    ParentNationality = ?, MotherOccupation = ?, FatherOccupation = ?
                                    WHERE UserID = ?";
        $stmt_update_studentinfo = $conn->prepare($sql_update_studentinfo);
        if ($stmt_update_studentinfo) {
            $stmt_update_studentinfo->bind_param("issssssssssssssssi", 
                $StudentID, $FirstName, $LastName, $Gender, $DateOfBirth, 
                $CitizenID, $Passport, $Nationality, $Blood, $Addresss, 
                $EmergencyContact, $Program, $MotherName, $FatherName, 
                $ParentNationality, $MotherOccupation, $FatherOccupation, $studentUserID);
            
            if (!$stmt_update_studentinfo->execute()) {
                $message = "Error updating student info: " . $stmt_update_studentinfo->error;
                $message_type = 'error';
            }
            $stmt_update_studentinfo->close();
        } else {
            $message = "Failed to prepare student info update: " . $conn->error;
            $message_type = 'error';
        }

        // Update users table
        if ($message_type !== 'error') { // Only proceed if studentinfo update was successful
            $sql_update_users = "UPDATE users SET Phone = ?, Email = ?, 2fa = ? WHERE UserID = ?";
            
            // If password is provided, include it in the update
            if (!empty($Password)) {
                $hashedPassword = password_hash($Password, PASSWORD_DEFAULT);
                $sql_update_users = "UPDATE users SET Phone = ?, Email = ?, Password = ?, 2fa = ? WHERE UserID = ?";
                $stmt_update_users = $conn->prepare($sql_update_users);
                if ($stmt_update_users) {
                    $stmt_update_users->bind_param("sssii", $Phone, $Email, $hashedPassword, $TwoFA, $studentUserID);
                }
            } else {
                $stmt_update_users = $conn->prepare($sql_update_users);
                if ($stmt_update_users) {
                    $stmt_update_users->bind_param("ssii", $Phone, $Email, $TwoFA, $studentUserID);
                }
            }
            
            if ($stmt_update_users) {
                if ($stmt_update_users->execute()) {
                    $message = "Student ID " . htmlspecialchars($studentUserID) . " information updated successfully!";
                    $message_type = 'success';
                } else {
                    $message = "Error updating user account: " . $stmt_update_users->error;
                    $message_type = 'error';
                }
                $stmt_update_users->close();
            } else {
                $message = "Failed to prepare user account update: " . $conn->error;
                $message_type = 'error';
            }
        }
        // Redirect to clear POST data and show updated student
        if ($message_type === 'success') {
            header("Location: managestudents.php?edit_student_id=" . $studentUserID . "&message=" . urlencode($message) . "&type=" . $message_type);
            exit();
        } else {
            // Keep the form populated with submission data if there was an error
            $_GET['edit_student_id'] = $studentUserID;
        }
    }
}


if (isset($_GET['edit_student_id']) && is_numeric($_GET['edit_student_id'])) {
    $targetStudentUserID = $_GET['edit_student_id'];
    $sql_get_student = "SELECT u.UserID, u.Phone, u.Email, u.2fa,
                               si.StudentID, si.FirstName, si.LastName, si.Gender, si.DateOfBirth,
                               si.CitizenID, si.Passport, si.Nationality, si.Blood, si.Addresss,
                               si.EmergencyContact, si.Program, si.MotherName, si.FatherName,
                               si.ParentNationality, si.MotherOccupation, si.FatherOccupation
                        FROM users u
                        JOIN studentinfo si ON u.UserID = si.UserID
                        WHERE u.UserID = ? AND u.UserFlag = 1"; // Ensure it's a student
    $stmt_get_student = $conn->prepare($sql_get_student);
    if ($stmt_get_student) {
        $stmt_get_student->bind_param("i", $targetStudentUserID);
        $stmt_get_student->execute();
        $result_get_student = $stmt_get_student->get_result();
        $selectedStudent = $result_get_student->fetch_assoc();
        $stmt_get_student->close();

        if (!$selectedStudent) {
            $message = "Student not found or is not registered as a student user.";
            $message_type = 'error';
            unset($_GET['edit_student_id']); // Clear parameter to show all students
        }
    } else {
        $message = "Failed to prepare student fetch statement: " . $conn->error;
        $message_type = 'error';
    }

    // Retrieve message from URL if redirected after update
    if (isset($_GET['message']) && isset($_GET['type'])) {
        $message = htmlspecialchars($_GET['message']);
        $message_type = htmlspecialchars($_GET['type']);
    }
}

// --- Fetch All Students for Listing ---
$allStudents = [];
$sql_all_students = "SELECT u.UserID, u.Email, u.Phone,
                            si.StudentID, si.FirstName, si.LastName, si.Program
                     FROM users u
                     JOIN studentinfo si ON u.UserID = si.UserID
                     WHERE u.UserFlag = 1
                     ORDER BY si.FirstName, si.LastName";
$stmt_all_students = $conn->prepare($sql_all_students);
if ($stmt_all_students) {
    $stmt_all_students->execute();
    $result_all_students = $stmt_all_students->get_result();
    while ($row = $result_all_students->fetch_assoc()) {
        $allStudents[] = $row;
    }
    $stmt_all_students->close();
} else {
    $message = (empty($message) ? '' : $message . '<br>') . "Error fetching all students: " . $conn->error;
    $message_type = (empty($message_type) ? 'error' : $message_type);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students</title>
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

        .table-container, .form-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 20px auto;
        }

        .table-container h2, .form-container h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
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

        .action-button {
            background-color: #007bff;
            color: white;
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            text-decoration: none; /* For anchor tags styled as buttons */
            display: inline-block;
        }

        .action-button:hover {
            background-color: #0056b3;
        }
        
        .form-group {
            margin-bottom: 15px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .form-group label {
            flex: 0 0 150px; /* Fixed width for labels */
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
            text-align: right;
            padding-right: 15px;
            box-sizing: border-box;
        }

        .form-group .input-field {
            flex: 1 1 calc(100% - 150px); /* Take remaining width */
            margin-bottom: 5px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="date"],
        .form-group input[type="password"],
        .form-group select {
            width: calc(100% - 10px); /* Adjust for padding/border */
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .form-group input[type="checkbox"] {
            margin-left: 0; /* Align checkbox */
            width: auto;
        }

        .form-group input[type="checkbox"] + label {
            text-align: left;
            padding-left: 10px;
        }

        .form-actions {
            margin-top: 20px;
            text-align: center;
        }

        .form-actions button {
            background-color: #28a745;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .form-actions button:hover {
            background-color: #218838;
        }

        .message-success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
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
            <li class="menu-item has-submenu open">
                <a href="#"><i class="fa fa-cog"></i> Manage</a>
                <ul class="submenu" style="max-height: 200px;">
                    <li class="active"><a href="managestudents.php">Students</a></li>
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
                <a href="#"><i class="fa fa-envelope"></i> Manage Tickets</a>
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
    <div class="main-content" id="main-content">
        <div class="page-header">
            <h1>Manage Students</h1>
            <p>Admin UserID: <?= $adminUserID; ?></p>
        </div>

        <?php if ($message): ?>
            <div class="message-<?= $message_type; ?>">
                <?= $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($selectedStudent): ?>
            <div class="form-container">
                <h2>Edit Student: <?= htmlspecialchars($selectedStudent['FirstName'] . ' ' . $selectedStudent['LastName']); ?> (UserID: <?= htmlspecialchars($selectedStudent['UserID']); ?>)</h2>
                <form action="managestudents.php" method="POST">
                    <input type="hidden" name="UserID" value="<?= htmlspecialchars($selectedStudent['UserID']); ?>">
                    
                    <h3>User Account Information</h3>
                    <div class="form-group">
                        <label for="Email">Email:</label>
                        <div class="input-field">
                            <input type="email" id="Email" name="Email" value="<?= htmlspecialchars($selectedStudent['Email']); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="Phone">Phone:</label>
                        <div class="input-field">
                            <input type="tel" id="Phone" name="Phone" value="<?= htmlspecialchars($selectedStudent['Phone']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="Password">New Password (leave blank to keep current):</label>
                        <div class="input-field">
                            <input type="password" id="Password" name="Password">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="2fa">2FA Enabled:</label>
                        <div class="input-field">
                            <input type="checkbox" id="2fa" name="2fa" <?= ($selectedStudent['2fa'] == 1) ? 'checked' : ''; ?>>
                        </div>
                    </div>

                    <h3>Student Profile Information</h3>
                    <div class="form-group">
                        <label for="StudentID">Student ID:</label>
                        <div class="input-field">
                            <input type="text" id="StudentID" name="StudentID" value="<?= htmlspecialchars($selectedStudent['StudentID']); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="FirstName">First Name:</label>
                        <div class="input-field">
                            <input type="text" id="FirstName" name="FirstName" value="<?= htmlspecialchars($selectedStudent['FirstName']); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="LastName">Last Name:</label>
                        <div class="input-field">
                            <input type="text" id="LastName" name="LastName" value="<?= htmlspecialchars($selectedStudent['LastName']); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="Gender">Gender:</label>
                        <div class="input-field">
                            <select id="Gender" name="Gender">
                                <option value="">Select Gender</option>
                                <option value="Male" <?= ($selectedStudent['Gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?= ($selectedStudent['Gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?= ($selectedStudent['Gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="DateOfBirth">Date of Birth:</label>
                        <div class="input-field">
                            <input type="date" id="DateOfBirth" name="DateOfBirth" value="<?= htmlspecialchars($selectedStudent['DateOfBirth']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="CitizenID">Citizen ID:</label>
                        <div class="input-field">
                            <input type="text" id="CitizenID" name="CitizenID" value="<?= htmlspecialchars($selectedStudent['CitizenID']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="Passport">Passport:</label>
                        <div class="input-field">
                            <input type="text" id="Passport" name="Passport" value="<?= htmlspecialchars($selectedStudent['Passport']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="Nationality">Nationality:</label>
                        <div class="input-field">
                            <input type="text" id="Nationality" name="Nationality" value="<?= htmlspecialchars($selectedStudent['Nationality']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="Blood">Blood Group:</label>
                        <div class="input-field">
                            <input type="text" id="Blood" name="Blood" value="<?= htmlspecialchars($selectedStudent['Blood']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="Addresss">Address:</label>
                        <div class="input-field">
                            <input type="text" id="Addresss" name="Addresss" value="<?= htmlspecialchars($selectedStudent['Addresss']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="EmergencyContact">Emergency Contact:</label>
                        <div class="input-field">
                            <input type="tel" id="EmergencyContact" name="EmergencyContact" value="<?= htmlspecialchars($selectedStudent['EmergencyContact']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="Program">Program:</label>
                        <div class="input-field">
                            <input type="text" id="Program" name="Program" value="<?= htmlspecialchars($selectedStudent['Program']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="MotherName">Mother's Name:</label>
                        <div class="input-field">
                            <input type="text" id="MotherName" name="MotherName" value="<?= htmlspecialchars($selectedStudent['MotherName']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="FatherName">Father's Name:</label>
                        <div class="input-field">
                            <input type="text" id="FatherName" name="FatherName" value="<?= htmlspecialchars($selectedStudent['FatherName']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ParentNationality">Parent's Nationality:</label>
                        <div class="input-field">
                            <input type="text" id="ParentNationality" name="ParentNationality" value="<?= htmlspecialchars($selectedStudent['ParentNationality']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="MotherOccupation">Mother's Occupation:</label>
                        <div class="input-field">
                            <input type="text" id="MotherOccupation" name="MotherOccupation" value="<?= htmlspecialchars($selectedStudent['MotherOccupation']); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="FatherOccupation">Father's Occupation:</label>
                        <div class="input-field">
                            <input type="text" id="FatherOccupation" name="FatherOccupation" value="<?= htmlspecialchars($selectedStudent['FatherOccupation']); ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_student">Update Student Information</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <h2>All Students</h2>
            <?php if (!empty($allStudents)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Student ID</th>
                            <th>First Name</th>
                            <th>Last Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Program</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allStudents as $student): ?>
                            <tr>
                                <td><?= htmlspecialchars($student['UserID']); ?></td>
                                <td><?= htmlspecialchars($student['StudentID']); ?></td>
                                <td><?= htmlspecialchars($student['FirstName']); ?></td>
                                <td><?= htmlspecialchars($student['LastName']); ?></td>
                                <td><?= htmlspecialchars($student['Email']); ?></td>
                                <td><?= htmlspecialchars($student['Phone']); ?></td>
                                <td><?= htmlspecialchars($student['Program']); ?></td>
                                <td>
                                    <a href="managestudents.php?edit_student_id=<?= htmlspecialchars($student['UserID']); ?>" class="action-button">View / Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No student records found in the system.</p>
            <?php endif; ?>
        </div>
    </div>

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
