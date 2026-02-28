<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

// Basic method + auth + CSRF checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) { http_response_code(400); echo json_encode(['error'=>'csrf']); exit; }

$role = $_SESSION['role'] ?? '';
$me = (int)($_SESSION['user_id'] ?? 0);
$order_id = (int)($_POST['order_id'] ?? 0);
if ($order_id <= 0) { http_response_code(400); echo json_encode(['error'=>'invalid']); exit; }

// Logger
$logdir = __DIR__ . '/../logs'; if (!is_dir($logdir)) @mkdir($logdir,0755,true);
$logfile = $logdir . '/api_cancel_order.log';
function _log($msg) { global $logfile, $me, $role, $order_id; $ctx = date('c') . " | user_id=" . ($me ?? '0') . " | role=" . ($role ?? '') . " | order_id=" . ($order_id ?? '0') . " | msg=" . $msg . "\n"; @file_put_contents($logfile, $ctx, FILE_APPEND | LOCK_EX); }
_log('entered');

// Detect available columns
$colsRes = $conn->query("SHOW COLUMNS FROM orders");
$cols = [];
if ($colsRes) { while ($c = $colsRes->fetch_assoc()) $cols[] = $c['Field']; }

// Build SELECT with existing columns
$selectCols = ['id','status','status_code','user_id'];
if (in_array('claimed_by',$cols)) $selectCols[] = 'claimed_by';
if (in_array('supplier_id',$cols)) $selectCols[] = 'supplier_id';
if (in_array('approved_at',$cols)) $selectCols[] = 'approved_at';
if (in_array('approved_by',$cols)) $selectCols[] = 'approved_by';
if (in_array('cancel_until',$cols)) $selectCols[] = 'cancel_until';
if (in_array('cancel_deadline',$cols) && !in_array('cancel_until',$selectCols)) $selectCols[] = 'cancel_deadline';

$q = $conn->prepare('SELECT ' . implode(',', $selectCols) . ' FROM orders WHERE id=?');
if (!$q) { _log('prepare_failed: '.$conn->error); http_response_code(500); echo json_encode(['error'=>'db','msg'=>$conn->error]); exit; }
$q->bind_param('i',$order_id);
if (!$q->execute()) { _log('execute_failed: '.$q->error); http_response_code(500); echo json_encode(['error'=>'db','msg'=>$q->error]); exit; }
$res = $q->get_result();
if (!$res || !$row = $res->fetch_assoc()) { http_response_code(404); echo json_encode(['error'=>'notfound']); exit; }

$legacyStatus = $row['status'] ?? '';
$statusCode = isset($row['status_code']) ? intval($row['status_code']) : order_status_code_from_legacy($legacyStatus);

// Constants
$confirmedCode = defined('ORDER_STATUS_CONFIRMED') ? ORDER_STATUS_CONFIRMED : 1;
$openCode = defined('ORDER_STATUS_OPEN') ? ORDER_STATUS_OPEN : 0;
$closedCode = defined('ORDER_STATUS_CLOSED') ? ORDER_STATUS_CLOSED : 2;

// If already confirmed -> only claimant supplier may cancel
$claimed_by = isset($row['claimed_by']) ? (int)$row['claimed_by'] : 0;
if ($statusCode === $confirmedCode && !($role === 'supplier' && $claimed_by === $me)) {
    _log('already_accepted');
    http_response_code(409); echo json_encode(['error'=>'already_accepted']); exit;
}

// Role-based permission checks
if ($role === 'admin') {
    $newStatusText = 'cancelled_by_admin';
} elseif ($role === 'supplier') {
    if ($claimed_by !== $me) { _log('not_claimant'); http_response_code(403); echo json_encode(['error'=>'not_claimant']); exit; }
    $newStatusText = 'cancelled_by_supplier';
} else {
    $order_owner = (int)($row['user_id'] ?? 0);
    if ($order_owner !== $me) { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }
    // disallow if admin already approved
    if ((!empty($row['approved_at']) && $row['approved_at'] !== '0000-00-00 00:00:00') || !empty($row['approved_by'])) {
        http_response_code(409); echo json_encode(['error'=>'cannot_cancel_after_admin_approval']); exit;
    }
    // respect cancel deadline if present
    if (!empty($row['cancel_until']) || !empty($row['cancel_deadline'])) {
        $dl = !empty($row['cancel_until']) ? $row['cancel_until'] : $row['cancel_deadline'];
        if (!empty($dl)) {
            $now = new DateTime('now');
            try { $cut = new DateTime($dl); if ($now > $cut) { http_response_code(409); echo json_encode(['error'=>'cancel_deadline_passed']); exit; } } catch(Exception $e) {}
        }
    }
    $newStatusText = 'cancelled_by_user';
}

// Perform update
try {
    if ($role === 'supplier') {
        // restore to open and clear claimant/supplier where present
        $sets = ['status_code = ?'];
        $types = 'i';
        $values = [$openCode];
        if (in_array('status',$cols)) { $sets[] = 'status = ?'; $types .= 's'; $values[] = $newStatusText; }
        if (in_array('claimed_by',$cols)) $sets[] = 'claimed_by = NULL';
        if (in_array('supplier_id',$cols)) $sets[] = 'supplier_id = NULL';
        $sql = 'UPDATE orders SET ' . implode(', ', $sets) . ' WHERE id = ?';
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception($conn->error);
        // bind params dynamically
        $bindTypes = $types . 'i';
        $bindVals = array_merge($values, [$order_id]);
        $stmt->bind_param($bindTypes, ...$bindVals);
        if (!$stmt->execute()) throw new Exception($stmt->error);
    } else {
        if (in_array('status',$cols)) {
            $stmt = $conn->prepare('UPDATE orders SET status_code=?, status=? WHERE id=?');
            $stmt->bind_param('isi', $closedCode, $newStatusText, $order_id);
        } else {
            $stmt = $conn->prepare('UPDATE orders SET status_code=? WHERE id=?');
            $stmt->bind_param('ii', $closedCode, $order_id);
        }
        if (!$stmt->execute()) throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    _log('update_failed: '.$e->getMessage());
    http_response_code(500); echo json_encode(['error'=>'db','msg'=>'update_failed']); exit;
}

// Notifications (best-effort; notifications table may not exist)
$actor = $me;
$aname = '';
$usrq = $conn->prepare('SELECT name FROM users WHERE id=?');
if ($usrq) { $usrq->bind_param('i',$actor); $usrq->execute(); $usrres = $usrq->get_result(); if ($urow = $usrres->fetch_assoc()) $aname = $urow['name']; }

$claimed_by = isset($row['claimed_by']) ? (int)$row['claimed_by'] : 0;
$order_owner = (int)($row['user_id'] ?? 0);
if ($newStatusText === 'cancelled_by_admin') {
    $msg = $conn->real_escape_string("Pesanan #$order_id dibatalkan oleh $aname. Supplier diminta membuka kembali approval.");
    if ($claimed_by > 0) {
        @$conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES ($order_id,'$msg',$actor,$claimed_by,0)");
    } else {
        $resu = $conn->query("SELECT id FROM users WHERE role='supplier'");
        if ($resu) while ($r = $resu->fetch_assoc()) { $tid = (int)$r['id']; @$conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES ($order_id,'$msg',$actor,$tid,0)"); }
    }
} elseif ($newStatusText === 'cancelled_by_supplier') {
    $msg = $conn->real_escape_string("Pesanan #$order_id dibatalkan oleh supplier $aname.");
    $resa = $conn->query("SELECT id FROM users WHERE role IN ('admin','hrd')"); if ($resa) while ($ra = $resa->fetch_assoc()) { $tid = (int)$ra['id']; @$conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES ($order_id,'$msg',$actor,$tid,0)"); }
    if ($order_owner > 0) @$conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES ($order_id,'$msg',$actor,$order_owner,0)");
} elseif ($newStatusText === 'cancelled_by_user') {
    $msg = $conn->real_escape_string("Pesanan #$order_id dibatalkan oleh pemesan ($aname).");
    $resa = $conn->query("SELECT id FROM users WHERE role IN ('admin','hrd')"); if ($resa) while ($ra = $resa->fetch_assoc()) { $tid = (int)$ra['id']; @$conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES ($order_id,'$msg',$actor,$tid,0)"); }
    if ($claimed_by > 0) @$conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES ($order_id,'$msg',$actor,$claimed_by,0)");
}

echo json_encode(['ok'=>true]);
exit;

?>
