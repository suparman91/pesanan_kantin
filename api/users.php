<?php
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
if (!in_array($_SESSION['role'] ?? '', ['admin','hrd'])) { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }

$res = $conn->query('SELECT id,name,email,role,created_at FROM users ORDER BY created_at DESC');
if ($res === false) {
	http_response_code(500);
	echo json_encode(['error'=>'db','msg'=>$conn->error ?? 'query_failed']);
	exit;
}

$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;
echo json_encode($out);
exit;
