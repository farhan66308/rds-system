<?php
session_start();
require_once '../conn.php';
require_once '../libs/functions.php';
$db = new Database();
$conn = $db->getConnection();
$errors = [];
$success = '';
$firstName = '';
$lastName = '';
$phone = '';
$email = '';
$gender = '';
$userType = '';
$SignUpUserID = UserIDGen();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate form data
    $firstName = trim($_POST['FirstName'] ?? '');
    $lastName = trim($_POST['LastName'] ?? '');
    $phone = trim($_POST['Phone'] ?? '');
    $email = filter_var(trim($_POST['Email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $gender = trim($_POST['Gender'] ?? '');
    $password = trim($_POST['Password'] ?? '');
    $userType = trim($_POST['UserType'] ?? '');

    // Basic validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($userType)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    // Check if the email exists in  db eduor
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT UserID FROM users WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "This email is already registered.";
        }
        $stmt->close();
    }

    if (empty($errors)) {
        // Map UserType to a numerical value if your database requires it
        $typeMap = ['Student' => 1, 'Faculty' => 2, 'Accountant' => 3];
        $userTypeValue = $typeMap[$userType] ?? 0;

        $conn->begin_transaction();
        try {
            // users table
            $fa2 = 0;
            $stmt = $conn->prepare("INSERT INTO users (UserID, Phone, Email, password, UserFlag, 2fa) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssii", $SignUpUserID, $phone, $email, $password, $userTypeValue, $fa2);
            $stmt->execute();
            $newUserID = $conn->insert_id;
            $stmt->close();

            // studentinfo table
            $stmt = $conn->prepare("INSERT INTO studentinfo (UserID, FirstName, LastName, Gender) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $newUserID, $firstName, $lastName, $gender);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            $success = "Registration successful! You can now log in.";

            // Clear the form fields after success
            $firstName = $lastName = $phone = $email = $gender = $userType = '';
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Registration failed. Please try again later.";
            // Optionally log the error: error_log($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sign Up - Eduor System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../dash.css">
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
            <li><a href="admin.php"><i class="fa fa-home"></i> Home</a></li>
            <li><a href="profile.php"><i class="fa fa-user"></i> Profile</a></li>
            <li class="active"><a href="signup.php"><i class="fa fa-user-plus"></i> Sign Up</a></li>
            <li><a href="settings.php"><i class="fa fa-cog"></i> Settings</a></li>
            <li><a href="../logout.php"><i class="fa fa-power-off"></i> Logout</a></li>
        </ul>
    </div>

    <div class="main-content" id="main-content">
        <section class="max-w-xl mx-auto p-5">
            <h1 class="text-3xl font-bold mb-6 text-center">Create a New User</h1>

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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <input type="text" name="FirstName" placeholder="First Name" class="input input-bordered w-full" value="<?= htmlspecialchars($firstName); ?>" required />
                        <input type="text" name="LastName" placeholder="Last Name" class="input input-bordered w-full" value="<?= htmlspecialchars($lastName); ?>" required />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <input type="tel" name="Phone" placeholder="Phone" class="input input-bordered w-full" value="<?= htmlspecialchars($phone); ?>" required />
                        <input type="email" name="Email" placeholder="Email" class="input input-bordered w-full" value="<?= htmlspecialchars($email); ?>" required />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <select name="Gender" class="select select-bordered w-full" required>
                            <option disabled selected value="">Select Gender</option>
                            <option value="Male" <?= ($gender == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?= ($gender == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?= ($gender == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                        <select name="UserType" class="select select-bordered w-full" required>
                            <option disabled selected value="">Select User Type</option>
                            <option value="Student" <?= ($userType == 'Student') ? 'selected' : ''; ?>>Student</option>
                            <option value="Faculty" <?= ($userType == 'Faculty') ? 'selected' : ''; ?>>Faculty</option>
                            <option value="Accountant" <?= ($userType == 'Accountant') ? 'selected' : ''; ?>>Accountant</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <input type="password" name="Password" placeholder="Password" class="input input-bordered w-full" required />
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary w-full">Sign Up</button>
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