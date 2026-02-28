<?php require 'includes/header.php'; ?>
<?php require 'includes/sidebar.php'; ?>

<?php
$role = $_SESSION['role'] ?? '';
if (!in_array($role, ['admin','hrd'])) {
  echo "<div class='container'><div class='alert alert-danger'>Akses ditolak</div></div>";
  require 'includes/footer.php';
  exit;
}
?>

<div class="d-flex justify-content-between mb-3">
  <h4>Manajemen Notifikasi</h4>
  <div>
    <button id="refreshBtn" class="btn btn-sm btn-outline-secondary">Refresh</button>
    <button id="markAllBtn" class="btn btn-sm btn-secondary">Tandai semua terbaca</button>
  </div>
</div>

<table id="notifTbl" class="display table table-striped" style="width:100%">
  <thead>
    <tr><th>ID</th><th>Order</th><th>Pesan</th><th>Dibuat Oleh</th><th>Untuk</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr>
  </thead>
  <tbody></tbody>
</table>

<script>
$(document).ready(function(){
  var table = $('#notifTbl').DataTable({
    ajax: {
      url: 'api/notifications_admin.php',
      dataSrc: function(json){
        if (!json) { showToastError('Server error','Invalid response'); return []; }
        if (json.error) { showToastError('Error', json.error||json.msg||'API error'); return []; }
        return Array.isArray(json) ? json : (json.data || json.notifications || json);
      },
      error: function(xhr, status){
        var msg = status;
        try {
          var j = JSON.parse(xhr.responseText || '{}');
          if (j.error || j.msg) msg = (j.error || j.msg);
        } catch(e) {
          // leave msg as status
        }
        console.error('notifications_admin error', status, xhr.status, xhr.responseText);
        showToastError('Server error',msg);
      }
    },
    columns: [
      { data: 'id' },
      { data: 'order_id', render:function(d){ return d? ('#'+d): '' } },
      { data: 'message' },
      { data: 'created_by_name' },
      { data: 'target_user_name', render:function(d){ return d? d : 'All' } },
      { data: 'is_read', render:function(d){ return d==0 ? '<span class="badge bg-warning text-dark">Unread</span>' : '<span class="badge bg-secondary">Read</span>' } },
      { data: 'created_at' },
      { data: null, orderable:false, searchable:false, render:function(r){
           var s = '<button class="btn btn-sm btn-success mark-read" data-id="'+r.id+'">Mark Read</button> ';
           s += '<button class="btn btn-sm btn-danger del-notif" data-id="'+r.id+'">Delete</button>';
           return s;
      }}
    ]
  });

  $('#refreshBtn').on('click', function(){ table.ajax.reload(); });
  $('#markAllBtn').on('click', function(){
    var fd = new FormData(); fd.append('_csrf', document.querySelector('input[name="_csrf"]').value); fd.append('all','1');
    fetch('api/notifications_mark_read.php', {method:'POST', body:fd}).then(()=>table.ajax.reload());
  });

  $(document).on('click', '.mark-read', function(){
    var id = $(this).data('id');
    var fd = new FormData(); fd.append('_csrf', document.querySelector('input[name="_csrf"]').value); fd.append('id', id);
    fetch('api/notifications_mark_read.php', {method:'POST', body:fd}).then(()=>table.ajax.reload());
  });

  $(document).on('click', '.del-notif', function(){
    var id = $(this).data('id');
    Swal.fire({title:'Hapus notifikasi?',icon:'warning',showCancelButton:true}).then(function(x){
      if (x.isConfirmed) {
        var fd = new FormData(); fd.append('_csrf', document.querySelector('input[name="_csrf"]').value); fd.append('id', id);
        fetch('api/notifications_delete.php', {method:'POST', body:fd}).then(r=>r.json()).then(function(j){ if (j.ok) table.ajax.reload(); else Swal.fire('Gagal'); });
      }
    });
  });
});
</script>

<?php require 'includes/footer.php'; ?>
