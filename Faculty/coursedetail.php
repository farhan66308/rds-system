<?php
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
$courseStructure = []; 
$dynamicContent = [];
$error = null;
$successMessage = null;
$actualStructureID = null;

try {
    // 1. Verify user is a faculty member
    $sql_check_enrollment = "SELECT c.CourseName 
                             FROM enrolled e
                             JOIN courses c ON e.CourseID = c.CourseID
                             WHERE e.UserID = ? AND e.CourseID = ? AND e.Section = ? AND e.Role = 'Faculty'";
    $stmt_check = $conn->prepare($sql_check_enrollment);
    if (!$stmt_check) {
        throw new Exception("Failed to prepare enrollment check statement: " . $conn->error);
    }
    $stmt_check->bind_param("iss", $userID, $selectedCourseID, $selectedSection);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows === 0) {
        throw new Exception("You are not authorized to view this course as a faculty member.");
    }
    $courseData = $result_check->fetch_assoc();
    $courseName = $courseData['CourseName'];
    $stmt_check->close();

    // 2. Get the specific StructureID for this CourseID and Section
    $sql_get_structure_id = "SELECT StructureID FROM CourseStructure WHERE CourseID = ? AND Section = ?";
    $stmt_get_structure_id = $conn->prepare($sql_get_structure_id);
    if (!$stmt_get_structure_id) {
        throw new Exception("Failed to prepare structure ID retrieval statement.");
    }
    $stmt_get_structure_id->bind_param("ss", $selectedCourseID, $selectedSection);
    $stmt_get_structure_id->execute();
    $result_structure_id = $stmt_get_structure_id->get_result();
    $structureIDRow = $result_structure_id->fetch_assoc();
    $stmt_get_structure_id->close();

    if (!$structureIDRow) {
        throw new Exception("Course structure definition not found for this course and section.");
    }
    $actualStructureID = $structureIDRow['StructureID'];

    // 3. Get the course structure flags
    $sql_structure_flags = "SELECT Syllabus, Assignments, Modules, Annoucements, Files, People, Grades, Discussions 
                             FROM CourseStructure WHERE StructureID = ?";
    $stmt_structure_flags = $conn->prepare($sql_structure_flags);
    if (!$stmt_structure_flags) {
        throw new Exception("Failed to prepare course structure flags statement.");
    }
    $stmt_structure_flags->bind_param("s", $actualStructureID);
    $stmt_structure_flags->execute();
    $result_structure_flags = $stmt_structure_flags->get_result();
    $courseStructure = $result_structure_flags->fetch_assoc();
    $stmt_structure_flags->close();

    if (!$courseStructure) {
        throw new Exception("Course structure flags not found.");
    }

    foreach ($courseStructure as $key => $value) {
        $courseStructure[$key] = (bool)$value;
    }

    // 4. Get the dynamic content
    $sql_course_property = "SELECT Type, Content FROM courseproperty WHERE StructureID = ?";
    $stmt_course_property = $conn->prepare($sql_course_property);
    if (!$stmt_course_property) {
        throw new Exception("Failed to prepare course property statement.");
    }
    $stmt_course_property->bind_param("s", $actualStructureID);
    $stmt_course_property->execute();
    $result_course_property = $stmt_course_property->get_result();
    while ($row = $result_course_property->fetch_assoc()) {
        $dynamicContent[$row['Type']] = $row['Content'];
    }
    $stmt_course_property->close();

    // Handle form submissions for updating content
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_content'])) {
        $contentToUpdate = $_POST['content'] ?? '';
        $titleToUpdate = $_POST['title'] ?? null;
        $sectionToUpdate = $_POST['section_type'];
        $currentContentExists = isset($dynamicContent[$sectionToUpdate]);

        if ($sectionToUpdate === 'Modules' && isset($_FILES['module_file']) && $_FILES['module_file']['error'] == 0) {
            $upload_dir = __DIR__ . '../resource/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $fileName = basename($_FILES['module_file']['name']);
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $uniqueFileName = uniqid() . '.' . $fileExtension;
            $uploadFilePath = $upload_dir . $uniqueFileName;
            $fileUrl = '/resource/' . $uniqueFileName;
            
            if (move_uploaded_file($_FILES['module_file']['tmp_name'], $uploadFilePath)) {
                $contentToUpdate = $fileUrl;
                $successMessage = "File uploaded and module content updated successfully! ✨";
            } else {
                throw new Exception("Failed to upload file. Please check file permissions.");
            }
        } elseif ($sectionToUpdate !== 'Modules') {
            $successMessage = "Content updated successfully! 👍";
        }

        if ($currentContentExists) {
            if ($sectionToUpdate === 'Assignments') {
                $content_json = json_encode(['title' => $titleToUpdate, 'content' => $contentToUpdate]);
                $sql_update = "UPDATE courseproperty SET Content = ? WHERE StructureID = ? AND Type = ?";
                $stmt_update = $conn->prepare($sql_update);
                if (!$stmt_update) throw new Exception("Failed to prepare update statement.");
                $stmt_update->bind_param("sss", $content_json, $actualStructureID, $sectionToUpdate);
                $stmt_update->execute();
                $stmt_update->close();
            } else {
                $sql_update = "UPDATE courseproperty SET Content = ? WHERE StructureID = ? AND Type = ?";
                $stmt_update = $conn->prepare($sql_update);
                if (!$stmt_update) throw new Exception("Failed to prepare update statement.");
                $stmt_update->bind_param("sss", $contentToUpdate, $actualStructureID, $sectionToUpdate);
                $stmt_update->execute();
                $stmt_update->close();
            }
        } else {
            if ($sectionToUpdate === 'Assignments') {
                $content_json = json_encode(['title' => $titleToUpdate, 'content' => $contentToUpdate]);
                $sql_insert = "INSERT INTO courseproperty (StructureID, Type, Content) VALUES (?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                if (!$stmt_insert) throw new Exception("Failed to prepare insert statement.");
                $stmt_insert->bind_param("sss", $actualStructureID, $sectionToUpdate, $content_json);
                $stmt_insert->execute();
                $stmt_insert->close();
            } else {
                $sql_insert = "INSERT INTO courseproperty (StructureID, Type, Content) VALUES (?, ?, ?)";
                $stmt_insert = $conn->prepare($sql_insert);
                if (!$stmt_insert) throw new Exception("Failed to prepare insert statement.");
                $stmt_insert->bind_param("sss", $actualStructureID, $sectionToUpdate, $contentToUpdate);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        
        header("Location: coursedetail.php?courseID=" . urlencode($selectedCourseID) . "&section=" . urlencode($selectedSection) . "&page=" . urlencode($activeMenuItem) . "&success=" . urlencode($successMessage));
        exit();
    }

    if (isset($_GET['success'])) {
        $successMessage = htmlspecialchars($_GET['success']);
    }

} catch (Exception $e) {
    error_log("Error in coursedetail.php: " . $e->getMessage());
    $error = $e->getMessage();
}

$conn->close();

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

$dbColumnMap = [
    'Announcements' => 'Annoucements',
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
            min-height: calc(100vh - 4rem);
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
                
                <?php if ($successMessage): ?>
                    <div role="alert" class="alert alert-success mb-4">
                      <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                      <span><?php echo $successMessage; ?></span>
                    </div>
                <?php endif; ?>

                <div class="prose max-w-none">
                    <?php
                    $dbColumnName = $dbColumnMap[$activeMenuItem] ?? $activeMenuItem;

                    if (isset($courseStructure[$dbColumnName]) && $courseStructure[$dbColumnName]) {
                        if ($activeMenuItem === 'People') {
                            $db = new Database();
                            $conn = $db->getConnection();
                            $sql_people = "SELECT si.FirstName, si.LastName, e.Role
                                           FROM enrolled e
                                           JOIN studentinfo si ON e.UserID = si.UserID
                                           WHERE e.CourseID = ? AND e.Section = ? AND e.Role IN ('Student', 'TA')
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
                                                echo '</div>';
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
                                    echo '</div>';
                                } else {
                                    echo "<p>No one is currently enrolled in this course.</p>";
                                }
                                $stmt_people->close();
                            }
                            $conn->close();
                        } else {
                            $content = $dynamicContent[$activeMenuItem] ?? '';
                            
                            echo '<div class="card bg-gray-100 p-6 rounded-lg shadow-md mb-6">';
                            echo '<h3 class="text-xl font-semibold mb-4 text-gray-700">Edit ' . htmlspecialchars($activeMenuItem) . '</h3>';
                            
                            if ($activeMenuItem === 'Modules') {
                                echo '<form method="post" enctype="multipart/form-data" action="coursedetail.php?courseID=' . urlencode($selectedCourseID) . '&section=' . urlencode($selectedSection) . '&page=' . urlencode($activeMenuItem) . '">';
                                echo '<input type="hidden" name="section_type" value="' . htmlspecialchars($activeMenuItem) . '">';
                                echo '<input type="hidden" name="structureID" value="' . htmlspecialchars($actualStructureID) . '">';
                                echo '<div class="form-control w-full max-w-xs">';
                                echo '<label class="label"><span class="label-text">Upload a new file:</span></label>';
                                echo '<input type="file" name="module_file" class="file-input file-input-bordered w-full max-w-xs" required />';
                                echo '</div>';
                                echo '<button type="submit" name="update_content" class="btn btn-primary mt-4">Upload File</button>';
                                echo '</form>';
                            } elseif ($activeMenuItem === 'Assignments') {
                                // Assignment content is now JSON
                                $content_data = json_decode($content, true);
                                $assignment_title = $content_data['title'] ?? '';
                                $assignment_content = $content_data['content'] ?? '';

                                echo '<form method="post" action="coursedetail.php?courseID=' . urlencode($selectedCourseID) . '&section=' . urlencode($selectedSection) . '&page=' . urlencode($activeMenuItem) . '">';
                                echo '<input type="hidden" name="section_type" value="' . htmlspecialchars($activeMenuItem) . '">';
                                echo '<input type="hidden" name="structureID" value="' . htmlspecialchars($actualStructureID) . '">';
                                
                                echo '<div class="form-control w-full mb-4">';
                                echo '<label class="label"><span class="label-text">Assignment Title:</span></label>';
                                echo '<input type="text" name="title" class="input input-bordered w-full" placeholder="e.g., Homework 1" value="' . htmlspecialchars($assignment_title) . '" required />';
                                echo '</div>';

                                echo '<div class="form-control w-full">';
                                echo '<label class="label"><span class="label-text">Assignment Content:</span></label>';
                                echo '<textarea name="content" class="textarea textarea-bordered w-full h-48 mb-4" placeholder="Add assignment details here...">' . htmlspecialchars($assignment_content) . '</textarea>';
                                echo '</div>';

                                echo '<button type="submit" name="update_content" class="btn btn-primary">Update Assignment</button>';
                                echo '</form>';
                            } else {
                                echo '<form method="post" action="coursedetail.php?courseID=' . urlencode($selectedCourseID) . '&section=' . urlencode($selectedSection) . '&page=' . urlencode($activeMenuItem) . '">';
                                echo '<input type="hidden" name="section_type" value="' . htmlspecialchars($activeMenuItem) . '">';
                                echo '<input type="hidden" name="structureID" value="' . htmlspecialchars($actualStructureID) . '">';
                                echo '<textarea name="content" class="textarea textarea-bordered w-full h-48 mb-4" placeholder="Add your content here...">' . htmlspecialchars($content) . '</textarea>';
                                echo '<button type="submit" name="update_content" class="btn btn-primary">Update Content</button>';
                                echo '</form>';
                            }
                            echo '</div>';

                            echo '<h2 class="text-2xl font-bold mt-8 mb-4">Current ' . htmlspecialchars($activeMenuItem) . ' Content</h2>';
                            if ($activeMenuItem === 'Modules' && !empty($content)) {
                                echo '<a href="' . htmlspecialchars($content) . '" class="link link-primary" target="_blank">View Uploaded File</a>';
                            } elseif ($activeMenuItem === 'Assignments' && !empty($content_data)) {
                                echo '<h3 class="text-xl font-bold">' . htmlspecialchars($content_data['title']) . '</h3>';
                                echo '<div class="mt-2 p-4 border rounded-md">' . nl2br(htmlspecialchars($content_data['content'])) . '</div>';
                            } elseif (!empty($content)) {
                                echo $content;
                            } else {
                                echo "<p>Content for this section is enabled but not yet defined in the database.</p>";
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