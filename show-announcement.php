<?php
session_start();
require_once 'conn.php';

// Check login
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

$db = new Database();
$conn = $db->getConnection();

// Fetch CourseIDs where user is enrolled as Student, TA, or Instructor
$sql = "SELECT DISTINCT CourseID FROM enrolled WHERE UserID = ? AND Role IN ('Student', 'TA', 'Instructor')";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$courseIDs = [];
while ($row = $result->fetch_assoc()) {
    $courseIDs[] = $row['CourseID'];
}
$stmt->close();

$annoucements = [];
if (count($courseIDs) > 0) {
    // Create placeholders for prepared statement IN clause
    $placeholders = implode(',', array_fill(0, count($courseIDs), '?'));
    
    // Prepare types string for bind_param
    $types = str_repeat('i', count($courseIDs));
    
    // Prepare query to get annoucements from those courses
    $sql = "SELECT a.*, c.CourseName
            FROM annoucement a
            JOIN courses c ON a.FromCourseID = c.CourseID
            JOIN users u ON a.AuthorUserID = u.UserID
            WHERE a.FromCourseID IN ($placeholders)
            ORDER BY a.DateUpload DESC";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . htmlspecialchars($conn->error));
    }

    // Bind params dynamically
    $stmt->bind_param($types, ...$courseIDs);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $annoucements[] = $row;
    }
    $stmt->close();
}

// Count is no longer needed, so this variable is removed
// $annoucementCount = count($annoucements);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Your Announcements</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="dash.css">
</head>

<body class="bg-gray-100">
    <div class="navbar bg-base-100 shadow-md">
        <div class="navbar-left">
            <span class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></span>
            <img src="https://dummyimage.com/200x40/004080/ffffff&text=Eduor+System" alt="Eduor Logo" class="logo">
        </div>
    </div>

    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="dash.php"><i class="fa fa-home"></i> Home</a></li>
            <li class="active"><a href="announcement.php"><i class="fa fa-bullhorn"></i> Announcements</a></li>
        </ul>
    </div>

    <div class="main-content" id="main-content">
        <section class="max-w-5xl mx-auto p-5">
            <div class="p-8 bg-white rounded-lg shadow-lg">
                <h1 class="text-4xl font-bold mb-6 text-center">Your Announcements</h1>

                <?php 
                // Count is no longer needed here, but you can still check if the array is empty
                if (empty($annoucements)): ?>
                    <p class="text-center text-gray-600">No announcements available for your courses.</p>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($annoucements as $annoucement): ?>
                            <article class="border rounded-lg p-6 shadow hover:shadow-lg transition-shadow duration-300">
                                <header class="mb-3">
                                    <h2 class="text-2xl font-semibold text-blue-700"><?php echo htmlspecialchars($annoucement['Title']); ?></h2>
                                    <div class="text-sm text-gray-500">
                                        Course: <span class="font-medium"><?php echo htmlspecialchars($annoucement['CourseName']); ?></span> |
                                        <time datetime="<?php echo htmlspecialchars($annoucement['DateUpload']); ?>">
                                            <?php echo date('F j, Y, g:i a', strtotime($annoucement['DateUpload'])); ?>
                                        </time>
                                    </div>
                                </header>
                                <p class="text-gray-700 whitespace-pre-line"><?php echo nl2br(htmlspecialchars($annoucement['Description'])); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
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