<?php
session_start();
require_once '../conn.php';

// Check if user is logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

$db = new Database();
$conn = $db->getConnection();

$enrolledCourses = [];

try {
    $sql = "SELECT e.CourseID, e.Section, c.CourseName 
            FROM enrolled e
            JOIN courses c ON e.CourseID = c.CourseID
            WHERE e.UserID = ? AND e.Role = 'Student'
            ORDER BY c.CourseName, e.Section";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $enrolledCourses[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error fetching enrolled courses: " . $e->getMessage());
    $error = "Could not retrieve your courses. Please try again later.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-container {
            display: flex;
            flex-grow: 1;
        }
        .sidebar {
            width: 250px;
            background-color: #ffffff;
            border-right: 1px solid #e5e7eb;
            padding: 1.5rem;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .content-area {
            flex-grow: 1;
            padding: 2rem;
            background-color: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="navbar bg-base-100 shadow-md z-10">
        <div class="flex-1">
            <a class="btn btn-ghost text-xl font-bold text-blue-700" href="course_selection.php">Eduor</a>
        </div>
        <div class="flex-none gap-2">
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                    <div class="w-10 rounded-full">
                        <img alt="User Avatar" src="https://daisyui.com/images/stock/photo-1534528741775-53994a69daeb.jpg" />
                    </div>
                </div>
                <ul tabindex="0" class="mt-3 z-[1] p-2 shadow menu menu-sm dropdown-content bg-base-100 rounded-box w-52">
                    <li><a href="#">Profile</a></li>
                    <li><a href="#">Settings</a></li>
                    <li><a href="login.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </div>

    <div class="main-container">
        <div class="sidebar">
            <div class="space-y-4">
                <h2 class="text-lg font-semibold text-gray-800">Navigation</h2>
                <ul class="menu p-0 space-y-2">
                    <li><a href="viewcourse.php" class="btn btn-ghost w-full justify-start text-gray-700 font-medium hover:bg-gray-200">
                        <i class="fa fa-book mr-2"></i> My Courses
                    </a></li>
                    <li><a href="../dash.php" class="btn btn-ghost w-full justify-start text-gray-700 font-medium hover:bg-gray-200">
                        <i class="fa fa-calendar-alt mr-2"></i> Dashboard
                    </a></li>
                    <li><a href="#" class="btn btn-ghost w-full justify-start text-gray-700 font-medium hover:bg-gray-200">
                        <i class="fa fa-inbox mr-2"></i> Inbox
                    </a></li>
                    <li><a href="#" class="btn btn-ghost w-full justify-start text-gray-700 font-medium hover:bg-gray-200">
                        <i class="fa fa-question-circle mr-2"></i> Help
                    </a></li>
                </ul>
            </div>
        </div>

        <div class="content-area">
            <div class="max-w-4xl mx-auto">
                <div class="flex justify-between items-center mb-8">
                    <h1 class="text-4xl font-extrabold text-gray-800">My Courses</h1>
                    <span class="text-lg text-gray-600">User ID: <span class="font-bold"><?php echo htmlspecialchars($userID); ?></span></span>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php elseif (empty($enrolledCourses)): ?>
                    <div class="text-center text-gray-600 text-lg p-10 bg-gray-50 rounded-lg">
                        You are not enrolled in any courses as a student.
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($enrolledCourses as $course): ?>
                            <a href="coursedetail.php?courseID=<?php echo urlencode($course['CourseID']); ?>&section=<?php echo urlencode($course['Section']); ?>" 
                               class="card w-full bg-base-100 shadow-md hover:shadow-lg transition-shadow duration-300 rounded-lg overflow-hidden border border-gray-200">
                                <div class="card-body p-6">
                                    <h2 class="card-title text-2xl font-semibold text-blue-700 mb-2">
                                        <?php echo htmlspecialchars($course['CourseName']); ?>
                                    </h2>
                                    <p class="text-gray-600">
                                        <span class="font-medium">Course ID:</span> <?php echo htmlspecialchars($course['CourseID']); ?>
                                    </p>
                                    <p class="text-gray-600">
                                        <span class="font-medium">Section:</span> <?php echo htmlspecialchars($course['Section']); ?>
                                    </p>
                                    <div class="card-actions justify-end mt-4">
                                        <button class="btn btn-sm btn-primary btn-outline">View Course</button>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>