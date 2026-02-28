<?php require 'includes/header.php'; ?>
<?php require 'includes/sidebar.php'; ?>

<?php
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin','hrd','supplier','karyawan'])) {
  echo "<div class='container'><div class='alert alert-danger'>Akses ditolak</div></div>";
  require 'includes/footer.php';
  exit;
}
?>

<div class="d-flex justify-content-between mb-3">
  <h4>Menu Makanan</h4>
  <div>
    <?php if (in_array($role, ['admin','hrd','supplier'])): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Menu</button>
    <?php endif; ?>
  </div>
</div>

<div class="table-responsive">
<table id="menuTbl" class="display table table-striped" style="width:100%">
  <thead>
    <tr>
      <th>ID</th>
      <th>Nama</th>
      <th>Deskripsi</th>
      <th>Harga</th>
      <th>Supplier</th>
      <th>Disajikan Pada</th>
      <th>Status</th>
      <th>Dibuat</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" id="menuForm">
      <?= csrf_input() ?>
      <input type="hidden" name="id" value="0">
      <div class="modal-header"><h5 class="modal-title">Tambah / Ubah Menu</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label>Nama</label><input name="name" class="form-control" required></div>
        <div class="mb-3"><label>Deskripsi</label><textarea name="description" class="form-control"></textarea></div>
        <div class="mb-3"><label>Harga</label><input name="price" type="number" step="0.01" class="form-control" value="0"></div>
        <div class="mb-3"><label>Disajikan Pada (tanggal)</label><input name="available_date" type="date" class="form-control"></div>
        <div class="mb-3"><label>Supplier</label><select name="supplier_id" class="form-select"><option value="">-- Pilih --</option></select></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="menuSaveBtn">Simpan</button></div>
    </form>
  </div>
</div>

<script>
$(function(){
  var role = '<?= $_SESSION['role'] ?? '' ?>';
  var table = $('#menuTbl').DataTable({
    ajax: { url: 'api/menu.php', dataSrc: function(json){ if (!json) { showToastError('Server error','Invalid response'); return []; } if (json.error) { showToastError('Error', json.error || json.msg); return []; } return Array.isArray(json)?json:json.data; }, error:function(xhr,status){ try{var j=JSON.parse(xhr.responseText||'{}'); showToastError('Server error', j.error||j.msg||status);}catch(e){showToastError('Server error',status);} } },
    columns:[ {data:'id'},{data:'name'},{data:'description'},{data:'price',render:function(d){return parseFloat(d).toFixed(2);}}, {data:'supplier_name'},{data:'available_date',render:function(d){return d?d:'-';}}, {data:'is_active',render:function(d){return d==1?'<span class="badge bg-success">Active</span>':'<span class="badge bg-secondary">Inactive</span>'; }}, {data:'created_at'}, {data:null, orderable:false, searchable:false, render:function(r){ var s=''; if (role==='admin' || role==='hrd' || role==='supplier') s += '<button class="btn btn-sm btn-primary edit-menu" data-id="'+r.id+'">Edit</button> '; if (role==='admin' || role==='hrd') s += '<button class="btn btn-sm btn-danger del-menu" data-id="'+r.id+'">Delete</button>'; return s; } }]
  });

  function loadSuppliers(){
    $.get('api/suppliers.php').done(function(list){ var opts = '<option value="">-- Pilih --</option>'; list.forEach(function(s){ opts += '<option value="'+s.id+'">'+s.name+'</option>'; }); $('select[name=supplier_id]').html(opts); });
  }
  loadSuppliers();

  $('#menuForm').on('submit', function(e){ e.preventDefault(); var fd = $(this).serialize(); var id = $('input[name=id]').val(); var url = id>0 ? 'api/update_menu.php' : 'api/create_menu.php'; $('#menuSaveBtn').prop('disabled',true).text('Menyimpan...'); $.post(url, fd).done(function(res){ if (res.ok) { $('#addModal').modal('hide'); Swal.fire('Berhasil'); table.ajax.reload(); } else Swal.fire('Gagal', res.error || res.msg || 'Error'); }).fail(function(){ Swal.fire('Gagal','Server error'); }).always(function(){ $('#menuSaveBtn').prop('disabled',false).text('Simpan'); }); });

  $(document).on('click', '.edit-menu', function(){ var id = $(this).data('id'); $.get('api/menu.php').done(function(list){ var item = list.find(function(x){ return x.id == id; }); if (!item) return; $('input[name=id]').val(item.id); $('input[name=name]').val(item.name); $('textarea[name=description]').val(item.description); $('input[name=price]').val(item.price); $('input[name=available_date]').val(item.available_date || ''); $('select[name=supplier_id]').val(item.supplier_id); var m = new bootstrap.Modal(document.getElementById('addModal')); m.show(); }); });

  $(document).on('click', '.del-menu', function(){ var id = $(this).data('id'); Swal.fire({title:'Hapus?',icon:'warning',showCancelButton:true}).then(function(x){ if (x.isConfirmed){ var fd = new FormData(); fd.append('_csrf', document.querySelector('input[name="_csrf"]').value); fd.append('id', id); fetch('api/delete_menu.php',{method:'POST',body:fd}).then(r=>r.json()).then(function(j){ if (j.ok) { Swal.fire('Dihapus'); table.ajax.reload(); } else Swal.fire('Gagal'); }); } }); });
});
</script>

<?php require 'includes/footer.php'; ?>
