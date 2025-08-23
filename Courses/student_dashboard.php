<?php
session_start();
require_once '../conn.php';

// Check login, if not logged in, redirect to login page.
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];
$db = new Database();
$conn = $db->getConnection();

$enrolledCourses = [];

// This query retrieves course info for all courses the user is enrolled in.
// It joins the 'enrolled', 'courses', and 'coursetructure' tables.
$sql = "SELECT c.CourseID, c.CourseName, c.Description, s.Section FROM enrolled e JOIN courses c ON e.CourseID = c.CourseID LEFT JOIN coursetructure s ON c.CourseID = s.CourseID WHERE e.UserID = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $enrolledCourses[] = $row;
}
$stmt->close();
$conn->close();

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Courses - Eduor System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        .course-card-link {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .course-card-link:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>

<body class="bg-gray-100">
    <div class="navbar bg-base-100 shadow-md">
        <div class="navbar-left">
            <span class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></span>
            <img src="https://dummyimage.com/200x40/004080/ffffff&text=Eduor+System" alt="Eduor Logo" class="logo">
        </div>
    </div>
    
    <div class="main-content" id="main-content">
        <section class="max-w-5xl mx-auto p-5">
            <h1 class="text-3xl font-bold mb-6 text-center">My Courses</h1>
            
            <?php if (empty($enrolledCourses)): ?>
                <div class="text-center p-6 bg-white rounded-lg shadow-md">
                    <p class="text-gray-600">You are not enrolled in any courses yet.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($enrolledCourses as $course): ?>
                        <a href="view_course.php?course_id=<?= urlencode($course['CourseID']); ?>&section=<?= urlencode($course['Section']); ?>" class="course-card-link">
                            <div class="card bg-white shadow-xl h-full">
                                <div class="card-body">
                                    <h2 class="card-title text-2xl"><?= htmlspecialchars($course['CourseName']); ?></h2>
                                    <p class="text-gray-600 mb-2"><?= htmlspecialchars($course['CourseID']); ?></p>
                                    <p class="text-gray-700 mt-2"><?= nl2br(htmlspecialchars($course['Description'])); ?></p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>