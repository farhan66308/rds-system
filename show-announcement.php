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

// Count annoucements
$annoucementCount = count($annoucements);
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Your annoucements</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 to-yellow-200 p-6">

<div class="max-w-5xl mx-auto bg-white rounded-lg shadow-lg p-8">
    <h1 class="text-4xl font-bold mb-6 text-center">Your annoucements</h1>

    <div class="mb-6 text-center text-lg font-semibold">
        You have <span class="text-green-600"><?php echo $annoucementCount; ?></span> annoucement<?php echo $annoucementCount !== 1 ? 's' : ''; ?>.
    </div>

    <?php if ($annoucementCount === 0): ?>
        <p class="text-center text-gray-600">No annoucements available for your courses.</p>
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

</body>
</html>
