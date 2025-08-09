<?php
session_start();
require_once 'conn.php';

// Ensure user logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

$db = new Database();
$conn = $db->getConnection();

// Observer interfaces and classes
interface Observer {
    public function update($announcement);
}

interface Subject {
    public function attach(Observer $observer);
    public function detach(Observer $observer);
    public function notify($announcement);
}

class AnnouncementSubject implements Subject {
    private $observers = [];

    public function attach(Observer $observer) {
        $this->observers[$observer->getUserID()] = $observer;
    }

    public function detach(Observer $observer) {
        unset($this->observers[$observer->getUserID()]);
    }

    public function notify($announcement) {
        foreach ($this->observers as $observer) {
            $observer->update($announcement);
        }
    }

    public function loadObserversByCourse($courseID, $conn) {
        $this->observers = [];
        $sql = "SELECT UserID FROM enrolled WHERE CourseID = ? AND Role = 'Student'";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . htmlspecialchars($conn->error));
        }
        // Since CourseID is VARCHAR, use 's' for bind_param
        $stmt->bind_param("s", $courseID);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $this->attach(new StudentObserver($row['UserID'], $conn));
        }
        $stmt->close();
    }
}

class StudentObserver implements Observer {
    private $userID;
    private $conn;

    public function __construct($userID, $conn) {
        $this->userID = $userID;
        $this->conn = $conn;
    }

    public function getUserID() {
        return $this->userID;
    }

    public function update($announcement) {
        // Insert notification or record for student
        $stmt = $this->conn->prepare("INSERT INTO notifications (UserID, AnnouncementID, IsRead) VALUES (?, ?, 0)");
        if (!$stmt) {
            // Log error or handle failure silently
            return;
        }
        $stmt->bind_param("is", $this->userID, $announcement['AnnounceID']);
        $stmt->execute();
        $stmt->close();
    }
}

// Generate random AnnounceID (12 chars alphanumeric)
function generateAnnounceID($length = 12) {
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $id = '';
    for ($i = 0; $i < $length; $i++) {
            $id .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $id;
}

// Fetch faculty courses for dropdown
$stmt = $conn->prepare("SELECT c.CourseID, c.CourseName FROM courses c JOIN enrolled e ON c.CourseID = e.CourseID WHERE e.UserID = ? AND e.Role = 'Faculty'");
if (!$stmt) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $userID);
$stmt->execute();
$facultyCourses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $courseID = $_POST['course'] ?? '';

    // Validate inputs
    if (!$title) $errors[] = "Title is required.";
    if (!$description) $errors[] = "Description is required.";

    // Corrected validation: compare as strings because CourseID is VARCHAR
    $validCourseIDs = array_column($facultyCourses, 'CourseID');
    if (!$courseID || !in_array($courseID, $validCourseIDs, true)) {
        $errors[] = "Invalid course selected.";
    }

    if (empty($errors)) {
        // Generate AnnounceID and insert announcement
        $announceID = generateAnnounceID();

        $stmt = $conn->prepare("INSERT INTO annoucement (AnnouceID, AuthorUserID, FromCourseID, Description, DateUpload, Title) VALUES (?, ?, ?, ?, NOW(), ?)");
        if (!$stmt) {
            die("Prepare failed: " . htmlspecialchars($conn->error));
        }
        // Bind parameters types: AnnouceID (string), AuthorUserID (int), FromCourseID (string), Description (string), Title (string)
        // Note the change in bind_param: 's' for FromCourseID
        $stmt->bind_param("sissi", $announceID, $userID, $courseID, $description, $title);


        if ($stmt->execute()) {
            $announcement = [
                'AnnounceID' => $announceID,
                'AuthorUserID' => $userID,
                'FromCourseID' => $courseID,
                'Description' => $description,
                'Title' => $title,
                'DateUpload' => date('Y-m-d H:i:s')
            ];

            $subject = new AnnouncementSubject();
            $subject->loadObserversByCourse($courseID, $conn);
            $subject->notify($announcement);

            $success = true;
        } else {
            $errors[] = "Failed to post announcement: " . htmlspecialchars($stmt->error);
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Announcement</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 to-yellow-200 flex items-center justify-center">

<div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-xl">
    <h1 class="text-3xl font-bold mb-6">Create Announcement</h1>

    <?php if ($success): ?>
        <div class="alert alert-success mb-4">
            Announcement posted and students notified successfully.
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-error mb-4">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="" class="space-y-4">
        <div>
            <label class="label"><span class="label-text font-semibold">Title</span></label>
            <input type="text" name="title" required
                   class="input input-bordered w-full"
                   value="<?php echo htmlspecialchars($_POST['title'] ?? '') ?>">
        </div>

        <div>
            <label class="label"><span class="label-text font-semibold">Description</span></label>
            <textarea name="description" required
                      class="textarea textarea-bordered w-full"
                      rows="5"><?php echo htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>

        <div>
            <label class="label"><span class="label-text font-semibold">Select Course</span></label>
            <select name="course" required class="select select-bordered w-full">
                <option value="" disabled <?php echo !isset($_POST['course']) ? 'selected' : '' ?>>Select a course</option>
                <?php foreach ($facultyCourses as $course): ?>
                    <option value="<?php echo $course['CourseID'] ?>"
                        <?php echo (isset($_POST['course']) && $_POST['course'] === $course['CourseID']) ? 'selected' : '' ?>>
                        <?php echo htmlspecialchars($course['CourseName']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-full">Post Announcement</button>
    </form>
</div>

</body>
</html>