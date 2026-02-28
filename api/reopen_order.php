<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) { http_response_code(400); echo json_encode(['error'=>'csrf']); exit; }

$role = $_SESSION['role'] ?? '';
$me = (int)($_SESSION['user_id'] ?? 0);
$order_id = (int)($_POST['order_id'] ?? 0);
if ($order_id <= 0) { http_response_code(400); echo json_encode(['error'=>'invalid']); exit; }

$logdir = __DIR__ . '/../logs'; if (!is_dir($logdir)) @mkdir($logdir,0755,true);
$logfile = $logdir . '/api_reopen_order.log';
function _rlog($m){ global $logfile,$me,$role,$order_id; @file_put_contents($logfile, date('c') . " | user_id=" . ($me??'0') . " | role=" . ($role??'') . " | order_id=" . ($order_id??'0') . " | msg=" . $m . "\n", FILE_APPEND | LOCK_EX); }
_rlog('entered');

// load order
$colsRes = $conn->query("SHOW COLUMNS FROM orders"); $cols=[]; if ($colsRes) while ($c=$colsRes->fetch_assoc()) $cols[]=$c['Field'];
$select = ['id','status','status_code','user_id','created_at','order_date'];
if (in_array('claimed_by',$cols)) $select[]='claimed_by';
if (in_array('supplier_id',$cols)) $select[]='supplier_id';
if (in_array('approved_at',$cols)) $select[]='approved_at';
if (in_array('approved_by',$cols)) $select[]='approved_by';

$q = $conn->prepare('SELECT ' . implode(',', $select) . ' FROM orders WHERE id=?');
if (!$q) { _rlog('prepare_failed: '.$conn->error); http_response_code(500); echo json_encode(['error'=>'db','msg'=>$conn->error]); exit; }
$q->bind_param('i',$order_id); $q->execute(); $res=$q->get_result(); if (!$res || !$row=$res->fetch_assoc()) { http_response_code(404); echo json_encode(['error'=>'notfound']); exit; }

$legacy = $row['status'] ?? '';
$code = isset($row['status_code']) ? intval($row['status_code']) : order_status_code_from_legacy($legacy);
$confirmed = defined('ORDER_STATUS_CONFIRMED') ? ORDER_STATUS_CONFIRMED : 1;
$open = defined('ORDER_STATUS_OPEN') ? ORDER_STATUS_OPEN : 0;

// only allow reopen if status was cancelled_by_admin or cancelled_by_user
$wasCancelledByAdmin = (strtolower($row['status'] ?? '') === 'cancelled_by_admin') || (strtolower($row['status_label'] ?? '') === 'cancelled_by_admin');
$wasCancelledByUser = (strtolower($row['status'] ?? '') === 'cancelled_by_user') || (strtolower($row['status_label'] ?? '') === 'cancelled_by_user');
if (!($wasCancelledByAdmin || $wasCancelledByUser)) { _rlog('not_cancelled_state'); http_response_code(409); echo json_encode(['error'=>'not_cancelled']); exit; }

// if supplier already approved/confirmed, disallow reopen
if ($code === $confirmed) { _rlog('already_confirmed'); http_response_code(409); echo json_encode(['error'=>'already_accepted']); exit; }

// check H+1 window
$od = $row['order_date'] ?: $row['created_at'];
if ($od) {
    try {
        $dt = new DateTime($od); $cut = clone $dt; $cut->modify('+1 day');
        $now = new DateTime('now');
        if ($now > $cut) { _rlog('reopen_window_passed'); http_response_code(409); echo json_encode(['error'=>'reopen_window_passed']); exit; }
    } catch(Exception $e) {}
}

// permission: admin, order owner, or supplier claimant/assigned
$claimed = isset($row['claimed_by']) ? (int)$row['claimed_by'] : 0;
$supplierAssigned = isset($row['supplier_id']) ? (int)$row['supplier_id'] : 0;
$orderOwner = (int)$row['user_id'];
if (!($role === 'admin' || $orderOwner === $me || ($role === 'supplier' && ($claimed === $me || $supplierAssigned === $me)))) { _rlog('no_permission'); http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }

// perform reopen: set status_code to OPEN and clear claimant/supplier so UI returns to normal
try {
    $sets = ['status_code = ?']; $types = 'i'; $vals = [$open];
    if (in_array('status',$cols)) { $sets[] = 'status = ?'; $types .= 's'; $vals[] = 'open'; }
    if (in_array('claimed_by',$cols)) $sets[] = 'claimed_by = NULL';
    if (in_array('supplier_id',$cols)) $sets[] = 'supplier_id = NULL';
    $sql = 'UPDATE orders SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $stmt = $conn->prepare($sql); if (!$stmt) throw new Exception($conn->error);
    $bindTypes = $types . 'i'; $bindVals = array_merge($vals, [$order_id]);
    $stmt->bind_param($bindTypes, ...$bindVals);
    if (!$stmt->execute()) throw new Exception($stmt->error);
} catch(Exception $e) { _rlog('update_failed: '.$e->getMessage()); http_response_code(500); echo json_encode(['error'=>'db','msg'=>'update_failed']); exit; }

// notify parties (best-effort)
$actor = $me; $aname=''; $u = $conn->prepare('SELECT name FROM users WHERE id=?'); if ($u) { $u->bind_param('i',$actor); $u->execute(); $r=$u->get_result(); if ($rr=$r->fetch_assoc()) $aname=$rr['name']; }
$msg = $conn->real_escape_string("Pesanan #$order_id dibuka kembali oleh $aname.");
// notify admin/hrd
$resu = $conn->query("SELECT id FROM users WHERE role IN ('admin','hrd')"); if ($resu) while($ru=$resu->fetch_assoc()){ $tid=(int)$ru['id']; @$conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES ($order_id,'$msg',$actor,$tid,0)"); }

_rlog('ok'); echo json_encode(['ok'=>true]); exit;

?>
<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }
if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) { http_response_code(400); echo json_encode(['error'=>'csrf']); exit; }

$role = $_SESSION['role'] ?? '';
if ($role !== 'supplier') { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }

$order_id = (int)($_POST['order_id'] ?? 0);
if ($order_id <= 0) { http_response_code(400); echo json_encode(['error'=>'invalid']); exit; }

$q = $conn->prepare('SELECT id,status,status_code,claimed_by,user_id FROM orders WHERE id=?');
$q->bind_param('i',$order_id);
$q->execute();
$res = $q->get_result();
if (!$row = $res->fetch_assoc()) { http_response_code(404); echo json_encode(['error'=>'notfound']); exit; }
$legacyStatus = $row['status'] ?? '';
$statusCode = isset($row['status_code']) ? intval($row['status_code']) : order_status_code_from_legacy($legacyStatus);

$claimed_by = (int)($row['claimed_by'] ?? 0);
$me = (int)$_SESSION['user_id'];
if ($claimed_by !== $me) { http_response_code(403); echo json_encode(['error'=>'not_permitted']); exit; }

// only allow reopen if the order appears to be admin-cancelled or closed
$closedCode = defined('ORDER_STATUS_CLOSED') ? ORDER_STATUS_CLOSED : 2;
if (!($legacyStatus === 'cancelled_by_admin' || $statusCode === $closedCode)) { http_response_code(409); echo json_encode(['error'=>'invalid_status']); exit; }

// set back to claimed and open status_code
$openCode = defined('ORDER_STATUS_OPEN') ? ORDER_STATUS_OPEN : 0;
$hasStatusCol = $conn->query("SHOW COLUMNS FROM orders LIKE 'status'");
if ($hasStatusCol && $hasStatusCol->num_rows) {
    $u = $conn->prepare('UPDATE orders SET status_code=?, status=? WHERE id=?');
    $newStatusText = 'claimed';
    $u->bind_param('isi',$openCode,$newStatusText,$order_id);
} else {
    $u = $conn->prepare('UPDATE orders SET status_code=? WHERE id=?');
    $u->bind_param('ii',$openCode,$order_id);
}
if (!$u->execute()) { http_response_code(500); echo json_encode(['error'=>'db','msg'=>$conn->error]); exit; }

// ensure notifications table exists
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    message VARCHAR(255),
    created_by INT,
    target_user INT DEFAULT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$col = $conn->query("SHOW COLUMNS FROM notifications LIKE 'is_read'");
if (!$col || $col->num_rows === 0) { $conn->query("ALTER TABLE notifications ADD COLUMN is_read TINYINT(1) DEFAULT 0"); }
$col2 = $conn->query("SHOW COLUMNS FROM notifications LIKE 'target_user'");
if (!$col2 || $col2->num_rows === 0) { $conn->query("ALTER TABLE notifications ADD COLUMN target_user INT DEFAULT NULL"); }

$actor = $me;
$aname = '';
$usrq = $conn->prepare('SELECT name FROM users WHERE id=?'); $usrq->bind_param('i',$actor); $usrq->execute(); $usrres = $usrq->get_result(); if ($urow = $usrres->fetch_assoc()) $aname = $urow['name'];

$msg = $conn->real_escape_string("Supplier $aname membuka kembali approval untuk Pesanan #$order_id");

// notify all admins/hrd
$resu = $conn->query("SELECT id FROM users WHERE role IN ('admin','hrd')");
while ($r = $resu->fetch_assoc()) {
    $tid = (int)$r['id'];
    $conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES ($order_id,'$msg',$actor,$tid,0)");
}

echo json_encode(['ok'=>true]);
exit;

?>
