<?php
// view_course_layout.php
session_start();

// For sidebar/navbar consistency
$current_page = 'view_course_layout.php';
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>View Course Layout - Eduor System</title>
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
            <li><a href="create_course_structure.php"><i class="fa fa-book"></i> Create Course Structure</a></li>
            <li class="<?= ($current_page == 'view_course_layout.php') ? 'active' : ''; ?>"><a href="view_course_layout.php"><i class="fa fa-eye"></i> View Course Layout</a></li>
            <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fa fa-power-off"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content" id="main-content">
        <section class="max-w-xl mx-auto p-5">
            <h1 class="text-3xl font-bold mb-6 text-center">View Specific Course Layout</h1>
            <div class="p-8 bg-white rounded-lg shadow-md">
                <form method="GET" action="preview.php">
                    <div class="mb-4">
                        <label for="course_id" class="block text-sm font-medium text-gray-700 mb-2">Course ID (e.g., CS101)</label>
                        <input type="text" id="course_id" name="course_id" placeholder="Enter Course ID" class="input input-bordered w-full" required />
                    </div>

                    <div class="mb-6">
                        <label for="section_number" class="block text-sm font-medium text-gray-700 mb-2">Section Number</label>
                        <input type="number" id="section_number" name="section_number" placeholder="Enter Section Number" class="input input-bordered w-full" required min="1" />
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary w-full">Go to Preview</button>
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