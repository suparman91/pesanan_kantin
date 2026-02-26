<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','hrd'])) { http_response_code(403); echo json_encode(['error'=>'forbidden']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'method']); exit; }
if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) { http_response_code(400); echo json_encode(['error'=>'csrf']); exit; }

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? 'password';
$role = $_POST['role'] ?? 'karyawan';
if ($name==='' || $email==='') { http_response_code(400); echo json_encode(['error'=>'required']); exit; }

$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
$stmt->bind_param('ssss',$name,$email,$hash,$role);
if ($stmt->execute()) echo json_encode(['ok'=>true,'id'=>$stmt->insert_id]);
else { http_response_code(500); echo json_encode(['error'=>'db','msg'=>$conn->error]); }
