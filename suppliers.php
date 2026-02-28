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

<div class="row align-items-center mb-3">
  <div class="col-md-6">
    <h4 class="mb-0">Supplier Kantin</h4>
  </div>
  <div class="col-md-6 text-md-end mt-2 mt-md-0">
    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Supplier</button>
  </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg']=='added'): ?>
  <script>Swal.fire({icon:'success',title:'Berhasil',text:'Supplier ditambahkan'});</script>
<?php endif; ?>

<div class="table-responsive">
<table id="tbl2" class="display table table-striped" style="width:100%">
  <thead><tr><th>ID</th><th>Nama</th><th>Kontak</th><th>Dibuat</th></tr></thead>
  <tbody></tbody>
</table>
</div>

<!-- modal add -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content" id="supplierAddForm">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title">Tambah Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
      ajax: {
        url: 'api/suppliers.php',
        crossDomain: false,
        cache: false,
        timeout: 10000,
        xhrFields: { withCredentials: true },
        dataSrc: function(json){
          if (!json) { showToastError('Server error','Invalid response'); return []; }
          if (json.error) { showToastError('Error', json.error||json.msg||'API error'); return []; }
          return Array.isArray(json) ? json : (json.data || json.suppliers || json);
        },
        error: function(xhr, status){
          // status may be 'timeout', 'error', 'abort', 'parsererror'
          var msg = status || 'error';
          try { var j = JSON.parse(xhr.responseText || '{}'); if (j.error || j.msg) msg = (j.error || j.msg); } catch(e) {}
          // status 0 usually means network error, blocked request, or CORS/extension interference
          if (xhr && xhr.status === 0) {
            msg = 'Network error or request blocked (status 0). Try disabling browser extensions or open in Incognito to test.';
          } else if (xhr && xhr.status >= 400) {
            msg = 'Server error: ' + xhr.status + ' ' + (xhr.statusText || '') + '\n' + (xhr.responseText ? xhr.responseText.substring(0,200) : '');
          }
          console.error('suppliers API error', {statusText: status, httpStatus: xhr.status, responseText: xhr.responseText, xhr: xhr});
          // fallback: if status==0, attempt a direct fetch to get a clearer error
          if (xhr && xhr.status === 0 && window.fetch) {
            fetch('api/suppliers.php', {credentials:'same-origin'}).then(function(r){
              return r.text().then(function(t){
                console.warn('fetch fallback response:', r.status, t.substring(0,500));
                showToastError('Server error', 'Network/request blocked (status 0). Try disabling extensions or open in Incognito.');
              });
            }).catch(function(e){
              console.error('fetch fallback failed', e);
              showToastError('Server error',msg);
            });
          } else {
            showToastError('Server error',msg);
          }
        }
      },
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
