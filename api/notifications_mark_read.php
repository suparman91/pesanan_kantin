<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }
if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) { http_response_code(400); echo json_encode(['error'=>'csrf']); exit; }

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$all = isset($_POST['all']) && $_POST['all'] === '1';

// ensure table exists
$check = $conn->query("SHOW TABLES LIKE 'notifications'");
if (!$check || $check->num_rows === 0) { http_response_code(404); echo json_encode(['error'=>'no_table']); exit; }

if ($all) {
    // admins/hrd may mark all; otherwise only their notifications
    $role = $_SESSION['role'] ?? '';
    if (in_array($role, ['admin','hrd'])) {
        $stmt = $conn->prepare('UPDATE notifications SET is_read=1 WHERE is_read=0');
        if ($stmt->execute()) echo json_encode(['ok'=>true]); else { http_response_code(500); echo json_encode(['error'=>'db']); }
    } else {
        $uid = (int)$_SESSION['user_id'];
        $stmt = $conn->prepare('UPDATE notifications SET is_read=1 WHERE is_read=0 AND (target_user IS NULL OR target_user = ?)');
        $stmt->bind_param('i',$uid);
        if ($stmt->execute()) echo json_encode(['ok'=>true]); else { http_response_code(500); echo json_encode(['error'=>'db']); }
    }
    exit;
}

if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'invalid']); exit; }
$role = $_SESSION['role'] ?? '';
if (in_array($role, ['admin','hrd'])) {
    $stmt = $conn->prepare('UPDATE notifications SET is_read=1 WHERE id=?');
    $stmt->bind_param('i',$id);
} else {
    $uid = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND (target_user IS NULL OR target_user = ?)');
    $stmt->bind_param('ii',$id,$uid);
}
if ($stmt->execute()) echo json_encode(['ok'=>true]); else { http_response_code(500); echo json_encode(['error'=>'db']); }
