<?php
// create_course_structure.php
session_start();
require_once '../conn.php'; 
require_once 'Course.php'; 
require_once 'CourseBuilder.php'; 
require_once 'concretebuilder.php'; 

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = '';
$courseIdInput = ''; 
$sectionInput = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseIdInput = trim($_POST['course_id'] ?? '');
    $sectionInput = trim($_POST['section_number'] ?? '');
    $selectedSections = $_POST['sections'] ?? [];

    if (empty($courseIdInput)) {
        $errors[] = "Course ID is required.";
    }

    if (empty($sectionInput) || !is_numeric($sectionInput) || (int)$sectionInput <= 0) {
        $errors[] = "Section must be a positive number.";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM courses WHERE CourseID = ?");
            $stmt->bind_param("s", $courseIdInput);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();

            if ($count === 0) {
                $errors[] = "The provided Course ID does not exist. Please create the course first.";
            } else {
                $builder = new FullCourseBuilder();

                // Build the course based on selected sections
                if (in_array('syllabus', $selectedSections)) $builder->addSyllabus();
                if (in_array('assignments', $selectedSections)) $builder->addAssignments();
                if (in_array('modules', $selectedSections)) $builder->addModules();
                if (in_array('announcements', $selectedSections)) $builder->addAnnouncements();
                if (in_array('files', $selectedSections)) $builder->addFiles();
                if (in_array('people', $selectedSections)) $builder->addPeople();
                if (in_array('grades', $selectedSections)) $builder->addGrades();
                if (in_array('discussion', $selectedSections)) $builder->addDiscussion();

                // Save the built course structure to the database
                if ($builder->saveToDatabase($conn, $courseIdInput, $sectionInput)) {
                    $success = "Course structure for '{$courseIdInput}' section '{$sectionInput}' saved successfully!";
                    $courseIdInput = ''; // Clear input field on success
                    $sectionInput = ''; // Clear section input field on success
                } else {
                    $errors[] = "Failed to save course structure to the database. It might already exist.";
                }
            }
        } catch (Exception $e) {
            $errors[] = "An error occurred: " . $e->getMessage();
        }
    }
}

// All available sections for the checkboxes
$allSections = [
    'syllabus' => 'Syllabus',
    'assignments' => 'Assignments',
    'modules' => 'Modules',
    'announcements' => 'Announcements',
    'files' => 'Files',
    'people' => 'People',
    'grades' => 'Grades',
    'discussion' => 'Discussion',
];

// For sidebar/navbar consistency (assuming you have these CSS classes)
$current_page = 'create_course_structure.php';
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Course Structure - Eduor System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../dash.css"> </head>

<body>
    <div class="navbar">
        <div class="navbar-left">
            <span class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></span>
            <img src="https://dummyimage.com/200x40/004080/ffffff&text=Eduor+System" alt="Eduor Logo" class="logo">
        </div>
    </div>

    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="../admin/admin.php"><i class="fa fa-home"></i> Home</a></li>
            <li class="<?= ($current_page == 'create_course_structure.php') ? 'active' : ''; ?>"><a href="create_course_structure.php"><i class="fa fa-book"></i> Create Course Structure</a></li>
            <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fa fa-power-off"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content" id="main-content">
        <section class="max-w-xl mx-auto p-5">
            <h1 class="text-3xl font-bold mb-6 text-center">Define Course Structure</h1>
            
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
                <form method="POST">
                    <div class="mb-4">
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-2">Course ID (e.g., CS101)</label>
                        <input type="text" id="course_id" name="course_id" placeholder="Enter Course ID" class="input input-bordered w-full" value="<?= htmlspecialchars($courseIdInput); ?>" required />
                        <p class="text-xs text-gray-500 mt-1">Make sure this Course ID already exists in your `courses` table.</p>
                    </div>

                    <div class="mb-4">
                        <label for="section_number" class="block text-sm font-medium text-gray-700 mb-2">Section Number</label>
                        <input type="number" id="section_number" name="section_number" placeholder="Enter Section Number" class="input input-bordered w-full" value="<?= htmlspecialchars($sectionInput); ?>" required min="1" />
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Course Sections:</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <?php foreach ($allSections as $value => $label): ?>
                                <div class="form-control">
                                    <label class="label cursor-pointer">
                                        <span class="label-text"><?= htmlspecialchars($label); ?></span> 
                                        <input type="checkbox" name="sections[]" value="<?= htmlspecialchars($value); ?>" class="checkbox checkbox-primary" 
                                            <?php 
                                            // Check if the form was submitted and this section was selected
                                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($value, $selectedSections)) {
                                                echo 'checked';
                                            }
                                            ?>
                                        />
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary w-full">Save Course Structure</button>
                    </div>
                </form>
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