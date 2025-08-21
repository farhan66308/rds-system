<?php
require_once 'conn.php';
session_start();

if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$db = new Database();
$conn = $db->getConnection();

$userID = $_SESSION['UserID'];
$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $phone = trim($_POST['phone']);
    $emergencyContact = trim($_POST['emergency_contact']);
    $motherOccupation = trim($_POST['mother_occupation']);
    $fatherOccupation = trim($_POST['father_occupation']);
    $address = trim($_POST['address']);
    $passport = trim($_POST['passport']);

    // Update users table
    $stmt1 = $conn->prepare("UPDATE users SET Phone = ? WHERE UserID = ?");
    $stmt1->bind_param("si", $phone, $userID);
    $ok1 = $stmt1->execute();
    $stmt1->close();

    // Update studentinfo table
    $stmt2 = $conn->prepare("UPDATE studentinfo 
        SET EmergencyContact = ?, MotherOccupation = ?, FatherOccupation = ?, Addresss = ?, Passport = ?
        WHERE UserID = ?");
    $stmt2->bind_param("sssssi", $emergencyContact, $motherOccupation, $fatherOccupation, $address, $passport, $userID);
    $ok2 = $stmt2->execute();
    $stmt2->close();

    if ($ok1 && $ok2) {
        $message = "Profile updated successfully.";
    } else {
        $message = "Error updating profile.";
    }
}

// Fetch current data
$sql = "SELECT u.Phone, s.EmergencyContact, s.MotherOccupation, s.FatherOccupation, s.Addresss, s.Passport
        FROM users u
        LEFT JOIN studentinfo s ON u.UserID = s.UserID
        WHERE u.UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userID);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Eduor System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="dash.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="navbar">
        <div class="navbar-left">
            <span class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></span>
            <img src="https://dummyimage.com/200x40/004080/ffffff&text=Eduor+System" alt="Eduor Logo" class="logo">
        </div>
    </div>

    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="dash.php"><i class="fa fa-home"></i> Home</a></li>
            <li><a href="profile.php"><i class="fa fa-user"></i> Profile</a></li>
            <li class="active"><a href="profiledit.php"><i class="fa fa-edit"></i> Edit Profile</a></li>
            <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="logout.php"><i class="fa fa-power-off"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content" id="main-content">
        <div class="page-header">
            <h1>Edit Profile</h1>
        </div>

        <?php if ($message): ?>
            <div class="alert-box success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" class="form-box">
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($data['Phone'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="emergency_contact">Emergency Contact</label>
                    <input type="text" id="emergency_contact" name="emergency_contact" value="<?= htmlspecialchars($data['EmergencyContact'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="mother_occupation">Mother's Occupation</label>
                    <input type="text" id="mother_occupation" name="mother_occupation" value="<?= htmlspecialchars($data['MotherOccupation'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="father_occupation">Father's Occupation</label>
                    <input type="text" id="father_occupation" name="father_occupation" value="<?= htmlspecialchars($data['FatherOccupation'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?= htmlspecialchars($data['Addresss'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="passport">Passport</label>
                    <input type="text" id="passport" name="passport" value="<?= htmlspecialchars($data['Passport'] ?? '') ?>">
                </div>
                <div class="form-buttons">
                    <button type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById("sidebar").classList.toggle("active");
            document.getElementById("main-content").classList.toggle("shift");
        }
    </script>
</body>
</html>