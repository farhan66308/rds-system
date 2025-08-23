<?php
// take_attendance.php
require_once "../libs/functions.php";
require_once '../conn.php';
session_start();

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = '';

// Check if user is authenticated and has the correct UserFlag
if (!isset($_SESSION['UserID'])) {
    header("Location: ../login.php");
    exit();
}

$facultyID = $_SESSION['UserID'];

// Check for UserFlag == 2
$stmt = $conn->prepare("SELECT UserFlag FROM users WHERE UserID = ?");
// FIX 1: UserID is varchar, so bind as 's'
$stmt->bind_param("s", $facultyID);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!isset($user) || $user['UserFlag'] != 2) {
    header("Location: ../unauthorized.php"); // Redirect to an unauthorized page
    exit();
}

$currentStep = 'select_course';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['course_info'])) {
        list($courseID, $section) = explode('|', $_POST['course_info']);
        $currentStep = 'take_attendance';
    } 
    elseif (isset($_POST['submit_attendance'])) {
        $courseID = $_POST['course_id'];
        $section = $_POST['section'];
        $facultyID = $_POST['faculty_id'];
        $lectureNumber = $_POST['lecture_number'];
        $date = date('Y-m-d');
        
        $attendanceSubmitted = true;
        foreach ($_POST['status'] as $studentID => $status) {
            $attendanceID = AttendanceIDGen();
            $stmt = $conn->prepare("INSERT INTO attendance (AttendanceID, CourseID, StudentID, LectureNumber, Status, Date, Section, FacultyID) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            // FIX 2: StudentID is varchar, so bind as 's'. Section is int, so bind as 'i'.
            $stmt->bind_param("sssiisss", $attendanceID, $courseID, $studentID, $lectureNumber, $status, $date, $section, $facultyID);
            
            if (!$stmt->execute()) {
                $errors[] = "Failed to submit attendance for student ID {$studentID}: " . $stmt->error;
                $attendanceSubmitted = false;
            }
            $stmt->close();
        }

        if ($attendanceSubmitted) {
            $success = "Attendance for Lecture #{$lectureNumber} of course {$courseID} (Section {$section}) has been successfully recorded.";
            $currentStep = 'select_course';
        }
    }
}

// --- Dynamic Content based on the current step ---

// Step 1: Display list of courses
if ($currentStep === 'select_course') {
    $facultyCourses = [];
    $stmt = $conn->prepare("SELECT CourseID, Section FROM enrolled WHERE UserID = ? AND Role = 'Faculty'");
    // FIX 1 (applied again here): UserID is varchar, so bind as 's'
    $stmt->bind_param("s", $facultyID);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $facultyCourses[] = $row;
    }
    $stmt->close();
}

// Step 2: Display list of students for attendance
if ($currentStep === 'take_attendance') {
    // Get next lecture number
    $stmt = $conn->prepare("SELECT MAX(LectureNumber) AS last_lecture FROM attendance WHERE CourseID = ? AND Section = ?");
    $stmt->bind_param("si", $courseID, $section); // This was already correct
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $lectureNumber = ($row['last_lecture'] ?? 0) + 1;
    $stmt->close();

    // Get list of students
    $students = [];
    $stmt = $conn->prepare("
        SELECT e.UserID, s.FirstName, s.LastName
        FROM enrolled e
        JOIN studentinfo s ON e.UserID = s.UserID
        WHERE e.CourseID = ? AND e.Section = ? AND (e.Role = 'Student' OR e.Role = 'student')
    ");
    // This was the fix from before, which is correct based on your new info
    $stmt->bind_param("si", $courseID, $section);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}
// For sidebar/navbar consistency
$current_page = 'take_attendance.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Attendance | Eduor System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
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
            <li><a href="../faculty/dashboard.php"><i class="fa fa-home"></i> Home</a></li>
            <li class="<?= ($current_page == 'takeattendance.php') ? 'active' : ''; ?>"><a href="take_attendance.php"><i class="fa fa-check-square"></i> Take Attendance</a></li>
            <li><a href="../settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php"><i class="fa fa-power-off"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content" id="main-content">
        <section class="max-w-4xl mx-auto p-5">
            <h1 class="text-3xl font-bold mb-6 text-center">Take Class Attendance</h1>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error mb-4">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success mb-4">
                    <p><?= htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($currentStep === 'select_course'): ?>
                <div class="p-8 bg-white rounded-lg shadow-md">
                    <h2 class="text-2xl font-semibold mb-4 border-b pb-2">Select a Course to take Attendance</h2>
                    <?php if (empty($facultyCourses)): ?>
                        <div class="alert alert-info">
                            <p>You are not currently leading any courses.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="mb-4">
                                <label for="course_select" class="block text-sm font-medium text-gray-700 mb-2">My Courses</label>
                                <select id="course_select" name="course_info" class="select select-bordered w-full" required>
                                    <option value="" disabled selected>Select a Course and Section</option>
                                    <?php foreach ($facultyCourses as $course): ?>
                                        <option value="<?= htmlspecialchars($course['CourseID'] . '|' . $course['Section']); ?>">
                                            <?= htmlspecialchars($course['CourseID']); ?> (Section: <?= htmlspecialchars($course['Section']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <button type="submit" class="btn btn-primary w-full">Proceed to Attendance</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

            <?php elseif ($currentStep === 'take_attendance'): ?>
                <div class="p-8 bg-white rounded-lg shadow-md">
                    <h2 class="text-2xl font-semibold mb-4 border-b pb-2">Attendance for <?= htmlspecialchars($courseID); ?> (Section: <?= htmlspecialchars($section); ?>)</h2>
                    <p class="text-lg font-medium mb-4">Lecture #<?= htmlspecialchars($lectureNumber); ?> | Date: <?= date('F j, Y'); ?></p>

                    <?php if (empty($students)): ?>
                        <div class="alert alert-info">
                            <p>No students are currently enrolled in this course and section.</p>
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <table class="table-auto w-full text-left">
                                <thead>
                                    <tr>
                                        <th class="py-2">Student Name</th>
                                        <th class="py-2">Present</th>
                                        <th class="py-2">Absent</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td class="py-2"><?= htmlspecialchars($student['FirstName'] . ' ' . $student['LastName']); ?></td>
                                            <td class="py-2">
                                                <input type="radio" name="status[<?= $student['UserID']; ?>]" value="Present" class="radio radio-primary" checked />
                                            </td>
                                            <td class="py-2">
                                                <input type="radio" name="status[<?= $student['UserID']; ?>]" value="Absent" class="radio radio-primary" />
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            
                            <input type="hidden" name="course_id" value="<?= htmlspecialchars($courseID); ?>">
                            <input type="hidden" name="section" value="<?= htmlspecialchars($section); ?>">
                            <input type="hidden" name="faculty_id" value="<?= htmlspecialchars($facultyID); ?>">
                            <input type="hidden" name="lecture_number" value="<?= htmlspecialchars($lectureNumber); ?>">
                            <div class="mt-6">
                                <button type="submit" name="submit_attendance" class="btn btn-primary w-full">Submit Attendance</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("active");
            document.getElementById("main-content").classList.toggle("shift");
        }
    </script>
</body>
</html>