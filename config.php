<?php
// config.php - koneksi database (mysqli)
// Ensure a writable session save path to avoid PHP warnings on Windows/XAMPP
$sessDir = __DIR__ . '/sessions';
// Only set session.save_path if a session has not yet been started
if (session_status() == PHP_SESSION_NONE) {
    if (!is_dir($sessDir)) @mkdir($sessDir, 0777, true);
    if (is_dir($sessDir) && is_writable($sessDir)) {
        ini_set('session.save_path', $sessDir);
    } else {
        $tmp = sys_get_temp_dir();
        if (is_dir($tmp) && is_writable($tmp)) ini_set('session.save_path', $tmp);
    }
}
// hide direct error output to users; log errors instead
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
@ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
if (session_status() == PHP_SESSION_NONE) session_start();

$DB_HOST = '192.168.1.223';
$DB_USER = 'nas_it';
$DB_PASS = 'Nasityc@2025';
$DB_NAME = 'pesanan_kantin';

// connect to DB but avoid printing plain text on failure (APIs expect JSON)
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    // don't die with plain text; set to null and let callers handle
    $DB_CONN_ERROR = $conn->connect_error;
    $conn = null;
} else {
    $DB_CONN_ERROR = null;
}

// If running under an API path, ensure fatal errors return JSON instead of raw HTML/text
$scriptName = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
if (strpos($scriptName, '/api/') !== false) {
    // configure error logging to file for diagnostics
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    $logFile = $logDir . '/php_errors.log';
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', $logFile);
    error_reporting(E_ALL);
    if (!headers_sent()) header('Content-Type: application/json');
    ob_start();
    register_shutdown_function(function() use (&$DB_CONN_ERROR, $scriptName) {
        $err = error_get_last();
        $buf = '';
        if (ob_get_length()) $buf = ob_get_clean();
        if ($err) {
            http_response_code(500);
            $out = ['error'=>'server_error','msg'=>substr($err['message'],0,200)];
            // include buffer for debugging if present
            if (!empty($buf)) $out['debug'] = mb_substr($buf,0,1000);
            echo json_encode($out);
            return;
        }
        // If DB connection failed earlier, respond with JSON
        if (!empty($DB_CONN_ERROR)) {
            http_response_code(500);
            echo json_encode(['error'=>'db_connect','msg'=>$DB_CONN_ERROR]);
            return;
        }
        // otherwise flush any buffered output
        if ($buf !== '') echo $buf;
    });
}

function esc($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Menu update configuration: which days suppliers are allowed to update menu (1=Mon .. 7=Sun)
// Default: all days allowed
$MENU_UPDATE_DAYS = [1,2,3,4,5,6,7];

// Order status codes (master): store integers in DB for portability and compactness
// 0 = open (pending/new), 1 = confirmed, 2 = closed
if (!defined('ORDER_STATUS_OPEN')) define('ORDER_STATUS_OPEN', 0);
if (!defined('ORDER_STATUS_CONFIRMED')) define('ORDER_STATUS_CONFIRMED', 1);
if (!defined('ORDER_STATUS_CLOSED')) define('ORDER_STATUS_CLOSED', 2);

// Human-readable labels for status codes
$ORDER_STATUS_LABELS = [
    ORDER_STATUS_OPEN => 'Open',
    ORDER_STATUS_CONFIRMED => 'Confirmed',
    ORDER_STATUS_CLOSED => 'Closed',
];

// Backwards compatibility mapping from legacy string statuses to codes.
// Extend this map if your existing code stores statuses as strings like 'pending','claimed', etc.
$LEGACY_STATUS_TO_CODE = [
    'pending' => ORDER_STATUS_OPEN,
    'open' => ORDER_STATUS_OPEN,
    'confirmed' => ORDER_STATUS_CONFIRMED,
    'accepted' => ORDER_STATUS_CONFIRMED,
    'closed' => ORDER_STATUS_CLOSED,
    'cancelled' => ORDER_STATUS_CLOSED,
    // add other legacy variants as needed
];

function order_status_label($code) {
    global $ORDER_STATUS_LABELS;
    return $ORDER_STATUS_LABELS[$code] ?? 'Unknown';
}

function order_status_code_from_legacy($s) {
    global $LEGACY_STATUS_TO_CODE;
    if (is_numeric($s)) return intval($s);
    $k = strtolower(trim($s));
    return $LEGACY_STATUS_TO_CODE[$k] ?? ORDER_STATUS_OPEN;
}
