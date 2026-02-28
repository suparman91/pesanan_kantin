<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }
if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) { http_response_code(400); echo json_encode(['error'=>'csrf']); exit; }

$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['supplier','admin','hrd'])) { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }

$order_id = (int)($_POST['order_id'] ?? 0);
if ($order_id <= 0) { http_response_code(400); echo json_encode(['error'=>'invalid']); exit; }

$q = $conn->prepare('SELECT id,status,status_code,claimed_by,user_id FROM orders WHERE id=?');
$q->bind_param('i',$order_id);
$q->execute();
$res = $q->get_result();
if (!$row = $res->fetch_assoc()) { http_response_code(404); echo json_encode(['error'=>'notfound']); exit; }
$legacyStatus = $row['status'] ?? '';
$statusCode = isset($row['status_code']) ? intval($row['status_code']) : order_status_code_from_legacy($legacyStatus);

// require that this order has been claimed by a supplier before accepting
$claimed_by = (int)($row['claimed_by'] ?? 0);
if ($claimed_by <= 0) { http_response_code(409); echo json_encode(['error'=>'not_claimed']); exit; }

// only the claimant supplier may accept — do not allow admin/hrd to accept supplier totals
$claimed_by = (int)($row['claimed_by'] ?? 0);
$me = (int)$_SESSION['user_id'];
if ($me !== $claimed_by) { http_response_code(403); echo json_encode(['error'=>'not_permitted']); exit; }
$hasStatusCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'status'");
$newCode = defined('ORDER_STATUS_CONFIRMED') ? ORDER_STATUS_CONFIRMED : 1;
if ($hasStatusCol && $hasStatusCol->num_rows) {
    $u = $conn->prepare('UPDATE orders SET status_code=?, status=? WHERE id=?');
    $newStatusText = 'accepted';
    $u->bind_param('isi',$newCode,$newStatusText,$order_id);
} else {
    $u = $conn->prepare('UPDATE orders SET status_code=? WHERE id=?');
    $u->bind_param('ii',$newCode,$order_id);
}
if ($u->execute()) echo json_encode(['ok'=>true]);
else { http_response_code(500); echo json_encode(['error'=>'db','msg'=>$conn->error]); }

// create notifications table if missing and insert notification (per-user)
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    message VARCHAR(255),
    created_by INT,
    target_user INT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
// ensure columns exist for older installs
$col = $conn->query("SHOW COLUMNS FROM notifications LIKE 'is_read'");
if (!$col || $col->num_rows === 0) {
	$conn->query("ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) DEFAULT 0");
}
$col2 = $conn->query("SHOW COLUMNS FROM notifications LIKE 'target_user'");
if (!$col2 || $col2->num_rows === 0) {
	$conn->query("ALTER TABLE notifications ADD COLUMN target_user INT DEFAULT NULL");
}
$actor = $me;
$un = '';
$order_owner = (int)($row['user_id'] ?? 0);
$usrq = $conn->prepare('SELECT name FROM users WHERE id=?');
$usrq->bind_param('i',$actor); $usrq->execute(); $usrres = $usrq->get_result(); if ($urow = $usrres->fetch_assoc()) $un = $urow['name'];
$msg = $conn->real_escape_string("Pesanan #$order_id diterima oleh $un");
$conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES ($order_id,'$msg',$actor,$order_owner,0)");
