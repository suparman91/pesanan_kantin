<?php require 'includes/header.php'; ?>
<?php require 'includes/sidebar.php'; ?>

<?php
// show supplier-specific view
if (isset($_SESSION['role']) && $_SESSION['role'] === 'supplier') {
    // supplier dashboard: show counts and selectable view
    $me = (int)$_SESSION['user_id'];
    // safe query helper
    function safe_count($conn, $sql) {
      if (!$conn) return 0;
      $res = $conn->query($sql);
      if (!$res) return 0;
      $row = $res->fetch_assoc();
      return isset($row['c']) ? (int)$row['c'] : 0;
    }
    $openCode = defined('ORDER_STATUS_OPEN') ? ORDER_STATUS_OPEN : 0;
    $confirmedCode = defined('ORDER_STATUS_CONFIRMED') ? ORDER_STATUS_CONFIRMED : 1;
    $pending = safe_count($conn, "SELECT COUNT(*) as c FROM orders WHERE status_code={$openCode}");
    $claimed = safe_count($conn, "SELECT COUNT(*) as c FROM orders WHERE claimed_by={$me}");
    $accepted = safe_count($conn, "SELECT COUNT(*) as c FROM orders WHERE status_code={$confirmedCode} AND claimed_by={$me}");
    ?>
    <div class="row mb-3">
      <div class="col-md-4"><div class="card"><div class="card-body"><h6>Pending</h6><div class="display-6"><?= $pending ?></div></div></div></div>
      <div class="col-md-4"><div class="card"><div class="card-body"><h6>Claimed oleh saya</h6><div class="display-6"><?= $claimed ?></div></div></div></div>
      <div class="col-md-4"><div class="card"><div class="card-body"><h6>Diterima oleh saya</h6><div class="display-6"><?= $accepted ?></div></div></div></div>
    </div>

    <div class="mb-3">
      <label>Pilih tampilan:</label>
      <select id="supplierView" class="form-select" style="width:220px;display:inline-block;">
        <option value="pending">Pending</option>
        <option value="claimed">Claimed (oleh saya)</option>
        <option value="accepted">Accepted (oleh saya)</option>
      </select>
    </div>

    <table id="tbl_supplier" class="display table table-striped" style="width:100%">
      <thead><tr><th>ID</th><th>User</th><th>Item</th><th>Qty</th><th>Total</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
      <tbody></tbody>
    </table>
    <script>
      $(document).ready(function(){
        function loadTable(filter){
          if ($.fn.DataTable.isDataTable('#tbl_supplier')) { $('#tbl_supplier').DataTable().destroy(); $('#tbl_supplier tbody').empty(); }
          $('#tbl_supplier').DataTable({
            ajax: 'api/orders.php?status='+encodeURIComponent(filter),
            columns: [
              { data: 'id' },
              { data: 'user_name' },
              { data: 'item' },
              { data: 'quantity' },
              { data: 'total_price', render: function(d){ return parseFloat(d).toFixed(2); } },
              { data: 'status_label' },
              { data: 'created_at' },
              { data: null, orderable:false, searchable:false, render:function(row){
                  var html = '';
                  var s = (row.status_label || row.status || '').toLowerCase();
                  if (s === 'open' || s === 'pending') html += '<button class="btn btn-sm btn-success claim-btn" data-id="'+row.id+'">Claim</button>';
                  if ((s === 'claimed' || parseInt(row.claimed_by||0) === <?= $me ?>) && parseInt(row.claimed_by||0) == <?= $me ?>) html += '<button class="btn btn-sm btn-primary accept-btn" data-id="'+row.id+'">Accept</button>';
                  return html;
              }}
            ]
          });
        }
        loadTable('pending');
        $('#supplierView').on('change', function(){ loadTable(this.value); });
      });
    </script>
    <?php
} else {
    // hitung ringkasan untuk admin/hrd/karyawan
    function safe_count_global($conn, $sql) { if (!$conn) return 0; $r = $conn->query($sql); if (!$r) return 0; $row=$r->fetch_assoc(); return isset($row['c'])?(int)$row['c']:0; }
    $ordersCount = safe_count_global($conn, 'SELECT COUNT(*) as c FROM orders');
    $suppliersCount = safe_count_global($conn, 'SELECT COUNT(*) as c FROM suppliers');
    $usersCount = safe_count_global($conn, "SELECT COUNT(*) as c FROM users WHERE role!='supplier'");
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
