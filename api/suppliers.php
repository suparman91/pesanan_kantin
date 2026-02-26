<?php
require_once __DIR__ . '/../config.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) {
    http_response_code(401); echo json_encode(['error'=>'unauth']); exit;
}

// only admin/hrd can access full list
if (!in_array($_SESSION['role'] ?? '', ['admin','hrd'])) {
    http_response_code(403); echo json_encode(['error'=>'forbidden']); exit;
}

$res = $conn->query('SELECT * FROM suppliers ORDER BY created_at DESC');
$out = [];
while ($r = $res->fetch_assoc()) $out[] = $r;
echo json_encode($out);
