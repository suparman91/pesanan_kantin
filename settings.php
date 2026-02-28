<?php require 'includes/header.php'; ?>
<?php require 'includes/sidebar.php'; ?>

<?php
// only admin/hrd
if (!in_array($_SESSION['role'] ?? '', ['admin','hrd'])) {
  echo "<div class='container'><div class='alert alert-danger'>Akses ditolak</div></div>";
  require 'includes/footer.php';
  exit;
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_once __DIR__ . '/includes/csrf.php';
  if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) {
    $msg = 'Token CSRF tidak valid';
  } else {
    if (!isset($_FILES['site_logo']) || $_FILES['site_logo']['error'] !== UPLOAD_ERR_OK) {
      $msg = 'File tidak diupload';
    } else {
      $f = $_FILES['site_logo'];
      // validate
      if ($f['size'] > 2 * 1024 * 1024) { $msg = 'File terlalu besar (max 2MB)'; }
      else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($f['tmp_name']);
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
        if (!isset($allowed[$mime])) { $msg = 'Tipe file tidak diizinkan'; }
        else {
          $ext = $allowed[$mime];
          $uploads = __DIR__ . '/uploads';
          if (!is_dir($uploads)) mkdir($uploads, 0755, true);
          $dest = $uploads . '/site_logo.' . $ext;
          if (move_uploaded_file($f['tmp_name'], $dest)) {
            // normalize to site_logo.png for header lookup (prefer png)
            if ($ext !== 'png') {
              // convert jpg to png if possible by copying (keep original extension too)
              @copy($dest, $uploads . '/site_logo.' . $ext);
            }
            // also write a canonical site_logo.png if PNG, otherwise copy
            if ($ext === 'png') {
              copy($dest, $uploads . '/site_logo.png');
            } else {
              // attempt to convert via GD if available
              if (function_exists('imagecreatefromjpeg') && function_exists('imagepng')) {
                $img = imagecreatefromjpeg($dest);
                if ($img) { imagepng($img, $uploads . '/site_logo.png'); imagedestroy($img); }
              } else {
                copy($dest, $uploads . '/site_logo.png');
              }
            }
            $msg = 'Logo berhasil diunggah';
          } else $msg = 'Gagal memindahkan file';
        }
      }
    }
  }
}

$site_logo = null;
$logo_path = __DIR__ . '/uploads/site_logo.png';
if (file_exists($logo_path)) $site_logo = 'uploads/site_logo.png';
?>

<div class="mb-3 d-flex align-items-center">
  <h4 class="me-auto">Pengaturan Situs</h4>
  <?php if ($msg): ?>
    <div class="alert alert-info ms-3"><?= esc($msg) ?></div>
  <?php endif; ?>
</div>

<div class="card mb-3">
  <div class="card-body">
    <h5>Logo Situs</h5>
    <?php if ($site_logo): ?>
      <div class="mb-3"><img src="<?= $site_logo ?>" alt="Logo" style="max-height:80px;object-fit:contain;border:1px solid #ddd;padding:6px;background:#fff;"></div>
    <?php else: ?>
      <div class="mb-3 text-muted">Belum ada logo terpasang.</div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <?= csrf_input() ?>
      <div class="mb-3"><input type="file" name="site_logo" accept="image/png,image/jpeg" class="form-control"></div>
      <div><button class="btn btn-primary">Unggah Logo</button></div>
    </form>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
