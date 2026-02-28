<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
if (session_status() == PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error'=>'method']);
    exit;
}

$data = $_POST;
// Enforce CSRF only when the current session already has a token.
// This allows users on a new/expired session to submit the login form
// (e.g. they loaded the page earlier and the server-side session expired),
// while still protecting authenticated sessions.
if (!empty($_SESSION['_csrf_token'])) {
    if (empty($data['_csrf']) || !csrf_verify($data['_csrf'])) {
        http_response_code(400);
        echo json_encode(['error'=>'csrf','message'=>'Token tidak valid']);
        exit;
    }
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error'=>'invalid','message'=>'Email dan password diperlukan']);
    exit;
}

if ($conn === null) {
    http_response_code(500);
    echo json_encode(['error'=>'db_connect','message'=>'Database connection error']);
    exit;
}

$stmt = $conn->prepare('SELECT id,name,password,role FROM users WHERE email=?');
if (! $stmt) {
    http_response_code(500);
    echo json_encode(['error'=>'db','message'=>$conn->error]);
    exit;
}
$stmt->bind_param('s', $email);
if (! $stmt->execute()) {
    http_response_code(500);
    echo json_encode(['error'=>'db','message'=>$stmt->error]);
    exit;
}
$stmt->store_result();
if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $name, $hash, $role);
    $stmt->fetch();
    if (password_verify($password, $hash)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['name'] = $name;
        $_SESSION['role'] = $role;
        echo json_encode(['ok'=>true,'redirect'=>'dashboard']);
        exit;
    }
}

http_response_code(401);
echo json_encode(['error'=>'auth','message'=>'Email atau password salah']);
exit;
