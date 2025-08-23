<?php
session_start();

require_once '../conn.php';
$db = new Database();
$conn = $db->getConnection();

// --- Faculty Authentication Check ---
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php"); // Redirect to login if not authenticated
    exit();
}

$facultyUserID = $_SESSION['UserID'];

// Verify if the logged-in user is a faculty member (UserFlag = 2)
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

if (!isset($user_info) || $user_info['UserFlag'] !== 2) {
    // Redirect non-faculty users (or users with incorrect UserFlag)
    header("Location: ../dashboard.php"); // Or another appropriate dashboard
    exit();
}
// --- End Faculty Authentication Check ---

$fullname = 'User'; // Default name for the navbar
$userEmail = 'N/A'; // Default email for the navbar

// Fetch user's full name for the navbar/sidebar
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

// Fetch user's email from the 'users' table for the navbar/sidebar
$sql_user_email = "SELECT Email FROM users WHERE UserID = ?";
$stmt_user_email = $conn->prepare($sql_user_email);
if ($stmt_user_email) {
    $stmt_user_email->bind_param("i", $facultyUserID);
    $stmt_user_email->execute();
    $result_user_email = $stmt_user_email->get_result();
    $row_email = $result_user_email->fetch_assoc();
    if ($row_email) {
        $userEmail = htmlspecialchars($row_email['Email']);
    }
    $stmt_user_email->close();
}


$courses = [];
$students = [];
$message = '';
$message_type = ''; // 'success' or 'error'

// Fetch courses taught by the current faculty
$sql_courses = "SELECT DISTINCT CourseID, Section 
                FROM enrolled 
                WHERE UserID = ? AND Role = 'Faculty'";
$stmt_courses = $conn->prepare($sql_courses);
if ($stmt_courses) {
    $stmt_courses->bind_param("i", $facultyUserID);
    $stmt_courses->execute();
    $result_courses = $stmt_courses->get_result();
    while ($row = $result_courses->fetch_assoc()) {
        $courses[] = $row;
    }
    $stmt_courses->close();
} else {
    $message = "Error fetching courses: " . $conn->error;
    $message_type = 'error';
}


// Handle AJAX request for students (if a course is selected)
if (isset($_POST['action']) && $_POST['action'] === 'get_students' && isset($_POST['course_id']) && isset($_POST['section'])) {
    $selectedCourseID = $_POST['course_id'];
    $selectedSection = $_POST['section'];

    $sql_students = "SELECT e.UserID, si.FirstName, si.LastName 
                     FROM enrolled e
                     JOIN studentinfo si ON e.UserID = si.UserID
                     WHERE e.CourseID = ? AND e.Section = ? AND e.Role = 'Student'
                     ORDER BY si.FirstName, si.LastName";
    $stmt_students = $conn->prepare($sql_students);
    if ($stmt_students) {
        $stmt_students->bind_param("ss", $selectedCourseID, $selectedSection);
        $stmt_students->execute();
        $result_students = $stmt_students->get_result();
        $fetched_students = [];
        while ($row = $result_students->fetch_assoc()) {
            $fetched_students[] = $row;
        }
        $stmt_students->close();
        echo json_encode(['success' => true, 'students' => $fetched_students]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to prepare student query: ' . $conn->error]);
    }
    $conn->close();
    exit(); // Important to exit after AJAX response
}


// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grades'])) {
    $selectedCourseID = $_POST['course_id'] ?? '';
    $selectedSection = $_POST['section'] ?? '';
    $studentUserID = $_POST['student_id'] ?? '';
    $assignments = (float)($_POST['assignments'] ?? 0);
    $attendance = (float)($_POST['attendance'] ?? 0);
    $midterm = (float)($_POST['midterm'] ?? 0);
    $finalterm = (float)($_POST['finalterm'] ?? 0);
    $quizzes = (float)($_POST['quizzes'] ?? 0);
    $semester = $_POST['semester'] ?? '';

    // Input validation
    if (empty($selectedCourseID) || empty($selectedSection) || empty($studentUserID) || empty($semester)) {
        $message = "Please select a course, section, student, and provide the semester.";
        $message_type = 'error';
    } else if (!is_numeric($studentUserID) || $studentUserID <= 0) {
        $message = "Invalid student selected.";
        $message_type = 'error';
    } else {
        // Validate score ranges (0-100)
        if ($assignments < 0 || $assignments > 100 ||
            $attendance < 0 || $attendance > 100 ||
            $midterm < 0 || $midterm > 100 ||
            $finalterm < 0 || $finalterm > 100 ||
            $quizzes < 0 || $quizzes > 100) {
            $message = "All assessment scores must be between 0 and 100.";
            $message_type = 'error';
        } else {
            // Calculate total score with weights
            $totalScore = (
                ($assignments ) +
                ($attendance ) +
                ($midterm ) +
                ($finalterm ) +
                ($quizzes)
            );

            if ($totalScore > 100) {
                $message = "Calculated total score exceeds 100. Please check your inputs.";
                $message_type = 'error';
            } else {
                // Determine grade
                $grade = '';
                if ($totalScore >= 93) $grade = "A";
                else if ($totalScore >= 90) $grade = "A-";
                else if ($totalScore >= 87) $grade = "B+";
                else if ($totalScore >= 83) $grade = "B";
                else if ($totalScore >= 80) $grade = "B-";
                else if ($totalScore >= 77) $grade = "C+";
                else if ($totalScore >= 73) $grade = "C";
                else if ($totalScore >= 70) $grade = "C-";
                else if ($totalScore >= 67) $grade = "D+";
                else if ($totalScore >= 60) $grade = "D";
                else $grade = "F";

                // Insert into grades table
                $sql_insert_grade = "INSERT INTO grades (UserID, CourseID, Grade, Semester) VALUES (?, ?, ?, ?)";
                $stmt_insert_grade = $conn->prepare($sql_insert_grade);
                if ($stmt_insert_grade) {
                    $stmt_insert_grade->bind_param("isss", $studentUserID, $selectedCourseID, $grade, $semester);
                    if ($stmt_insert_grade->execute()) {
                        $message = "Grade '$grade' for student ID $studentUserID in $selectedCourseID ($selectedSection) for $semester published successfully! Total Score: " . round($totalScore, 2);
                        $message_type = 'success';
                    } else {
                        $message = "Error publishing grade: " . $stmt_insert_grade->error;
                        $message_type = 'error';
                    }
                    $stmt_insert_grade->close();
                } else {
                    $message = "Failed to prepare grade insertion statement: " . $conn->error;
                    $message_type = 'error';
                }
            }
        }
    }
}
$conn->close(); // Close connection after all operations
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publish Grades</title>
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

        .form-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 20px auto;
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

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box; /* Ensures padding doesn't increase width */
        }
        
        .form-group input[type="number"] {
            -moz-appearance: textfield; /* Firefox */
        }
        .form-group input[type="number"]::-webkit-outer-spin-button,
        .form-group input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .btn-submit {
            background-color: #28a745;
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
            <li><a href="facultydashboard.php"><i class="fa fa-home"></i> Home</a></li>
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
                    <li class="active"><a href="publishgrades.php">Publish Grades</a></li>
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
            <h1>Publish Grades</h1>
            <p>UserID: <?= $facultyUserID; ?></p>
        </div>

        <div class="form-container">
            <?php if ($message): ?>
                <div class="message-<?= $message_type; ?>">
                    <?= $message; ?>
                </div>
            <?php endif; ?>

            <form action="publishgrades.php" method="POST">
                <!-- Course Selection -->
                <div class="form-group">
                    <label for="course_select">Select Course & Section:</label>
                    <select id="course_select" name="course_id_section" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= htmlspecialchars($course['CourseID'] . '|' . $course['Section']); ?>"
                                <?= (isset($_POST['course_id_section']) && $_POST['course_id_section'] == $course['CourseID'] . '|' . $course['Section']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($course['CourseID'] . ' - Section ' . $course['Section']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" id="hidden_course_id" name="course_id">
                    <input type="hidden" id="hidden_section" name="section">
                </div>

                <!-- Student Selection -->
                <div class="form-group">
                    <label for="student_select">Select Student:</label>
                    <select id="student_select" name="student_id" required <?= empty($students) ? 'disabled' : ''; ?>>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= htmlspecialchars($student['UserID']); ?>"
                                <?= (isset($_POST['student_id']) && $_POST['student_id'] == $student['UserID']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName'] . ' (ID: ' . $student['UserID'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Assessment Inputs -->
                <div class="form-group">
                    <label for="assignments">Assignments (0-100%):</label>
                    <input type="number" id="assignments" name="assignments" min="0" max="100" step="0.01" required value="<?= htmlspecialchars($_POST['assignments'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="attendance">Attendance (0-100%):</label>
                    <input type="number" id="attendance" name="attendance" min="0" max="100" step="0.01" required value="<?= htmlspecialchars($_POST['attendance'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="midterm">Mid Term (0-100%):</label>
                    <input type="number" id="midterm" name="midterm" min="0" max="100" step="0.01" required value="<?= htmlspecialchars($_POST['midterm'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="finalterm">Final Term (0-100%):</label>
                    <input type="number" id="finalterm" name="finalterm" min="0" max="100" step="0.01" required value="<?= htmlspecialchars($_POST['finalterm'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="quizzes">Quizzes (0-100%):</label>
                    <input type="number" id="quizzes" name="quizzes" min="0" max="100" step="0.01" required value="<?= htmlspecialchars($_POST['quizzes'] ?? ''); ?>">
                </div>

                <!-- Semester -->
                <div class="form-group">
                    <label for="semester">Semester (e.g., Fall 2025):</label>
                    <input type="text" id="semester" name="semester" required value="<?= htmlspecialchars($_POST['semester'] ?? ''); ?>">
                </div>

                <button type="submit" name="submit_grades" class="btn-submit">Publish Grade</button>
            </form>
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

            // Handle course selection change dynamically
            const courseSelect = document.getElementById('course_select');
            const studentSelect = document.getElementById('student_select');
            const hiddenCourseId = document.getElementById('hidden_course_id');
            const hiddenSection = document.getElementById('hidden_section');

            const loadStudents = async (courseId, section) => {
                if (!courseId || !section) {
                    studentSelect.innerHTML = '<option value="">-- Select Student --</option>';
                    studentSelect.disabled = true;
                    return;
                }

                try {
                    const response = await fetch('publishgrades.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=get_students&course_id=${encodeURIComponent(courseId)}&section=${encodeURIComponent(section)}`
                    });
                    const data = await response.json();

                    studentSelect.innerHTML = '<option value="">-- Select Student --</option>';
                    if (data.success && data.students.length > 0) {
                        data.students.forEach(student => {
                            const option = document.createElement('option');
                            option.value = student.UserID;
                            option.textContent = `${student.FirstName} ${student.LastName} (ID: ${student.UserID})`;
                            studentSelect.appendChild(option);
                        });
                        studentSelect.disabled = false;
                    } else {
                        studentSelect.disabled = true;
                        if (data.error) {
                             console.error("Error loading students:", data.error);
                             const option = document.createElement('option');
                             option.value = "";
                             option.textContent = `Error: ${data.error}`;
                             studentSelect.appendChild(option);
                        } else {
                             const option = document.createElement('option');
                             option.value = "";
                             option.textContent = "No students found for this course/section.";
                             studentSelect.appendChild(option);
                        }
                    }
                    
                    // Re-select student if previously selected after reload
                    const previouslySelectedStudent = "<?= htmlspecialchars($_POST['student_id'] ?? ''); ?>";
                    if (previouslySelectedStudent) {
                         studentSelect.value = previouslySelectedStudent;
                    }

                } catch (error) {
                    console.error('Error fetching students:', error);
                    studentSelect.innerHTML = '<option value="">Error loading students.</option>';
                    studentSelect.disabled = true;
                }
            };

            courseSelect.addEventListener('change', () => {
                const selectedValue = courseSelect.value;
                if (selectedValue) {
                    const [courseId, section] = selectedValue.split('|');
                    hiddenCourseId.value = courseId;
                    hiddenSection.value = section;
                    loadStudents(courseId, section);
                } else {
                    hiddenCourseId.value = '';
                    hiddenSection.value = '';
                    loadStudents(null, null); // Clear students if no course is selected
                }
            });

            // Trigger initial load if a course was already selected (e.g., after a form submission with errors)
            const initialSelectedCourseSection = courseSelect.value;
            if (initialSelectedCourseSection) {
                const [courseId, section] = initialSelectedCourseSection.split('|');
                hiddenCourseId.value = courseId;
                hiddenSection.value = section;
                loadStudents(courseId, section);
            }
        });
    </script>
</body>
</html>
