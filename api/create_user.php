<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/csrf.php';
if (session_status() == PHP_SESSION_NONE) session_start();
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
// notify admin/hrd about new user
$newId = $stmt->insert_id;
$conn->query("CREATE TABLE IF NOT EXISTS notifications (
	id INT AUTO_INCREMENT PRIMARY KEY,
	order_id INT,
	message VARCHAR(255),
	created_by INT,
	target_user INT DEFAULT NULL,
	is_read TINYINT(1) DEFAULT 0,
	created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$col = $conn->query("SHOW COLUMNS FROM notifications LIKE 'target_user'");
if (!$col || $col->num_rows === 0) {
	$conn->query("ALTER TABLE notifications ADD COLUMN target_user INT DEFAULT NULL");
}
$admins = $conn->query("SELECT id,name FROM users WHERE role IN ('admin','hrd')");
$msg = $conn->real_escape_string("Pengguna baru: $name ($role)");
if ($admins) {
	while ($a = $admins->fetch_assoc()) {
		$t = (int)$a['id'];
		$conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES (NULL,'$msg',{$_SESSION['user_id']},$t,0)");
	}
} else {
	$conn->query("INSERT INTO notifications (order_id,message,created_by,target_user,is_read) VALUES (NULL,'$msg',{$_SESSION['user_id']},NULL,0)");
}
