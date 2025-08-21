<?php
session_start();
require_once 'conn.php';

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

$db = new Database();
$conn = $db->getConnection();

// Prepare and execute the query joining all 3 tables on UserID
$sql = "SELECT 
    u.Phone, u.Email,
    s.StudentID, s.FirstName, s.LastName, s.Gender, s.DateOfBirth, s.CitizenID, s.Passport, s.Nationality, s.Blood,
    s.Addresss, s.EmergencyContact, s.Program, s.MotherName, s.FatherName, s.ParentNationality, s.MotherOccupation, s.FatherOccupation,
    a.CGPA, a.PassCredits, a.PendingCredits, a.CurrentSemester, a.StartSemester
FROM users u
LEFT JOIN studentinfo s ON u.UserID = s.UserID
LEFT JOIN academicinformation a ON u.UserID = a.UserID
WHERE u.UserID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if (!$data) {
    die("No data found for the user.");
}

$fullname = trim(($data['FirstName'] ?? '') . ' ' . ($data['LastName'] ?? ''));
if ($fullname === '') $fullname = "User";
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Profile - Eduor System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="dash.css">
</head>

<body>

    <!-- TOP NAVBAR (same as dash.php) -->
    <div class="navbar">
        <div class="navbar-left">
            <span class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></span>
            <img src="https://dummyimage.com/200x40/004080/ffffff&text=Eduor+System" alt="Eduor Logo" class="logo">
        </div>
    </div>

    <!-- SIDEBAR -->
    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="dash.php"><i class="fa fa-home"></i> Home</a></li>
            <li class="active"><a href="profile.php"><i class="fa fa-user"></i> Profile</a></li>
            <li><a href="#"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fa fa-power-off"></i> Logout</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content" id="main-content">
        <section class="max-w-5xl mx-auto p-5">
            <h1 class="text-3xl font-bold mb-6">Your Information</h1>

            <!-- Contact Info -->
            <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold mb-4 border-b pb-2">Contact Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><strong>Phone:</strong> <?= htmlspecialchars($data['Phone'] ?? 'N/A'); ?></div>
                    <div><strong>Email:</strong> <?= htmlspecialchars($data['Email'] ?? 'N/A'); ?></div>
                    <div><strong>Address:</strong> <?= htmlspecialchars($data['Addresss'] ?? 'N/A'); ?></div>
                    <div><strong>Emergency Contact:</strong> <?= htmlspecialchars($data['EmergencyContact'] ?? 'N/A'); ?></div>
                </div>
            </div>

            <!-- Personal Info -->
            <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold mb-4 border-b pb-2">Personal Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><strong>Student ID:</strong> <?= htmlspecialchars($data['StudentID'] ?? 'N/A'); ?></div>
                    <div><strong>Full Name:</strong> <?= htmlspecialchars($fullname); ?></div>
                    <div><strong>Gender:</strong> <?= htmlspecialchars($data['Gender'] ?? 'N/A'); ?></div>
                    <div><strong>Date of Birth:</strong> <?= htmlspecialchars($data['DateOfBirth'] ?? 'N/A'); ?></div>
                    <div><strong>Citizen ID:</strong> <?= htmlspecialchars($data['CitizenID'] ?? 'N/A'); ?></div>
                    <div><strong>Passport:</strong> <?= htmlspecialchars($data['Passport'] ?? 'N/A'); ?></div>
                    <div><strong>Nationality:</strong> <?= htmlspecialchars($data['Nationality'] ?? 'N/A'); ?></div>
                    <div><strong>Blood Group:</strong> <?= htmlspecialchars($data['Blood'] ?? 'N/A'); ?></div>
                    <div><strong>Mother's Name:</strong> <?= htmlspecialchars($data['MotherName'] ?? 'N/A'); ?></div>
                    <div><strong>Father's Name:</strong> <?= htmlspecialchars($data['FatherName'] ?? 'N/A'); ?></div>
                    <div><strong>Parent Nationality:</strong> <?= htmlspecialchars($data['ParentNationality'] ?? 'N/A'); ?></div>
                    <div><strong>Mother's Occupation:</strong> <?= htmlspecialchars($data['MotherOccupation'] ?? 'N/A'); ?></div>
                    <div><strong>Father's Occupation:</strong> <?= htmlspecialchars($data['FatherOccupation'] ?? 'N/A'); ?></div>
                </div>
            </div>

            <!-- Academic Info -->
            <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold mb-4 border-b pb-2">Academic Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div><strong>Program:</strong> <?= htmlspecialchars($data['Program'] ?? 'N/A'); ?></div>
                    <div><strong>CGPA:</strong> <?= htmlspecialchars($data['CGPA'] ?? 'N/A'); ?></div>
                    <div><strong>Pass Credits:</strong> <?= htmlspecialchars($data['PassCredits'] ?? 'N/A'); ?></div>
                    <div><strong>Pending Credits:</strong> <?= htmlspecialchars($data['PendingCredits'] ?? 'N/A'); ?></div>
                    <div><strong>Current Semester:</strong> <?= htmlspecialchars($data['CurrentSemester'] ?? 'N/A'); ?></div>
                    <div><strong>Start Semester:</strong> <?= htmlspecialchars($data['StartSemester'] ?? 'N/A'); ?></div>
                </div>
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
