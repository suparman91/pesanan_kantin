<?php
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
// try to return recent notifications if table exists
$role = $_SESSION['role'] ?? '';
$check = $conn->query("SHOW TABLES LIKE 'notifications'");
if ($check && $check->num_rows) {
    $uid = (int)($_SESSION['user_id'] ?? 0);
    $res = $conn->query("SELECT id,order_id,message,created_by,target_user,is_read,created_at FROM notifications WHERE (target_user IS NULL OR target_user = $uid) ORDER BY created_at DESC LIMIT 10");
    if ($res === false) {
        http_response_code(500);
        echo json_encode(['error'=>'db','msg'=>$conn->error ?? 'query_failed']);
        exit;
    }
    $out = [];
    while ($r = $res->fetch_assoc()) $out[] = $r;
    $unreadRes = $conn->query("SELECT COUNT(*) as c FROM notifications WHERE is_read=0 AND (target_user IS NULL OR target_user = $uid)");
    $unread = 0;
    if ($unreadRes && ($row = $unreadRes->fetch_assoc())) $unread = (int)$row['c'];
    echo json_encode(['notifications'=>$out,'unread'=> $unread]);
    exit;
}

// fallback: counts
$q = $conn->prepare("SELECT COUNT(*) as c FROM orders WHERE status='pending'");
$q->execute(); $res = $q->get_result()->fetch_assoc();
echo json_encode(['pending' => (int)$res['c']]);
