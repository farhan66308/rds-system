<?php
// ===== 1. Load bKash config from JSON =====
$configFile = __DIR__ . '/d9e7e478-1d75-424a-aacf-7c3ad35cdf46.json';
if (!file_exists($configFile)) {
    die(json_encode(['success' => false, 'error' => 'Config file missing']));
}
$bkashConfig = json_decode(file_get_contents($configFile), true);
if (!$bkashConfig) {
    die(json_encode(['success' => false, 'error' => 'Invalid config JSON']));
}

// ===== 2. DB Connection =====
$dbHost = "127.0.0.1";   // Change if needed
$dbName = "eduor";       // Your database name
$dbUser = "root";        // DB username
$dbPass = "";            // DB password

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'DB connection failed: ' . $e->getMessage()]));
}

// ===== 3. Helper: Output JSON =====
function jsonOut($arr) {
    header('Content-Type: application/json');
    echo json_encode($arr);
    exit;
}

// ===== 4. Helper: HTTP POST =====
function httpPost($url, $data, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $resp = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new Exception('cURL error: ' . curl_error($ch));
    }
    curl_close($ch);
    return json_decode($resp, true);
}

// ===== 5. Router =====
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'create') {
        // Get input
        $amount = $_POST['amount'] ?? $_GET['amount'] ?? 0;
        $userID = $_POST['userID'] ?? $_GET['userID'] ?? 0;
        if (!$amount || !$userID) {
            jsonOut(['success' => false, 'error' => 'amount and userID required']);
        }

        $invoice = time() . '-' . mt_rand(1000, 9999);
        $payload = [
            'amount' => $amount,
            'currency' => 'BDT',
            'merchantInvoiceNumber' => $invoice,
            'intent' => 'sale'
        ];

        // Call bKash create API
        $headers = [
            'Content-Type:application/json',
            'authorization:' . $bkashConfig['token'],
            'x-app-key:' . $bkashConfig['app_key']
        ];
        $resp = httpPost($bkashConfig['createURL'], $payload, $headers);

        // Save to DB
        $stmt = $pdo->prepare("INSERT INTO payment (PaymentID, Info, Amount, UserID, Date) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$invoice, json_encode($resp), $amount, $userID, date('Y-m-d')]);

        jsonOut(['success' => true, 'invoice' => $invoice, 'bkash' => $resp]);
    }
    elseif ($action === 'execute') {
        $paymentID = $_POST['paymentID'] ?? $_GET['paymentID'] ?? '';
        $localPaymentID = $_POST['localPaymentID'] ?? $_GET['localPaymentID'] ?? '';
        if (!$paymentID && !$localPaymentID) {
            jsonOut(['success' => false, 'error' => 'paymentID or localPaymentID required']);
        }

        $headers = [
            'Content-Type:application/json',
            'authorization:' . $bkashConfig['token'],
            'x-app-key:' . $bkashConfig['app_key']
        ];

        // Call bKash execute API
        $url = $bkashConfig['executeURL'] . ($paymentID ?: $localPaymentID);
        $resp = httpPost($url, [], $headers);

        // Update DB
        $stmt = $pdo->prepare("UPDATE payment SET Info = CONCAT(IFNULL(Info,''), ?), Date = ? WHERE PaymentID = ?");
        $stmt->execute(["\nEXECUTE: " . json_encode($resp), date('Y-m-d'), $localPaymentID ?: $paymentID]);

        jsonOut(['success' => true, 'bkash' => $resp]);
    }
    else {
        jsonOut(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    jsonOut(['success' => false, 'error' => $e->getMessage()]);
}
?>