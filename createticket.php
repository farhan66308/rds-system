<?php
session_start();
require_once 'conn.php'; 
require_once './libs/functions.php'; 

// Check login
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit();
}

$userID = $_SESSION['UserID'];

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = '';
$ticketDescription = '';
$TicketID = TicketIDGen();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketDescription = trim($_POST['description'] ?? '');

    if (empty($ticketDescription)) {
        $errors[] = "Ticket description cannot be empty.";
    }

    if (empty($errors)) {
        $status = 'Pending'; 

        $sql = "INSERT INTO tickets (TicketID, Description, FromUserID, Status) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $errors[] = "Database prepare failed: " . htmlspecialchars($conn->error);
        } else {
            $stmt->bind_param("ssss", $TicketID, $ticketDescription, $userID, $status);

            if ($stmt->execute()) {
                $success = "Your ticket has been submitted successfully! We will get back to you soon after an admin reviews this. Thank you for your patience. 🚀";
                $ticketDescription = ''; // Clear the form on success
            } else {
                $errors[] = "Failed to submit ticket: " . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Support Ticket - Eduor System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="dash.css"> </head>

<body class="bg-gray-100">
    <div class="navbar bg-base-100 shadow-md">
        <div class="navbar-left">
            <span class="menu-btn" onclick="toggleSidebar()"><i class="fa fa-bars"></i></span>
            <img src="https://dummyimage.com/200x40/004080/ffffff&text=Eduor+System" alt="Eduor Logo" class="logo">
        </div>
    </div>

    <div class="sidebar" id="sidebar">
        <ul>
            <li><a href="dash.php"><i class="fa fa-home"></i> Home</a></li>
            <li class="active"><a href="createticket.php"><i class="fa fa-ticket"></i> Create Ticket</a></li>
        </ul>
    </div>

    <div class="main-content" id="main-content">
        <section class="max-w-3xl mx-auto p-5">
            <h1 class="text-3xl font-bold mb-6 text-center">Create Support Ticket</h1>
            
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
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                            Describe your issue or request:
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="6" 
                            placeholder="Please provide a detailed description of your support request or issue here..." 
                            class="textarea textarea-bordered w-full" 
                            required
                        ><?= htmlspecialchars($ticketDescription); ?></textarea>
                    </div>
                    <div class="mt-6">
                        <button type="submit" class="btn btn-primary w-full">Submit Ticket</button>
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