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

$tickets = [];
$feedbackSuccess = '';
$feedbackError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $ticketID = trim($_POST['ticketID'] ?? '');
    $feedback = trim($_POST['feedback'] ?? '');

    if (empty($ticketID) || empty($feedback)) {
        $feedbackError = "Both ticket ID and feedback are required.";
    } else {
        // Prepare query to update the feedback
        $sql = "UPDATE tickets SET Feedback = ? WHERE TicketID = ? AND FromUserID = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $feedbackError = "Database prepare failed: " . htmlspecialchars($conn->error);
        } else {
            // Check if the ticket is already solved and doesn't have feedback
            $checkSql = "SELECT Status, Feedback FROM tickets WHERE TicketID = ? AND FromUserID = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("ss", $ticketID, $userID);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            $ticketData = $checkResult->fetch_assoc();
            $checkStmt->close();

            if ($ticketData['Status'] !== 'Solved') {
                $feedbackError = "You can only leave feedback on tickets that are 'Solved'.";
            } elseif (!empty($ticketData['Feedback'])) {
                $feedbackError = "Feedback for this ticket has already been submitted.";
            } else {
                // Execute the update
                $stmt->bind_param("sss", $feedback, $ticketID, $userID);
                if ($stmt->execute()) {
                    $feedbackSuccess = "Feedback for Ticket ID " . htmlspecialchars($ticketID) . " has been submitted successfully!";
                } else {
                    $feedbackError = "Failed to submit feedback: " . htmlspecialchars($stmt->error);
                }
            }
            $stmt->close();
        }
    }
}


// Retrieve tickets 
$sql = "SELECT TicketID, Description, Status, Feedback FROM tickets WHERE FromUserID = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Prepare failed: " . htmlspecialchars($conn->error));
}

$stmt->bind_param("s", $userID);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}
$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Track Your Tickets - Eduor System</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.12.14/dist/full.min.css" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="dash.css">
</head>

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
            <li><a href="createticket.php"><i class="fa fa-ticket"></i> Create Ticket</a></li>
            <li class="active"><a href="trackticket.php"><i class="fa fa-list"></i> Track Tickets</a></li>
        </ul>
    </div>

    <div class="main-content" id="main-content">
        <section class="max-w-5xl mx-auto p-5">
            <h1 class="text-3xl font-bold mb-6 text-center">Track Your Support Tickets</h1>

            <?php if (!empty($feedbackSuccess)): ?>
                <div class="alert alert-success mb-4">
                    <p><?= htmlspecialchars($feedbackSuccess); ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($feedbackError)): ?>
                <div class="alert alert-error mb-4">
                    <p><?= htmlspecialchars($feedbackError); ?></p>
                </div>
            <?php endif; ?>

            <?php if (empty($tickets)): ?>
                <div class="text-center p-6 bg-white rounded-lg shadow-md">
                    <p class="text-gray-600">You have not submitted any tickets yet. <a href="createticket.php" class="text-blue-600 hover:underline">Create one now.</a></p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($tickets as $ticket): ?>
                        <div class="card bg-white shadow-xl">
                            <div class="card-body">
                                <h2 class="card-title text-2xl">Ticket ID: <?= htmlspecialchars($ticket['TicketID']); ?></h2>
                                <div class="badge <?= $ticket['Status'] === 'Solved' ? 'badge-success' : 'badge-warning'; ?>">
                                    Status: <?= htmlspecialchars($ticket['Status']); ?>
                                </div>
                                <p class="text-gray-700 mt-2">
                                    <span class="font-semibold">Description:</span><br>
                                    <?= nl2br(htmlspecialchars($ticket['Description'])); ?>
                                </p>

                                <?php if ($ticket['Status'] === 'Solved' && empty($ticket['Feedback'])): ?>
                                    <div class="divider"></div>
                                    <form method="POST" class="mt-4">
                                        <input type="hidden" name="ticketID" value="<?= htmlspecialchars($ticket['TicketID']); ?>">
                                        <div class="form-control">
                                            <label for="feedback_<?= htmlspecialchars($ticket['TicketID']); ?>" class="label">
                                                <span class="label-text">Provide your feedback:</span>
                                            </label>
                                            <textarea id="feedback_<?= htmlspecialchars($ticket['TicketID']); ?>" name="feedback" class="textarea textarea-bordered h-24" placeholder="How satisfied are you with the resolution?" required></textarea>
                                        </div>
                                        <div class="mt-2">
                                            <button type="submit" name="submit_feedback" class="btn btn-primary btn-sm">Submit Feedback</button>
                                        </div>
                                    </form>
                                <?php elseif ($ticket['Status'] === 'Solved' && !empty($ticket['Feedback'])): ?>
                                    <div class="divider"></div>
                                    <p class="text-gray-600 mt-2">
                                        <span class="font-semibold">Your Feedback:</span><br>
                                        <?= nl2br(htmlspecialchars($ticket['Feedback'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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