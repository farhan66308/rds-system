<?php
require_once 'conn.php';
$db = new Database();
$conn = $db->getConnection();
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

// Fetch FirstName and LastName from DB
$sql = "SELECT s.FirstName, s.LastName
        FROM users u
        JOIN studentinfo s ON s.UserID = u.UserID
        WHERE u.UserID = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$fullname = ($row && ($row['FirstName'] || $row['LastName']))
    ? htmlspecialchars(trim($row['FirstName'] . ' ' . $row['LastName']))
    : 'User';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>RDS Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <style>
        /* Enhanced submenu styling */
        .courses-menu-wrapper {
            position: relative;
            cursor: pointer;
        }
        .courses-menu {
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%) scale(0.9);
            margin-top: 12px;
            width: 220px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease, transform 0.25s ease;
            z-index: 100;
            font-size: 16px;
            font-weight: 600;
            color: #2f855a; /* Tailwind green-600 */
            user-select: none;
        }
        .courses-menu-wrapper:hover .courses-menu {
            opacity: 1;
            pointer-events: auto;
            transform: translateX(-50%) scale(1);
        }
        .courses-menu ul {
            list-style: none;
            padding: 10px 0;
            margin: 0;
        }
        .courses-menu li {
            padding: 12px 24px;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #e2e8f0; /* Tailwind gray-300 */
        }
        .courses-menu li:last-child {
            border-bottom: none;
        }
        .courses-menu li:hover {
            background-color: #c6f6d5; /* Tailwind green-200 */
            color: #276749; /* Tailwind green-700 */
            cursor: pointer;
        }
        .courses-menu li a {
            text-decoration: none;
            color: inherit;
            display: block;
            width: 100%;
        }
        /* Small arrow above submenu */
        .courses-menu::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-bottom: 10px solid #fff;
            filter: drop-shadow(0 2px 2px rgba(0,0,0,0.05));
            z-index: 101;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 to-yellow-200">

<section>
    <div class="navbar bg-base-100 h-[100px] bg-slate-300 m-3 rounded-lg shadow-md">
        <div class="navbar-start">
            <div class="dropdown">
                <div tabindex="0" role="button" class="btn btn-ghost btn-circle" aria-label="Menu toggle">
                    <i class="fas fa-bars text-xl"></i>
                </div>
                <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-52" aria-label="Main menu">
                    <li><a href="#">Home</a></li>
                    <li><a href="settings.php">Settings</a></li>
                </ul>
            </div>
        </div>
        <div class="navbar-center">
            <a class="btn btn-ghost text-2xl font-bold" href="#">Eduor System</a>
        </div>
        <div class="navbar-end flex items-center">
            <span class="mr-4">Welcome, <?php echo $fullname; ?></span>
            <button class="btn btn-error text-white mr-4" onclick="logout()">Logout</button>
        </div>
    </div>
</section>

<section>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 m-5">

        <a href="profile.php" class="card bg-white hover:bg-blue-50 transition-all shadow-md rounded-lg hover:shadow-xl transform hover:scale-105">
            <div class="card-body items-center text-center">
                <i class="fas fa-info-circle text-4xl text-blue-500 mb-3"></i>
                <h2 class="text-xl font-semibold">Info</h2>
            </div>
        </a>

        <div class="courses-menu-wrapper group">
            <a href="courses.html" class="card bg-white hover:bg-green-50 transition-all shadow-md rounded-lg hover:shadow-xl transform hover:scale-105">
                <div class="card-body items-center text-center flex justify-center space-x-2">
                    <i class="fas fa-book-open text-4xl text-green-500 mb-3"></i>
                    <h2 class="text-xl font-semibold">Courses</h2>
                    <i class="fa-solid fa-angle-down self-center"></i>
                </div>
            </a>

            <nav class="courses-menu" aria-label="Courses submenu">
                <ul>
                    <li><a href="manage_courses.php">Manage</a></li>
                    <li><a href="advising.php">Advising</a></li>
                    <li><a href="pre_advise.php">Pre Advise</a></li>
                </ul>
            </nav>
        </div>

        <a href="learning.html" class="card bg-white hover:bg-yellow-50 transition-all shadow-md rounded-lg hover:shadow-xl transform hover:scale-105">
            <div class="card-body items-center text-center">
                <i class="fas fa-laptop-code text-4xl text-yellow-500 mb-3"></i>
                <h2 class="text-xl font-semibold">Learning</h2>
            </div>
        </a>

        <div class="relative group">
            <a href="announcement.html"
                class="card bg-white hover:bg-indigo-50 transition-all shadow-md rounded-lg hover:shadow-xl transform hover:scale-105 block">
                <div class="card-body items-center text-center">
                    <i class="fas fa-bullhorn text-4xl text-indigo-500 mb-3"></i>
                    <h2 class="text-xl font-semibold">Announcements</h2>
                </div>
            </a>

            <div
                class="absolute left-1/2 -translate-x-1/2 mt-2 w-48 bg-white rounded-lg shadow-lg opacity-0 group-hover:opacity-100 pointer-events-none group-hover:pointer-events-auto transition-opacity z-20"
            >
                <a href="show-announcement.php"
                    class="block px-4 py-2 hover:bg-indigo-100 text-indigo-700 font-semibold rounded-t-lg text-center cursor-pointer">
                    Show Announcement
                </a>
                <a href="create-announce.php"
                    class="block px-4 py-2 hover:bg-indigo-100 text-indigo-700 font-semibold text-center cursor-pointer">
                    Create Announcement
                </a>
            </div>
        </div>

        <a href="transaction.html" class="card bg-white hover:bg-rose-50 transition-all shadow-md rounded-lg hover:shadow-xl transform hover:scale-105">
            <div class="card-body items-center text-center">
                <i class="fas fa-credit-card text-4xl text-rose-500 mb-3"></i>
                <h2 class="text-xl font-semibold">Transactions</h2>
            </div>
        </a>

        <a href="support.html" class="card bg-white hover:bg-purple-50 transition-all shadow-md rounded-lg hover:shadow-xl transform hover:scale-105">
            <div class="card-body items-center text-center">
                <i class="fas fa-headset text-4xl text-purple-500 mb-3"></i>
                <h2 class="text-xl font-semibold">Support</h2>
            </div>
        </a>
    </div>
</section>

<script>
    function logout() {
        fetch('logout.php', { method: 'POST' }) // Use POST for logout
            .then(response => {
                if (response.ok) {
                    window.location.href = "login.php";
                } else {
                    alert('Logout failed.');
                }
            })
            .catch(() => alert('Logout failed.'));
    }
</script>

</body>
</html>