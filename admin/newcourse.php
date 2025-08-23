<?php
session_start();
require_once '../conn.php'; // Adjust path as necessary

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = '';

// Initialize form fields to retain values after submission
$courseId = $_POST['course_id'] ?? '';
$courseName = $_POST['course_name'] ?? '';
$description = $_POST['description'] ?? '';
$credits = $_POST['credits'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    if (empty($courseId)) {
        $errors[] = "Course ID is required.";
    }
    if (empty($courseName)) {
        $errors[] = "Course Name is required.";
    }
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    if (empty($credits) || !is_numeric($credits) || (int)$credits <= 0) {
        $errors[] = "Credits must be a positive number.";
    }

    // If no validation errors, proceed to check for duplicate CourseID
    if (empty($errors)) {
        // Check if CourseID already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM courses WHERE CourseID = ?");
        if (!$stmt) {
            $errors[] = "Database prepare error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $courseId);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count > 0) {
                $errors[] = "A course with this Course ID already exists.";
            }
        }
    }

    // If no errors, insert the course into the database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO courses (CourseID, CourseName, Description, Credits) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $errors[] = "Database prepare error: " . $conn->error;
        } else {
            $intCredits = (int)$credits; // Ensure credits is an integer
            $stmt->bind_param("sssi", $courseId, $courseName, $description, $intCredits);
            
            if ($stmt->execute()) {
                $success = "Course '{$courseName}' created successfully!";
                // Clear form fields on success
                $courseId = $courseName = $description = $credits = '';
            } else {
                $errors[] = "Failed to create course: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// For sidebar/navbar consistency
$current_page = 'create_course.php';
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create New Course - Eduor System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../dash.css"> <!-- Adjust path if necessary -->
</head>

<body>
    <!-- TOP NAVBAR -->
    <div class="navbar">
        <div class="navbar-left">
            <span class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></span>
            <img src="https://dummyimage.com/200x40/004080/ffffff&text=Eduor+System" alt="Eduor Logo" class="logo">
        </div>
    </div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="../admin/admin.php"><i class="fa fa-home"></i> Home</a></li>
            <li class="<?= ($current_page == 'create_course.php') ? 'active' : ''; ?>"><a href="create_course.php"><i class="fa fa-plus-circle"></i> Create New Course</a></li>
            <li><a href="logout.php"><i class="fa fa-power-off"></i> Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        <section class="max-w-xl mx-auto p-5">
            <h1 class="text-3xl font-bold mb-6 text-center">Create New Course</h1>
            
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

            <div class="p-8 bg-white rounded-lg shadow-md">
                <form id="createCourseForm" method="POST">
                    <div class="mb-4">
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-2">Course ID</label>
                        <input type="text" id="course_id" name="course_id" placeholder="e.g., CS101" class="input input-bordered w-full" value="<?= htmlspecialchars($courseId); ?>" required />
                    </div>

                    <div class="mb-4">
                        <label for="course_name" class="block text-sm font-medium text-gray-700 mb-2">Course Name</label>
                        <input type="text" id="course_name" name="course_name" placeholder="e.g., Introduction to Programming" class="input input-bordered w-full" value="<?= htmlspecialchars($courseName); ?>" required />
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="description" name="description" placeholder="A brief description of the course..." class="textarea textarea-bordered h-24 w-full" required><?= htmlspecialchars($description); ?></textarea>
                    </div>

                    <div class="mb-6">
                        <label for="credits" class="block text-sm font-medium text-gray-700 mb-2">Credits</label>
                        <input type="number" id="credits" name="credits" placeholder="e.g., 3" class="input input-bordered w-full" value="<?= htmlspecialchars($credits); ?>" required min="1" />
                    </div>
                    
                    <div>
                        <button type="button" onclick="showConfirmationModal()" class="btn btn-primary w-full">Create Course</button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <!-- Confirmation Modal -->
    <dialog id="confirmation_modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Confirm Course Creation</h3>
            <p class="py-4">Are you sure you want to create this course?</p>
            <div class="modal-action justify-end">
                <form method="dialog" class="flex gap-2">
                    <!-- This button closes the modal -->
                    <button class="btn">Cancel</button>
                    <!-- This button submits the form -->
                    <button class="btn btn-primary" onclick="document.getElementById('createCourseForm').submit()">Yes, Create</button>
                </form>
            </div>
        </div>
    </dialog>

    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("active");
            document.getElementById("main-content").classList.toggle("shift");
        }

        function showConfirmationModal() {
            document.getElementById('confirmation_modal').showModal();
        }
    </script>
</body>
</html>