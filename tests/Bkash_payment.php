<?php
/**
 * bkash_payment.php
 * Single-file bKash payment system (sandbox-ready)
 * Usage:
 *   - For creating a payment: POST {action: "create", amount: 100, userID: 5}
 *   - For executing a payment: POST {action: "execute", paymentID: "...", localPaymentID: "..."}
 */

/* =========================
   CONFIGURATION
   ========================= */
$config = (object)[
    // Database
    'db' => (object)[
        'host' => '127.0.0.1',
        'name' => 'eduor', // your DB name from the .sql you imported
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    // bKash API settings
    'bkash' => (object)[
        'sandbox' => true, // false for production
        'app_key' => 'YOUR_APP_KEY',
        'app_secret' => 'YOUR_APP_SECRET',
        'username' => 'YOUR_USERNAME', // optional per integration
        'password' => 'YOUR_PASSWORD', // optional
        'endpoints' => (object)[
            'sandbox' => (object)[
                'token' => 'https://tokenized.sandbox.bkash.com/checkout/token/grant',
                'create' => 'https://tokenized.sandbox.bkash.com/checkout/payment/create',
                'execute' => 'https://tokenized.sandbox.bkash.com/checkout/payment/execute/',
                'query' => 'https://tokenized.sandbox.bkash.com/checkout/payment/query/',
            ],
            'live' => (object)[
                'token' => 'https://tokenized.bkash.com/checkout/token/grant',
                'create' => 'https://tokenized.bkash.com/checkout/payment/create',
                'execute' => 'https://tokenized.bkash.com/checkout/payment/execute/',
                'query' => 'https://tokenized.bkash.com/checkout/payment/query/',
            ],
        ],
        'timeout' => 30,
    ],
];

/* =========================
   DB CONNECTION (PDO)
   ========================= */
try {
    $dsn = "mysql:host={$config->db->host};dbname={$config->db->name};charset={$config->db->charset}";
    $pdo = new PDO($dsn, $config->db->user, $config->db->pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'DB Error: ' . $e->getMessage()]));
}

/* =========================
   BKASH CLIENT CLASS
   ========================= */
class BkashClient {
    private $config;
    private $token;

    public function __construct($config) {
        $this->config = $config;
    }

    private function httpPost($url, $payload = null, $headers = []) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->bkash->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge([
            'Content-Type: application/json'
        ], $headers));
        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        $resp = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception("cURL error: " . curl_error($ch));
        }
        curl_close($ch);
        $decoded = json_decode($resp, true);
        if ($decoded === null) {
            throw new Exception("Invalid JSON: $resp");
        }
        return $decoded;
    }

    public function grantToken() {
        if (!empty($this->token) && isset($this->token['id_token'])) {
            return $this->token;
        }
        $env = $this->config->bkash->sandbox ? 'sandbox' : 'live';
        $url = $this->config->bkash->endpoints->$env->token;
        $payload = [
            'app_key' => $this->config->bkash->app_key,
            'app_secret' => $this->config->bkash->app_secret
        ];
        $resp = $this->httpPost($url, $payload);
        if (isset($resp['id_token'])) {
            $this->token = $resp;
            return $resp;
        }
        throw new Exception("Token error: " . json_encode($resp));
    }

    public function createPayment($amount, $invoice, $intent = 'sale') {
        $env = $this->config->bkash->sandbox ? 'sandbox' : 'live';
        $url = $this->config->bkash->endpoints->$env->create;
        $token = $this->grantToken();
        $headers = [
            "Authorization: {$token['id_token']}",
            "X-App-Key: {$this->config->bkash->app_key}"
        ];
        $payload = [
            'amount' => (string)$amount,
            'currency' => 'BDT',
            'merchantInvoiceNumber' => $invoice,
            'intent' => $intent
        ];
        return $this->httpPost($url, $payload, $headers);
    }

    public function executePayment($paymentID) {
        $env = $this->config->bkash->sandbox ? 'sandbox' : 'live';
        $url = rtrim($this->config->bkash->endpoints->$env->execute, '/') . '/' . $paymentID;
        $token = $this->grantToken();
        $headers = [
            "Authorization: {$token['id_token']}",
            "X-App-Key: {$this->config->bkash->app_key}"
        ];
        return $this->httpPost($url, null, $headers);
    }
}

/* =========================
   ROUTER (SINGLE ENTRY)
   ========================= */
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $input['action'] ?? null;

try {
    $bkash = new BkashClient($config);

    if ($action === 'create') {
        $amount = $input['amount'] ?? 10;
        $userID = $input['userID'] ?? 1;
        $invoice = 'INV_' . time() . rand(1000,9999);

        $resp = $bkash->createPayment($amount, $invoice);

        // Save in DB
        $stmt = $pdo->prepare("INSERT INTO payment (PaymentID, Info, Amount, UserID, Date) VALUES (:pid, :info, :amt, :uid, :date)");
        $stmt->execute([
            ':pid' => $invoice,
            ':info' => json_encode($resp),
            ':amt' => $amount,
            ':uid' => $userID,
            ':date' => date('Y-m-d')
        ]);

        echo json_encode(['success' => true, 'invoice' => $invoice, 'bkash' => $resp]);
        exit;
    }

    if ($action === 'execute') {
        $paymentID = $input['paymentID'] ?? null;
        $localPaymentID = $input['localPaymentID'] ?? null;
        if (!$paymentID && !$localPaymentID) {
            throw new Exception("Missing paymentID/localPaymentID");
        }
        $resp = $bkash->executePayment($paymentID ?: $localPaymentID);

        $stmt = $pdo->prepare("UPDATE payment SET Info = CONCAT(IFNULL(Info,''), :more), Date = :date WHERE PaymentID = :pid");
        $stmt->execute([
            ':more' => "\nEXECUTE: " . json_encode($resp),
            ':date' => date('Y-m-d'),
            ':pid' => $localPaymentID ?? $paymentID
        ]);

        echo json_encode(['success' => true, 'bkash' => $resp]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
