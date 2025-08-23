<?php
// preview.php
session_start();
require_once '../conn.php'; // Adjust path if necessary
require_once 'Course.php'; // Path to your Course.php
require_once 'CourseBuilder.php'; // Path to your CourseBuilder.php
require_once 'concretebuilder.php'; // Path to your FullCourseBuilder.php
require_once 'CourseDirector.php'; // Path to your CourseDirector.php

$db = new Database();
$conn = $db->getConnection();

$courseId = $_GET['course_id'] ?? null;
$sectionNumber = $_GET['section_number'] ?? null;
$loadedCourse = null;
$errors = [];

if (empty($courseId) || empty($sectionNumber)) {
    $errors[] = "Course ID and Section Number are required for preview.";
} elseif (!is_numeric($sectionNumber) || (int)$sectionNumber <= 0) {
    $errors[] = "Invalid Section Number.";
} else {
    try {
        $builder = new FullCourseBuilder();
        $director = new CourseDirector();
        $loadedCourse = $director->loadCourseFromDatabase($builder, $conn, $courseId, (int)$sectionNumber);
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

// Map section names to Font Awesome icons for a nicer UI
$sectionIcons = [
    'Syllabus' => 'fa-book',
    'Assignments' => 'fa-clipboard-list',
    'Modules' => 'fa-cubes',
    'Announcements' => 'fa-bullhorn',
    'Files' => 'fa-folder',
    'People' => 'fa-users',
    'Grades' => 'fa-graduation-cap',
    'Discussion' => 'fa-comments',
];

// For sidebar/navbar consistency
$current_page = 'preview.php';
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Course Preview: <?= htmlspecialchars($courseId ?? ''); ?> - Eduor System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../dash.css"> <!-- Adjust path if necessary -->
    <style>
        .course-section-card {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-radius: 0.5rem;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: all 0.2s ease-in-out;
        }
        .course-section-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            background-color: #f9fafb;
        }
        .course-section-card .icon {
            font-size: 1.5rem;
            color: #4A90E2; /* A nice blue */
            margin-right: 1rem;
        }
        .course-section-card .title {
            font-weight: 600;
            font-size: 1.125rem;
            color: #333;
        }
        .course-content {
            background-color: #fff;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>
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
            <li><a href="create_course_structure.php"><i class="fa fa-book"></i> Create Course Structure</a></li>
            <li><a href="view_course_layout.php"><i class="fa fa-eye"></i> View Course Layout</a></li>
            <li class="<?= ($current_page == 'preview.php') ? 'active' : ''; ?>"><a href="preview.php"><i class="fa fa-magnifying-glass"></i> Course Preview</a></li>
            <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fa fa-power-off"></i> Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        <section class="max-w-4xl mx-auto p-5">
            <h1 class="text-3xl font-bold mb-6 text-center">Course Preview: <?= htmlspecialchars($courseId ?? 'N/A'); ?> (Section <?= htmlspecialchars($sectionNumber ?? 'N/A'); ?>)</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error mb-4">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($loadedCourse): ?>
                <div class="flex flex-col lg:flex-row gap-6">
                    <!-- Left Sidebar for Sections -->
                    <div class="lg:w-1/4 space-y-3 p-4 bg-gray-50 rounded-lg shadow-sm">
                        <h2 class="text-xl font-semibold mb-4 text-gray-700">Course Sections</h2>
                        <?php 
                        $sectionsToDisplay = [
                            'Syllabus' => $loadedCourse->getSyllabus(),
                            'Assignments' => $loadedCourse->getAssignments(),
                            'Modules' => $loadedCourse->getModules(),
                            'Announcements' => $loadedCourse->getAnnouncements(),
                            'Files' => $loadedCourse->getFiles(),
                            'People' => $loadedCourse->getPeople(),
                            'Grades' => $loadedCourse->getGrades(),
                            'Discussion' => $loadedCourse->getDiscussion(),
                        ];

                        foreach ($sectionsToDisplay as $name => $isEnabled) {
                            if ($isEnabled) {
                                $iconClass = $sectionIcons[$name] ?? 'fa-circle-question';
                                echo "<a href=\"#{$name}\" class=\"course-section-card\">";
                                echo "<i class=\"fa {$iconClass} icon\"></i>";
                                echo "<span class=\"title\">{$name}</span>";
                                echo "</a>";
                            }
                        }
                        ?>
                    </div>

                    <!-- Main Content Area -->
                    <div class="lg:w-3/4 space-y-6">
                        <?php if ($loadedCourse->getSyllabus()): ?>
                            <div id="Syllabus" class="course-content">
                                <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fa fa-book text-primary mr-2"></i>Syllabus</h2>
                                <p class="text-gray-700">This is where the course syllabus content would be displayed. It includes course description, learning objectives, grading criteria, and policies.</p>
                                <p class="text-gray-500 text-sm mt-2">*(Content for Syllabus goes here)*</p>
                            </div>
                        <?php endif; ?>

                        <?php if ($loadedCourse->getAssignments()): ?>
                            <div id="Assignments" class="course-content">
                                <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fa fa-clipboard-list text-primary mr-2"></i>Assignments</h2>
                                <p class="text-gray-700">Here you will find all the assignments for this course, including due dates and submission links.</p>
                                <p class="text-gray-500 text-sm mt-2">*(Content for Assignments goes here)*</p>
                            </div>
                        <?php endif; ?>

                        <?php if ($loadedCourse->getModules()): ?>
                            <div id="Modules" class="course-content">
                                <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fa fa-cubes text-primary mr-2"></i>Modules</h2>
                                <p class="text-gray-700">Organized learning units with readings, videos, and activities.</p>
                                <p class="text-gray-500 text-sm mt-2">*(Content for Modules goes here)*</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($loadedCourse->getAnnouncements()): ?>
                            <div id="Announcements" class="course-content">
                                <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fa fa-bullhorn text-primary mr-2"></i>Announcements</h2>
                                <p class="text-gray-700">Stay up-to-date with important course news and updates from your instructor.</p>
                                <p class="text-gray-500 text-sm mt-2">*(Content for Announcements goes here)*</p>
                            </div>
                        <?php endif; ?>

                        <?php if ($loadedCourse->getFiles()): ?>
                            <div id="Files" class="course-content">
                                <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fa fa-folder text-primary mr-2"></i>Files</h2>
                                <p class="text-gray-700">Access all course documents, presentations, and resources.</p>
                                <p class="text-gray-500 text-sm mt-2">*(Content for Files goes here)*</p>
                            </div>
                        <?php endif; ?>

                        <?php if ($loadedCourse->getPeople()): ?>
                            <div id="People" class="course-content">
                                <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fa fa-users text-primary mr-2"></i>People</h2>
                                <p class="text-gray-700">View your classmates and instructor contact information.</p>
                                <p class="text-gray-500 text-sm mt-2">*(Content for People goes here)*</p>
                            </div>
                        <?php endif; ?>

                        <?php if ($loadedCourse->getGrades()): ?>
                            <div id="Grades" class="course-content">
                                <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fa fa-graduation-cap text-primary mr-2"></i>Grades</h2>
                                <p class="text-gray-700">Check your current grades and feedback for all course submissions.</p>
                                <p class="text-gray-500 text-sm mt-2">*(Content for Grades goes here)*</p>
                            </div>
                        <?php endif; ?>

                        <?php if ($loadedCourse->getDiscussion()): ?>
                            <div id="Discussion" class="course-content">
                                <h2 class="text-2xl font-bold text-gray-800 mb-4"><i class="fa fa-comments text-primary mr-2"></i>Discussion</h2>
                                <p class="text-gray-700">Engage in discussions with your peers and instructor.</p>
                                <p class="text-gray-500 text-sm mt-2">*(Content for Discussion goes here)*</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif (empty($errors)): ?>
                <div class="alert alert-info">
                    <p>No course layout loaded. Select a course from the previous page.</p>
                    <a href="view_course_layout.php" class="btn btn-sm btn-primary mt-4">Go Back to Course Selection</a>
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