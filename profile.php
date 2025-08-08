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
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Information - Eduor System</title>
  <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-100 to-yellow-200">
  <!-- Navbar -->
  <section>
    <div class="navbar bg-base-100 h-[100px] bg-slate-300 m-3 rounded-lg shadow-md">
      <div class="navbar-start">
        <a href="dashboard.php" class="btn btn-ghost">← Back to Dashboard</a>
      </div>
      <div class="navbar-center">
        <a class="btn btn-ghost text-2xl font-bold">Eduor System - User Info</a>
      </div>
    </div>
  </section>

  <section class="max-w-5xl mx-auto p-5">
    <h1 class="text-3xl font-bold mb-6">Your Information</h1>

    <!-- Contact Info -->
    <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
      <h2 class="text-2xl font-semibold mb-4 border-b pb-2">Contact Information</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><strong>Phone:</strong> <?php echo htmlspecialchars($data['Phone'] ?? 'N/A'); ?></div>
        <div><strong>Email:</strong> <?php echo htmlspecialchars($data['Email'] ?? 'N/A'); ?></div>
        <div><strong>Address:</strong> <?php echo htmlspecialchars($data['Addresss'] ?? 'N/A'); ?></div>
        <div><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($data['EmergencyContact'] ?? 'N/A'); ?></div>
      </div>
    </div>

    <!-- Personal Info -->
    <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
      <h2 class="text-2xl font-semibold mb-4 border-b pb-2">Personal Information</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><strong>Student ID:</strong> <?php echo htmlspecialchars($data['StudentID'] ?? 'N/A'); ?></div>
        <div><strong>Full Name:</strong> <?php echo htmlspecialchars($data['FirstName'] . ' ' . $data['LastName']); ?></div>
        <div><strong>Gender:</strong> <?php echo htmlspecialchars($data['Gender'] ?? 'N/A'); ?></div>
        <div><strong>Date of Birth:</strong> <?php echo htmlspecialchars($data['DateOfBirth'] ?? 'N/A'); ?></div>
        <div><strong>Citizen ID:</strong> <?php echo htmlspecialchars($data['CitizenID'] ?? 'N/A'); ?></div>
        <div><strong>Passport:</strong> <?php echo htmlspecialchars($data['Passport'] ?? 'N/A'); ?></div>
        <div><strong>Nationality:</strong> <?php echo htmlspecialchars($data['Nationality'] ?? 'N/A'); ?></div>
        <div><strong>Blood Group:</strong> <?php echo htmlspecialchars($data['Blood'] ?? 'N/A'); ?></div>
        <div><strong>Mother's Name:</strong> <?php echo htmlspecialchars($data['MotherName'] ?? 'N/A'); ?></div>
        <div><strong>Father's Name:</strong> <?php echo htmlspecialchars($data['FatherName'] ?? 'N/A'); ?></div>
        <div><strong>Parent Nationality:</strong> <?php echo htmlspecialchars($data['ParentNationality'] ?? 'N/A'); ?></div>
        <div><strong>Mother's Occupation:</strong> <?php echo htmlspecialchars($data['MotherOccupation'] ?? 'N/A'); ?></div>
        <div><strong>Father's Occupation:</strong> <?php echo htmlspecialchars($data['FatherOccupation'] ?? 'N/A'); ?></div>
      </div>
    </div>

    <!-- Academic Info -->
    <div class="mb-8 p-6 bg-white rounded-lg shadow-md">
      <h2 class="text-2xl font-semibold mb-4 border-b pb-2">Academic Information</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div><strong>Program:</strong> <?php echo htmlspecialchars($data['Program'] ?? 'N/A'); ?></div>
        <div><strong>CGPA:</strong> <?php echo htmlspecialchars($data['CGPA'] ?? 'N/A'); ?></div>
        <div><strong>Pass Credits:</strong> <?php echo htmlspecialchars($data['PassCredits'] ?? 'N/A'); ?></div>
        <div><strong>Pending Credits:</strong> <?php echo htmlspecialchars($data['PendingCredits'] ?? 'N/A'); ?></div>
        <div><strong>Current Semester:</strong> <?php echo htmlspecialchars($data['CurrentSemester'] ?? 'N/A'); ?></div>
        <div><strong>Start Semester:</strong> <?php echo htmlspecialchars($data['StartSemester'] ?? 'N/A'); ?></div>
      </div>
    </div>
  </section>

</body>
</html>