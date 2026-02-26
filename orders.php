<?php require 'includes/header.php'; ?>
<?php require 'includes/sidebar.php'; ?>

<?php
// tambah pesanan (karyawan)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
  // CSRF check
  if (empty($_POST['_csrf']) || !csrf_verify($_POST['_csrf'])) {
    echo "<script>Swal.fire({icon:'error',title:'Token tidak valid'});</script>";
  } else {
    $item = trim($_POST['item']);
    $qty = max(1, (int)$_POST['quantity']);
    $price = max(0, (float)$_POST['total_price']);
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare('INSERT INTO orders (user_id,item,quantity,total_price) VALUES (?,?,?,?)');
    $stmt->bind_param('isid', $user_id, $item, $qty, $price);
    $stmt->execute();
    header('Location: orders.php?msg=added');
    exit;
  }
}

?>

<div class="d-flex justify-content-between mb-3">
  <h4>Daftar Pesanan</h4>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Pesanan</button>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg']=='added'): ?>
  <script>Swal.fire({icon:'success',title:'Berhasil',text:'Pesanan ditambahkan'});</script>
<?php endif; ?>

<table id="tbl" class="display table table-striped" style="width:100%">
  <thead>
    <tr><th>ID</th><th>User</th><th>Item</th><th>Qty</th><th>Total</th><th>Status</th><th>Tanggal</th></tr>
  </thead>
  <tbody></tbody>
</table>

<!-- Modal add -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content" id="orderAddForm">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title">Tambah Pesanan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label>Item</label><input name="item" class="form-control" required></div>
        <div class="mb-3"><label>Quantity</label><input name="quantity" type="number" value="1" class="form-control" required></div>
        <div class="mb-3"><label>Total Price</label><input name="total_price" type="number" step="0.01" value="0" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="orderSaveBtn">Simpan</button></div>
    </form>
  </div>
</div>

<script>
  $(document).ready(function(){
    var table = $('#tbl').DataTable({
      ajax: 'api/orders.php',
      columns: [
        { data: 'id' },
        { data: 'user_name' },
        { data: 'item' },
        { data: 'quantity' },
        { data: 'total_price', render: function(d){ return parseFloat(d).toFixed(2); } },
        { data: 'status' },
        { data: 'created_at' }
      ]
    });

    $('#orderAddForm').on('submit', function(e){
      e.preventDefault();
      var fd = $(this).serialize();
      $('#orderSaveBtn').prop('disabled',true).text('Menyimpan...');
      $.post('api/create_order.php', fd).done(function(res){
        if (res.ok) {
          $('#addModal').modal('hide');
          Swal.fire({icon:'success',title:'Berhasil',text:'Pesanan ditambahkan'});
          table.ajax.reload(null,false);
        } else {
          Swal.fire({icon:'error',title:'Gagal',text:res.error || 'Error'});
        }
      }).fail(function(){ Swal.fire({icon:'error',title:'Gagal',text:'Server error'}); }).always(function(){ $('#orderSaveBtn').prop('disabled',false).text('Simpan'); });
    });
  });
</script>

<?php require 'includes/footer.php'; ?>
