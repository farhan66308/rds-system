<?php
// course_detail.php
session_start();
require_once '../conn.php';

// Check if user is logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];
$selectedCourseID = filter_input(INPUT_GET, 'courseID', FILTER_SANITIZE_STRING);
$selectedSection = filter_input(INPUT_GET, 'section', FILTER_SANITIZE_STRING);
$activeMenuItem = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_STRING) ?? 'Announcements'; // Default to Announcements

// Redirect if essential parameters are missing
if (empty($selectedCourseID) || empty($selectedSection)) {
    header("Location: course_selection.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$courseName = '';
$courseStructure = []; // Stores boolean flags (Syllabus, Assignments, etc.)
$dynamicContent = []; // Stores actual content from courseproperty (keyed by Type)
$error = null;

try {
    // 1. Verify user is actually enrolled in this course as a student
    $sql_check_enrollment = "SELECT c.CourseName 
                             FROM enrolled e
                             JOIN courses c ON e.CourseID = c.CourseID
                             WHERE e.UserID = ? AND e.CourseID = ? AND e.Section = ? AND e.Role = 'Student'";
    $stmt_check = $conn->prepare($sql_check_enrollment);
    if (!$stmt_check) {
        throw new Exception("Failed to prepare enrollment check statement: " . $conn->error);
    }
    // Assuming UserID is INT, CourseID and Section are STRING/VARCHAR
    $stmt_check->bind_param("iss", $userID, $selectedCourseID, $selectedSection);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows === 0) {
        throw new Exception("You are not authorized to view this course or section.");
    }
    $courseData = $result_check->fetch_assoc();
    $courseName = $courseData['CourseName'];
    $stmt_check->close();

    // 2. Get the specific StructureID for this CourseID and Section
    $sql_get_structure_id = "SELECT StructureID FROM CourseStructure WHERE CourseID = ? AND Section = ?";
    $stmt_get_structure_id = $conn->prepare($sql_get_structure_id);
    if (!$stmt_get_structure_id) {
        throw new Exception("Failed to prepare structure ID retrieval statement: " . $conn->error);
    }
    $stmt_get_structure_id->bind_param("si", $selectedCourseID, $selectedSection);
    $stmt_get_structure_id->execute();
    $result_structure_id = $stmt_get_structure_id->get_result();
    $structureIDRow = $result_structure_id->fetch_assoc();
    $stmt_get_structure_id->close();

    if (!$structureIDRow) {
        throw new Exception("Course structure definition not found for this course and section.");
    }
    $actualStructureID = $structureIDRow['StructureID'];

    // 3. Get the course structure flags (Syllabus, Assignments, etc.)
    $sql_structure_flags = "SELECT Syllabus, Assignments, Modules, Annoucements, Files, People, Grades, Discussions 
                             FROM CourseStructure WHERE StructureID = ?";
    $stmt_structure_flags = $conn->prepare($sql_structure_flags);
    if (!$stmt_structure_flags) {
        throw new Exception("Failed to prepare course structure flags statement: " . $conn->error);
    }
    $stmt_structure_flags->bind_param("s", $actualStructureID);
    $stmt_structure_flags->execute();
    $result_structure_flags = $stmt_structure_flags->get_result();
    $courseStructure = $result_structure_flags->fetch_assoc();
    $stmt_structure_flags->close();

    if (!$courseStructure) {
        throw new Exception("Course structure flags not found.");
    }

    // Convert '1'/'0' from DB to booleans for easier use
    foreach ($courseStructure as $key => $value) {
        $courseStructure[$key] = (bool)$value;
    }

    // 4. Get the dynamic content from the 'courseproperty' table
    $sql_course_property = "SELECT Type, Content FROM courseproperty WHERE StructureID = ?";
    $stmt_course_property = $conn->prepare($sql_course_property);
    if (!$stmt_course_property) {
        throw new Exception("Failed to prepare course property statement: " . $conn->error);
    }
    $stmt_course_property->bind_param("s", $actualStructureID);
    $stmt_course_property->execute();
    $result_course_property = $stmt_course_property->get_result();
    while ($row = $result_course_property->fetch_assoc()) {
        // Use the 'Type' as the key for the dynamic content array
        $dynamicContent[$row['Type']] = $row['Content'];
    }
    $stmt_course_property->close();

} catch (Exception $e) {
    error_log("Error in course_detail.php: " . $e->getMessage());
    $error = $e->getMessage();
} 

// Define menu items with icons (using Lucide SVGs directly)
$menuItems = [
    'Announcements' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-megaphone"><path d="M2.97 10C.9 8.64.9 6.27 2.97 4l12.14 8L2.97 10z"/><path d="M15 12c.74 1.39 1.95 2 3.14 2 1.34 0 2.5-.76 2.5-2.2 0-1.42-1.1-2.2-2.5-2.2-.66 0-1.4.12-2.1.42"/><path d="M7 17H4a2 2 0 0 1-2-2v-4a2 2 0 0 1 2-2h3"/><path d="M12 11h9.28a2 2 0 0 0 1.32-3.4l-.45-.45a2 2 0 0 0-3.41 1.32V11Z"/></svg>',
    'Syllabus' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-book-open"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
    'Modules' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-grid"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg>',
    'Assignments' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clipboard-list"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>',
    'Grades' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-award"><circle cx="12" cy="8" r="7"/><path d="M8.21 13.89 7 22l5-3 5 3-1.21-8.11"/><path d="M12 21.5V22"/></svg>',
    'Discussions' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-message-square"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
    'People' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-users"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    'Files' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-folder"><path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.24A2 2 0 0 0 4.07 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2z"/></svg>'
];

// Mapping for CourseStructure DB column names (handling the typo)
$dbColumnMap = [
    'Announcements' => 'Annoucements', // Database has 'Annoucements'
    'Syllabus' => 'Syllabus',
    'Modules' => 'Modules',
    'Assignments' => 'Assignments',
    'Grades' => 'Grades',
    'Discussions' => 'Discussions',
    'People' => 'People',
    'Files' => 'Files'
];
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($courseName); ?> - <?php echo htmlspecialchars($selectedCourseID); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }
        .main-content {
            min-height: calc(100vh - 4rem); /* Adjust based on header/footer if any */
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 p-4">
    <div class="max-w-7xl mx-auto bg-white rounded-lg shadow-xl overflow-hidden flex flex-col md:flex-row min-h-[calc(100vh-2rem)]">
        <aside class="w-full md:w-64 bg-gray-800 text-gray-100 p-4 md:flex-shrink-0 rounded-t-lg md:rounded-l-lg md:rounded-tr-none">
            <div class="flex items-center justify-between md:justify-start mb-6">
                <a href="viewcourse.php" class="btn btn-ghost text-gray-100 hover:bg-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-chevron-left"><path d="m15 18-6-6 6-6"/></svg>
                    Back to Courses
                </a>
            </div>
            <h2 class="text-2xl font-bold mb-4 px-2 text-blue-300">
                <?php echo htmlspecialchars($courseName); ?>
            </h2>
            <p class="text-sm text-gray-400 px-2 mb-6">
                <?php echo htmlspecialchars($selectedCourseID); ?> - <?php echo htmlspecialchars($selectedSection); ?>
            </p>

            <ul class="space-y-2">
                <?php if ($error): ?>
                    <li class="px-2 py-3 text-red-400">Error loading structure.</li>
                <?php else: ?>
                    <?php foreach ($menuItems as $label => $icon): ?>
                        <?php 
                        $dbColumnName = $dbColumnMap[$label] ?? $label; 
                        if (isset($courseStructure[$dbColumnName]) && $courseStructure[$dbColumnName]) :
                        ?>
                            <li>
                                <a href="?courseID=<?php echo urlencode($selectedCourseID); ?>&section=<?php echo urlencode($selectedSection); ?>&page=<?php echo urlencode($label); ?>" 
                                   class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-700 transition-colors duration-200 
                                   <?php echo ($activeMenuItem === $label) ? 'bg-blue-600 text-white shadow-md' : 'text-gray-200'; ?>">
                                    <?php echo $icon; ?>
                                    <span class="font-medium text-lg"><?php echo htmlspecialchars($label); ?></span>
                                </a>
                            </li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </aside>

        <main class="flex-grow p-6 md:p-8 main-content">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php else: ?>
                <h1 class="text-3xl font-bold mb-6 text-gray-800">
                    <?php echo htmlspecialchars($activeMenuItem); ?>
                </h1>
                <div class="prose max-w-none">
                    <?php 
                    $currentSectionDbName = $dbColumnMap[$activeMenuItem] ?? $activeMenuItem;

                    if (isset($courseStructure[$currentSectionDbName]) && $courseStructure[$currentSectionDbName]) {
                        // People section logic
                        if ($activeMenuItem === 'People') {
                            $db = new Database();
                            $conn = $db->getConnection();
                            $sql_people = "SELECT si.FirstName, si.LastName, e.Role
                                           FROM enrolled e
                                           JOIN studentinfo si ON e.UserID = si.UserID
                                           WHERE e.CourseID = ? AND e.Section = ?
                                           ORDER BY e.Role, si.LastName, si.FirstName";
                            
                            $stmt_people = $conn->prepare($sql_people);
                            if (!$stmt_people) {
                                echo "<p class='text-red-500'>Error preparing people list statement: " . $conn->error . "</p>";
                            } else {
                                $stmt_people->bind_param("ss", $selectedCourseID, $selectedSection);
                                $stmt_people->execute();
                                $result_people = $stmt_people->get_result();
                                
                                if ($result_people->num_rows > 0) {
                                    $currentRole = '';
                                    echo '<div class="space-y-4">';
                                    while ($person = $result_people->fetch_assoc()) {
                                        if ($person['Role'] !== $currentRole) {
                                            if ($currentRole !== '') {
                                                echo '</div>'; // Close previous list
                                            }
                                            $currentRole = $person['Role'];
                                            echo '<h3 class="text-2xl font-semibold mt-6 mb-2 capitalize">' . htmlspecialchars($currentRole) . 's</h3>';
                                            echo '<div class="bg-gray-100 rounded-lg p-4 shadow-sm">';
                                        }
                                        echo '<div class="flex items-center space-x-3 p-2 hover:bg-gray-200 rounded-md transition-colors">';
                                        echo '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user text-gray-600"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
                                        echo '<span class="text-lg font-medium">' . htmlspecialchars($person['FirstName']) . ' ' . htmlspecialchars($person['LastName']) . '</span>';
                                        echo '</div>';
                                    }
                                    echo '</div>'; // Close the last list
                                } else {
                                    echo "<p>No one is currently enrolled in this course.</p>";
                                }
                                $stmt_people->close();
                            }
                            $conn->close();
                        } else {
                            $content = $dynamicContent[$activeMenuItem] ?? '';
                            if ($activeMenuItem === 'Modules') {
                                if (!empty($content)) {
                                    // Split the string by multiple potential separators: comma, semicolon, or space
                                    $separators = ['/', ';', ' ']; // Common separators
                                    $files = [];
                                    $current = $content;
                                    
                                    // Use a loop to replace all separators with a single, consistent one (e.g., ',')
                                    foreach ($separators as $separator) {
                                        $current = str_replace($separator, ',', $current);
                                    }
                                    
                                    // Now explode with the consistent separator
                                    $filePaths = array_filter(explode(',', $current)); // array_filter removes empty elements

                                    echo '<div class="space-y-4">';
                                    $lectureNumber = 1;
                                    foreach ($filePaths as $path) {
                                        $path = trim($path);
                                        if (!empty($path)) {
                                            $fileName = 'Lecture #' . $lectureNumber . ' (' . strtoupper(pathinfo($path, PATHINFO_EXTENSION)) . ')';
                                            $fullPath = '../faculty' . $path; // Adjust path to point to the correct location
                                            echo '<div class="bg-gray-100 p-4 rounded-lg shadow-sm flex justify-between items-center">';
                                            echo '<span class="font-medium text-lg">' . htmlspecialchars($fileName) . '</span>';
                                            echo '<a href="' . htmlspecialchars($fullPath) . '" class="btn btn-primary btn-sm" download>Download</a>';
                                            echo '</div>';
                                            $lectureNumber++;
                                        }
                                    }
                                    echo '</div>';
                                } else {
                                    echo "<p>No modules have been uploaded for this course yet.</p>";
                                }
                            } elseif ($activeMenuItem === 'Assignments') {
                                // Assignment content is now JSON
                                $content_data = json_decode($content, true);
                                if ($content_data && isset($content_data['title']) && isset($content_data['content'])) {
                                    echo '<h2 class="text-2xl font-bold mb-4">' . htmlspecialchars($content_data['title']) . '</h2>';
                                    echo '<div class="prose max-w-none p-4 bg-gray-50 rounded-lg shadow-sm">' . nl2br(htmlspecialchars($content_data['content'])) . '</div>';
                                } else {
                                    echo "<p>No assignments have been posted yet.</p>";
                                }
                            } elseif (!empty($content)) {
                                echo $content;
                            } else {
                                echo "<p>Content for this section is enabled but not yet defined by the faculty member.</p>";
                            }
                        }
                    } else {
                        echo "<p>The <strong>" . htmlspecialchars($activeMenuItem) . "</strong> section is not enabled for this course structure.</p>";
                    }
                    ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>

<?php
// Close connection at the very end
$conn->close();
?>