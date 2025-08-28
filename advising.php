<?php
session_start();

require_once 'conn.php';
$db = new Database();
$conn = $db->getConnection();

if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php"); 
    exit();
}

$facultyUserID = $_SESSION['UserID'];

$sql_check_faculty = "SELECT UserFlag FROM users WHERE UserID = ?";
$stmt_check_faculty = $conn->prepare($sql_check_faculty);
if (!$stmt_check_faculty) {
    die("Prepare failed: " . $conn->error);
}
$stmt_check_faculty->bind_param("i", $facultyUserID);
$stmt_check_faculty->execute();
$result_check_faculty = $stmt_check_faculty->get_result();
$user_info = $result_check_faculty->fetch_assoc();
$stmt_check_faculty->close();

$fullname = 'User'; 
$message = '';
$message_type = '';A

$sql_user_name = "SELECT FirstName, LastName FROM studentinfo WHERE UserID = ?";
$stmt_user_name = $conn->prepare($sql_user_name);
if ($stmt_user_name) {
    $stmt_user_name->bind_param("i", $facultyUserID);
    $stmt_user_name->execute();
    $result_user_name = $stmt_user_name->get_result();
    $row_name = $result_user_name->fetch_assoc();
    if ($row_name && ($row_name['FirstName'] || $row_name['LastName'])) {
        $fullname = htmlspecialchars(trim($row_name['FirstName'] . ' ' . $row_name['LastName']));
    }
    $stmt_user_name->close();
}

$allCourses = [];
$sql_all_courses = "SELECT CourseID, CourseName, Credits FROM courses ORDER BY CourseID";
$stmt_all_courses = $conn->prepare($sql_all_courses);
if ($stmt_all_courses) {
    $stmt_all_courses->execute();
    $result_all_courses = $stmt_all_courses->get_result();
    while ($row = $result_all_courses->fetch_assoc()) {
        $allCourses[] = $row;
    }
    $stmt_all_courses->close();
} else {
    $message = "Error fetching all courses: " . $conn->error;
    $message_type = 'error';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['advise_course'])) {
    $selectedCourseID = $_POST['course_id'] ?? '';

    if (empty($selectedCourseID)) {
        $message = "Please select a course to advise.";
        $message_type = 'error';
    } else {
        // Check if the faculty is already advising this course to prevent duplicates
        $sql_check_advised = "SELECT COUNT(*) FROM preadvise WHERE UserID = ? AND CourseID = ?";
        $stmt_check_advised = $conn->prepare($sql_check_advised);
        if ($stmt_check_advised) {
            $stmt_check_advised->bind_param("is", $facultyUserID, $selectedCourseID);
            $stmt_check_advised->execute();
            $stmt_check_advised->bind_result($count);
            $stmt_check_advised->fetch();
            $stmt_check_advised->close();

            if ($count > 0) {
                $message = "You are already advising this course.";
                $message_type = 'error';
            } else {
                // Insert
                $sql_insert_advised = "INSERT INTO preadvise (UserID, CourseID) VALUES (?, ?)";
                $stmt_insert_advised = $conn->prepare($sql_insert_advised);
                if ($stmt_insert_advised) {
                    $stmt_insert_advised->bind_param("is", $facultyUserID, $selectedCourseID);
                    if ($stmt_insert_advised->execute()) {
                        $message = "Course " . htmlspecialchars($selectedCourseID) . " added to your advised list.";
                        $message_type = 'success';
                    } else {
                        $message = "Error advising course: " . $stmt_insert_advised->error;
                        $message_type = 'error';
                    }
                    $stmt_insert_advised->close();
                } else {
                    $message = "Failed to prepare course advising statement: " . $conn->error;
                    $message_type = 'error';
                }
            }
        } else {
            $message = "Error checking existing advised courses: " . $conn->error;
            $message_type = 'error';
        }
    }
}

// Fetch courses already advised by the current faculty
$advisedCourses = [];
$sql_advised_courses = "SELECT pa.CourseID, c.CourseName, c.Credits 
                        FROM preadvise pa
                        JOIN courses c ON pa.CourseID = c.CourseID
                        WHERE pa.UserID = ?
                        ORDER BY pa.CourseID";
$stmt_advised_courses = $conn->prepare($sql_advised_courses);
if ($stmt_advised_courses) {
    $stmt_advised_courses->bind_param("i", $facultyUserID);
    $stmt_advised_courses->execute();
    $result_advised_courses = $stmt_advised_courses->get_result();
    while ($row = $result_advised_courses->fetch_assoc()) {
        $advisedCourses[] = $row;
    }
    $stmt_advised_courses->close();
} else {
    $message = (empty($message) ? '' : $message . '<br>') . "Error fetching advised courses: " . $conn->error;
    $message_type = (empty($message_type) ? 'error' : $message_type); // Set to error if not already
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Advising</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="dash.css">
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

        .form-container, .table-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 20px auto;
        }
        .table-container {
            margin-top: 40px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .btn-submit {
            background-color: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        .btn-submit:hover {
            background-color: #0056b3;
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

        .table-container table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table-container th, .table-container td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }

        .table-container th {
            background-color: #e9ecef;
            font-weight: bold;
            color: #495057;
        }

        .table-container tbody tr:nth-child(even) {
            background-color: #f8f9fa;
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
            <li><a href="Dash.php"><i class="fa fa-home"></i> Home</a></li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-check-circle"></i> Info</a>
                <ul class="submenu">
                    <li><a href="../profile.php">Profile</a></li>
                    <li><a href="../editprofile.php">Edit</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu open">
                <a href="#"><i class="fa fa-book"></i> Courses</a>
                <ul class="submenu" style="max-height: 200px;">
                    <li><a href="viewcourse.php">Manage</a></li>
                    <li><a href="takeattendance.php">Take Attendance</a></li>
                    <li><a href="publishgrades.php">Publish Grades</a></li>
                    <li class="active"><a href="advising.php">Advising</a></li> <!-- New active link -->
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
                    <li><a href="../create-announce.php">Create Announcements</a></li>
                    <li><a href="#">Show Announcement</a></li>
                </ul>
            </li>
            <li class="menu-item has-submenu">
                <a href="#"><i class="fa fa-envelope"></i> Support</a>
                <ul class="submenu">
                    <li><a href="#">Create Ticket</a></li>
                    <li><a href="#">Track your support</a></li>
                </ul>
            </li>
            <li><a href="../settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php"><i class="fa fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <div class="page-header">
            <h1>Faculty Advising</h1>
            <p>UserID: <?= $facultyUserID; ?></p>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="message-<?= $message_type; ?>">
                    <?= $message; ?>
                </div>
            <?php endif; ?>

            <h2>Add Course to Advise</h2>
            <form action="advising.php" method="POST">
                <div class="form-group">
                    <label for="course_select">Select Course:</label>
                    <select id="course_select" name="course_id" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($allCourses as $course): ?>
                            <option value="<?= htmlspecialchars($course['CourseID']); ?>">
                                <?= htmlspecialchars($course['CourseID'] . ' - ' . $course['CourseName'] . ' (' . $course['Credits'] . ' Credits)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="advise_course" class="btn-submit">Confirm Advising</button>
            </form>
        </div>

        <div class="table-container">
            <h2>Courses I Advise</h2>
            <?php if (!empty($advisedCourses)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Course ID</th>
                            <th>Course Name</th>
                            <th>Credits</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($advisedCourses as $advisedCourse): ?>
                            <tr>
                                <td><?= htmlspecialchars($advisedCourse['CourseID']); ?></td>
                                <td><?= htmlspecialchars($advisedCourse['CourseName']); ?></td>
                                <td><?= htmlspecialchars($advisedCourse['Credits']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>You are not currently advising any courses.</p>
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
