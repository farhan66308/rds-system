<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include your proxy access class and the database connection
require_once 'ProxyAccess.php';
require_once 'ProxyReal.php';
require_once '../conn.php';

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the UserID and action from the form
    $userID = $_POST['userID'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($userID !== null && $action !== null) {
        // Instantiate the ProxyAccess with the provided UserID
        $userProxy = new ProxyAccess($userID);

        // Call the appropriate method. The proxy will handle the redirect.
        if (method_exists($userProxy, $action)) {
            $userProxy->$action($userID);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Proxy Design Pattern Test</title>
    <style>
        body { font-family: sans-serif; margin: 2em; }
        .container { max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ccc; border-radius: 8px; }
        h1 { text-align: center; }
        form div { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], select { width: 100%; padding: 8px; box-sizing: border-box; }
        button { padding: 10px 15px; background-color: #007bff; color: white; border: none; cursor: pointer; border-radius: 5px; }
        .info-box { margin-top: 20px; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Proxy Design Pattern Test</h1>
        <form action="" method="post">
            <div>
                <label for="userID">Enter UserID:</label>
                <input type="text" id="userID" name="userID" required>
            </div>
            <div>
                <label for="action">Select Action:</label>
                <select id="action" name="action" required>
                    <option value="">-- Choose an action --</option>
                    <option value="ManageUsers">Manage Users</option>
                    <option value="ManageTransaction">Manage Transactions</option>
                    <option value="ManageCourse">Manage Courses</option>
                    <option value="ViewCourse">View Courses</option>
                </select>
            </div>
            <button type="submit">Execute Action</button>
        </form>

        <div class="info-box">
            <p>Enter a UserID and select an action. If the user has the correct permissions, the page will redirect. Otherwise, nothing will happen (access denied).</p>
        </div>
    </div>
</body>
</html>