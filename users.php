<?php
require 'includes/header.php';
require 'includes/sidebar.php';

// only admin/HRD
if (!in_array($_SESSION['role'], ['admin','hrd'])) {
  echo "<script>Swal.fire({icon:'error',title:'Akses ditolak',text:'Hanya admin/HRD'}).then(()=>location='dashboard.php');</script>";
  exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
  if ($_POST['action']==='add') {
    if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) {
      echo "<script>Swal.fire({icon:'error',title:'Token tidak valid'});</script>";
      exit;
    }
    $name = trim($_POST['name']); $email = trim($_POST['email']); $role = $_POST['role'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)');
    $stmt->bind_param('ssss',$name,$email,$pass,$role); $stmt->execute();
    header('Location: users.php?msg=added'); exit;
  }
  if ($_POST['action']==='upload_logo' && $_SESSION['role']==='admin') {
    if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) {
      echo "<script>Swal.fire({icon:'error',title:'Token tidak valid'});</script>";
      exit;
    }
    if (!empty($_FILES['site_logo']) && $_FILES['site_logo']['error']===UPLOAD_ERR_OK) {
      $f = $_FILES['site_logo'];
      $allowed = ['image/png','image/jpeg','image/jpg'];
      if ($f['size'] > 2*1024*1024) { echo "<script>Swal.fire({icon:'error',title:'File terlalu besar'});</script>"; exit; }
      if (!in_array($f['type'], $allowed)) { echo "<script>Swal.fire({icon:'error',title:'Tipe file tidak didukung'});</script>"; exit; }
      $updir = __DIR__ . '/uploads';
      if (!is_dir($updir)) mkdir($updir,0755,true);
      $dest = $updir . '/site_logo.png';
      // move and normalize to png if needed (simple move)
      move_uploaded_file($f['tmp_name'], $dest);
      header('Location: users.php?msg=logo'); exit;
    } else {
      echo "<script>Swal.fire({icon:'error',title:'Upload gagal'});</script>"; exit;
    }
  }
}

$users = null;
?>

<div class="d-flex justify-content-between mb-3">
  <h4>Kelola Pengguna</h4>
  <div>
    <button class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Pengguna</button>
    <?php if ($_SESSION['role']==='admin'): ?>
      <button class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#logoModal">Upload Logo</button>
    <?php endif; ?>
  </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg']=='added'): ?>
  <script>Swal.fire({icon:'success',title:'Berhasil',text:'Pengguna ditambahkan'});</script>
<?php endif; ?>

<table id="tbl3" class="display table table-striped" style="width:100%">
  <thead><tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Dibuat</th></tr></thead>
  <tbody></tbody>
</table>

<!-- modal add -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content" id="userAddForm">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title">Tambah Pengguna</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label>Nama</label><input name="name" class="form-control" required></div>
        <div class="mb-3"><label>Email</label><input name="email" type="email" class="form-control" required></div>
        <div class="mb-3"><label>Password</label><input name="password" type="password" class="form-control" required></div>
        <div class="mb-3"><label>Role</label><select name="role" class="form-select"><option value="karyawan">karyawan</option><option value="hrd">hrd</option><option value="admin">admin</option><option value="supplier">supplier</option></select></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="userSaveBtn">Simpan</button></div>
    </form>
  </div>
</div>

<?php if ($_SESSION['role']==='admin'): ?>
<!-- modal upload logo -->
<div class="modal fade" id="logoModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" enctype="multipart/form-data" class="modal-content">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="upload_logo">
      <div class="modal-header"><h5 class="modal-title">Upload Site Logo</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label>Pilih file (PNG/JPEG, max 2MB)</label><input type="file" name="site_logo" accept="image/*" class="form-control" required></div>
      </div>
      <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Upload</button></div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
  $(document).ready(function(){
    var table3 = $('#tbl3').DataTable({
      ajax: 'api/users.php',
      columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'email' },
        { data: 'role' },
        { data: 'created_at' }
      ]
    });

    $('#userAddForm').on('submit', function(e){
      e.preventDefault();
      var fd = $(this).serialize();
      $('#userSaveBtn').prop('disabled',true).text('Menyimpan...');
      $.post('api/create_user.php', fd).done(function(res){
        if (res.ok) {
          $('#addModal').modal('hide');
          Swal.fire({icon:'success',title:'Berhasil',text:'Pengguna ditambahkan'});
          table3.ajax.reload(null,false);
        } else {
          Swal.fire({icon:'error',title:'Gagal',text:res.error || res.msg || 'Error'});
        }
      }).fail(function(){ Swal.fire({icon:'error',title:'Gagal',text:'Server error'}); }).always(function(){ $('#userSaveBtn').prop('disabled',false).text('Simpan'); });
    });
  });
</script>

<?php require 'includes/footer.php'; ?>
