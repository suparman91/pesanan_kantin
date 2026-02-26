<?php require 'includes/header.php'; ?>
<?php require 'includes/sidebar.php'; ?>

<?php
// show supplier-specific view
if (isset($_SESSION['role']) && $_SESSION['role'] === 'supplier') {
    $orders = $conn->query("SELECT o.*, u.name AS user_name FROM orders o LEFT JOIN users u ON o.user_id=u.id WHERE o.status='pending' ORDER BY o.created_at DESC");
    ?>
    <h4>Pesanan Pending (untuk supplier)</h4>
    <table id="tbl_supplier" class="display table table-striped" style="width:100%">
      <thead><tr><th>ID</th><th>User</th><th>Item</th><th>Qty</th><th>Total</th><th>Tanggal</th></tr></thead>
      <tbody></tbody>
    </table>
    <script>
      $(document).ready(function(){
        $('#tbl_supplier').DataTable({
          ajax: 'api/orders.php',
          columns: [
            { data: 'id' },
            { data: 'user_name' },
            { data: 'item' },
            { data: 'quantity' },
            { data: 'total_price', render: function(d){ return parseFloat(d).toFixed(2); } },
            { data: 'created_at' }
          ]
        });
      });
    </script>
    <?php
} else {
    // hitung ringkasan untuk admin/hrd/karyawan
    $res1 = $conn->query('SELECT COUNT(*) as c FROM orders');
    $ordersCount = $res1->fetch_assoc()['c'] ?? 0;
    $res2 = $conn->query('SELECT COUNT(*) as c FROM suppliers');
    $suppliersCount = $res2->fetch_assoc()['c'] ?? 0;
    $res3 = $conn->query("SELECT COUNT(*) as c FROM users WHERE role!='supplier'");
    $usersCount = $res3->fetch_assoc()['c'] ?? 0;
    ?>
    <div class="row">
      <div class="col-md-4 mb-3">
        <div class="card text-white bg-primary">
          <div class="card-body">
            <h5 class="card-title">Total Pesanan</h5>
            <p class="card-text display-6"><?= $ordersCount ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card text-white bg-success">
          <div class="card-body">
            <h5 class="card-title">Supplier</h5>
            <p class="card-text display-6"><?= $suppliersCount ?></p>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-3">
        <div class="card text-white bg-secondary">
          <div class="card-body">
            <h5 class="card-title">Pengguna (Non-supplier)</h5>
            <p class="card-text display-6"><?= $usersCount ?></p>
          </div>
        </div>
      </div>
    </div>
    <?php
}
?>

<?php require 'includes/footer.php'; ?>
