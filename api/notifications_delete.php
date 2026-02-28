<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error'=>'unauth']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }
if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) { http_response_code(400); echo json_encode(['error'=>'csrf']); exit; }
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin','hrd'])) { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { http_response_code(400); echo json_encode(['error'=>'invalid']); exit; }
$stmt = $conn->prepare('DELETE FROM notifications WHERE id=?');
$stmt->bind_param('i',$id);
if ($stmt->execute()) echo json_encode(['ok'=>true]); else { http_response_code(500); echo json_encode(['error'=>'db']); }
