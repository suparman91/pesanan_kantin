<?php
require 'includes/header.php';
require 'includes/sidebar.php';

// only admin/hrd can manage suppliers
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
    $name = trim($_POST['name']); $contact = trim($_POST['contact']);
    $stmt = $conn->prepare('INSERT INTO suppliers (name,contact) VALUES (?,?)');
    $stmt->bind_param('ss',$name,$contact); $stmt->execute();
    header('Location: suppliers.php?msg=added'); exit;
  }
}

$sup = null;
?>

<div class="d-flex justify-content-between mb-3">
<div class="d-flex justify-content-between mb-3">
  <h4>Supplier Kantin</h4>
  <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Supplier</button>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg']=='added'): ?>
  <script>Swal.fire({icon:'success',title:'Berhasil',text:'Supplier ditambahkan'});</script>
<?php endif; ?>

<table id="tbl2" class="display table table-striped" style="width:100%">
  <thead><tr><th>ID</th><th>Nama</th><th>Kontak</th><th>Dibuat</th></tr></thead>
  <tbody></tbody>
</table>

<!-- modal add -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content" id="supplierAddForm">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title">Tambah Supplier</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label>Nama</label><input name="name" class="form-control" required></div>
        <div class="mb-3"><label>Kontak</label><input name="contact" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-success" id="supplierSaveBtn">Simpan</button></div>
    </form>
  </div>
</div>

<script>
  $(document).ready(function(){
    var table2 = $('#tbl2').DataTable({
      ajax: 'api/suppliers.php',
      columns: [
        { data: 'id' },
        { data: 'name' },
        { data: 'contact' },
        { data: 'created_at' }
      ]
    });

    $('#supplierAddForm').on('submit', function(e){
      e.preventDefault();
      var fd = $(this).serialize();
      $('#supplierSaveBtn').prop('disabled',true).text('Menyimpan...');
      $.post('api/create_supplier.php', fd).done(function(res){
        if (res.ok) {
          $('#addModal').modal('hide');
          Swal.fire({icon:'success',title:'Berhasil',text:'Supplier ditambahkan'});
          table2.ajax.reload(null,false);
        } else {
          Swal.fire({icon:'error',title:'Gagal',text:res.error || 'Error'});
        }
      }).fail(function(){ Swal.fire({icon:'error',title:'Gagal',text:'Server error'}); }).always(function(){ $('#supplierSaveBtn').prop('disabled',false).text('Simpan'); });
    });
  });
</script>

<?php require 'includes/footer.php'; ?>
