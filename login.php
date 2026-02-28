<?php
require 'config.php';
require_once __DIR__ . '/includes/csrf.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF verification
        if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) {
                $error = 'Token tidak valid. Silakan muat ulang halaman.';
        } else {
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $stmt = $conn->prepare('SELECT id,name,password,role FROM users WHERE email=?');
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows === 1) {
                        $stmt->bind_result($id, $name, $hash, $role);
                        $stmt->fetch();
                        if (password_verify($password, $hash)) {
                                $_SESSION['user_id'] = $id;
                                $_SESSION['name'] = $name;
                                $_SESSION['role'] = $role;
                                header('Location: dashboard.php');
                                exit;
                        } else {
                                $error = 'Email atau password salah.';
                        }
                } else {
                        $error = 'Email atau password salah.';
                }
        }
}

// site logo if uploaded
$site_logo = null;
$logo_path = __DIR__ . '/uploads/site_logo.png';
if (file_exists($logo_path)) $site_logo = 'uploads/site_logo.png';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - Pesanan Kantin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { background: linear-gradient(135deg,#0d6efd20 0%, #6c757d10 100%); }
        .login-card{ max-width:900px; margin-top:6vh; box-shadow:0 6px 30px rgba(0,0,0,0.08); }
        .brand { max-height:70px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card login-card">
                    <div class="row g-0">
                        <div class="col-md-6 d-flex align-items-center p-4 bg-light">
                            <div class="w-100 text-center">
                                <?php if ($site_logo): ?>
                                    <img src="<?= $site_logo ?>" class="brand mb-3" alt="Logo">
                                <?php else: ?>
                                    <h3 class="mb-3">Sistem Pesanan Kantin</h3>
                                <?php endif; ?>
                                <p class="text-muted">Masuk untuk memesan atau mengelola kantin pabrik.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card-body p-4">
                                <h5 class="card-title mb-3">Login</h5>
                                <?php if (!empty($error)): ?>
                                    <script>$(function(){ Swal.fire({icon:'error',title:'Gagal',text:<?= json_encode($error) ?>}); });</script>
                                <?php endif; ?>
                                <form method="post" action="#" id="loginForm">
                                    <?= csrf_input() ?>
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                    <div class="d-grid">
                                        <button class="btn btn-primary" id="loginBtn">Login</button>
                                    </div>
                                </form>
                                <script>
                                    $(function(){
                                        $('#loginForm').on('submit', function(e){
                                            e.preventDefault();
                                            var $btn = $('#loginBtn');
                                            $btn.prop('disabled', true).text('Memeriksa...');
                                            $.post('api/auth.php', $(this).serialize()).done(function(res){
                                                if (res.ok) {
                                                    Swal.fire({icon:'success',title:'Berhasil',text:'Login sukses'}).then(function(){
                                                        window.location.href = 'dashboard.php';
                                                    });
                                                } else {
                                                    Swal.fire({icon:'error',title:'Gagal',text: res.message || 'Login gagal'});
                                                }
                                            }).fail(function(xhr){
                                                var msg = 'Server error';
                                                try { var j = JSON.parse(xhr.responseText); if (j && j.message) msg = j.message; } catch(e){}
                                                Swal.fire({icon:'error',title:'Gagal',text: msg});
                                            }).always(function(){ $btn.prop('disabled', false).text('Login'); });
                                        });
                                    });
                                </script>
                                <hr>
                                <div class="text-center text-muted small">Hubungi HRD untuk pembuatan akun.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
