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
    <tr><th>ID</th><th>User</th><th>Item</th><th>Qty</th><th>Total</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr>
  </thead>
  <tbody></tbody>
</table>

<!-- Modal add -->
<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" class="modal-content" id="orderAddForm">
      <?= csrf_input() ?>
      <input type="hidden" name="action" value="add">
      <div class="modal-header"><h5 class="modal-title">Tambah Pesanan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label>Pesan untuk tanggal</label><input name="order_date" type="date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
        <div class="mb-3"><label>Pilih dari Menu (opsional)</label><select name="menu_id" class="form-select"><option value="">-- Pilih Menu --</option></select></div>
        <div class="mb-3"><label>Item (jika bukan dari menu)</label><input name="item" class="form-control"></div>
        <div class="mb-3"><label>Quantity</label><input name="quantity" type="number" value="1" class="form-control" required></div>
        <div class="mb-3"><label>Total Price</label><input name="total_price" type="number" step="0.01" value="0" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary" id="orderSaveBtn">Simpan</button></div>
    </form>
  </div>
</div>

<script>
  $(document).ready(function(){
    var role = '<?= $_SESSION['role'] ?? '' ?>';
    var me = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
    // prefer DOM token if present, otherwise use server-rendered token as fallback
    var csrfToken = $('input[name="_csrf"]').first().val() || '<?= csrf_token() ?>';
    var csrfTokenServer = '<?= csrf_token() ?>';
    var table = $('#tbl').DataTable({
      ajax: {
        url: 'api/orders.php',
        dataSrc: function(json){
          if (!json) { showToastError('Server error','Invalid response'); return []; }
          if (json.error) { showToastError('Error', json.error||json.msg||'API error'); return []; }
          return Array.isArray(json) ? json : (json.data || json);
        },
        error: function(xhr, status, err){
          var msg = status;
          try { var j = JSON.parse(xhr.responseText || '{}'); if (j.error || j.msg) msg = (j.error || j.msg); } catch(e) {}
          console.error('orders API error', status, xhr.status, xhr.responseText);
          showToastError('Server error',msg);
        }
      },
      columns: [
        { data: 'id' },
        { data: 'user_name' },
        { data: 'item' },
        { data: 'quantity' },
        { data: 'total_price', render: function(d){ return parseFloat(d).toFixed(2); } },
        { data: 'status_label', render: function(d, type, row){ return row.status_label || row.status || d; } },
        { data: 'created_at' },
        { data: null, orderable:false, searchable:false, render:function(row){
            var html = '';
            var statusLabel = (row.status_label || '').toLowerCase();
            var legacyStatus = (row.status || '').toLowerCase();
            var claimedBy = parseInt(row.claimed_by || 0);
            var isClaimed = claimedBy > 0;
            var isClaimedByOther = isClaimed && claimedBy !== parseInt(me);
            var isAccepted = (statusLabel === 'confirmed' || legacyStatus === 'accepted');

            // Display override: show 'Approved' to admin/karyawan when claimed or accepted
            var displayStatus = row.status_label || row.status || '';
            if ((isClaimed || isAccepted) && (role === 'admin' || role === 'hrd' || role === '' || role === 'karyawan')) {
              displayStatus = 'Approved';
            }

            // disable for non-claimant suppliers only when the order is claimed by someone else
            var disabledForNonClaimant = (role === 'supplier') && isClaimedByOther;

            // Claim: show to suppliers when order is open/pending and not already claimed and not accepted
            if ((legacyStatus === 'pending' || statusLabel === 'open') && role === 'supplier' && !isClaimed && !isAccepted) {
              var d = disabledForNonClaimant ? ' disabled' : '';
              html += '<button class="btn btn-sm btn-success claim-btn"'+d+' data-id="'+row.id+'">Claim</button> ';
            }
            // Accept: show for suppliers when claimed; enabled only for claimant and not after accepted
            if ((legacyStatus === 'claimed' || isClaimed) && role === 'supplier') {
              var acceptDisabled = (claimedBy === parseInt(me) && !isAccepted) ? '' : ' disabled';
              html += '<button class="btn btn-sm btn-primary accept-btn"' + acceptDisabled + ' data-id="'+row.id+'">Accept</button> ';
            }
            // decide single Cancel button visibility to avoid duplicates
            // base permissions (ignore accepted state for visibility) - we'll disable when accepted
            var canAdminCancelBase = (role === 'admin') && (legacyStatus === 'pending' || statusLabel === 'open' || isClaimed);
            var canOwnerCancelBase = (parseInt(row.user_id) === parseInt(me)) && (legacyStatus === 'pending' || statusLabel === 'open' || isClaimed);
            var canSupplierCancelBase = (role === 'supplier') && (claimedBy === parseInt(me));
            var showCancel = canAdminCancelBase || canOwnerCancelBase || canSupplierCancelBase;
            if (showCancel) {
              // when supplier has accepted the order, keep the Cancel button visible
              // but disable it for everyone after accept; otherwise keep previous non-claimant supplier disable
              var cancelDisabled = '';
              if (isAccepted) {
                // allow the claimant supplier to still cancel after accept; others disabled
                if (role === 'supplier' && claimedBy === parseInt(me)) {
                  cancelDisabled = '';
                } else {
                  cancelDisabled = ' disabled';
                }
              } else {
                if (role === 'supplier' && isClaimedByOther) cancelDisabled = ' disabled';
              }
              html += '<button class="btn btn-sm btn-danger cancel-btn"'+cancelDisabled+' data-id="'+row.id+'">Cancel</button> ';
            }
            
            // Reopen: show when cancelled by admin or cancelled by user, before supplier approval and within H+1
            var cancelledByAdmin = (legacyStatus === 'cancelled_by_admin' || (row.status_label || '').toLowerCase() === 'cancelled_by_admin');
            var cancelledByUser = (legacyStatus === 'cancelled_by_user' || (row.status_label || '').toLowerCase() === 'cancelled_by_user');
            var cancelEligible = cancelledByAdmin || cancelledByUser;
            // determine order date (prefer order_date if provided)
            var od = row.order_date || row.created_at;
            var reopenWithin = false;
            if (od) {
              try {
                var odt = new Date(od);
                var cut = new Date(odt.getTime()); cut.setDate(cut.getDate()+1);
                var now = new Date();
                reopenWithin = now <= cut;
              } catch(e) { reopenWithin = false; }
            }
            // show reopen to supplier (if claimant/supplier), admin, or owner when eligible
            var orderOwner = parseInt(row.user_id || 0);
            var isSupplierAssigned = parseInt(row.supplier_id || 0) === parseInt(me);
            var canReopen = cancelEligible && reopenWithin && !isAccepted && ( (role === 'supplier' && (claimedBy === parseInt(me) || isSupplierAssigned)) || role === 'admin' || (orderOwner === parseInt(me)) );
            if (canReopen) {
              html += '<button class="btn btn-sm btn-warning reopen-btn" data-id="'+row.id+'">Reopen</button> '; 
            }
            // replace status display
            if (displayStatus) row.status_label = displayStatus;
            return html;
        }}
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

    // load menu items into select
    function loadMenuOptions(){
      $.get('api/menu.php').done(function(list){
        var opts = '<option value="">-- Pilih Menu --</option>';
        list.forEach(function(m){ opts += '<option data-price="'+m.price+'" value="'+m.id+'">'+m.name+' - Rp '+parseFloat(m.price).toFixed(2)+'</option>'; });
        $('select[name=menu_id]').html(opts);
      });
    }
    loadMenuOptions();

    // when menu selected, populate item and price
    $(document).on('change','select[name=menu_id]', function(){
      var val = $(this).val();
      if (val) {
        var sel = $(this).find('option:selected');
        var price = sel.data('price') || 0;
        $('input[name=item]').val(sel.text().split(' - Rp ')[0]);
        $('input[name=total_price]').val(parseFloat(price).toFixed(2));
      } else {
        $('input[name=item]').val('');
        $('input[name=total_price]').val('0.00');
      }
    });

    // claim button -> open modal
    $(document).on('click', '.claim-btn', function(){
      var id = $(this).data('id');
      // fetch suppliers for select
      $.get('api/suppliers.php').done(function(list){
        var opts = list.map(function(s){ return '<option value="'+s.id+'">'+s.name+'</option>'; }).join('');
        var modal = `
          <div class="modal fade" id="claimModal" tabindex="-1">
            <div class="modal-dialog">
              <form class="modal-content" id="claimForm">
                <?= csrf_input() ?>
                <input type="hidden" name="order_id" value="`+id+`">
                <div class="modal-header"><h5 class="modal-title">Claim Pesanan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                  <div class="mb-3"><label>Pilih Supplier</label><select name="supplier_id" class="form-control">`+opts+`</select></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-success">Claim</button></div>
              </form>
            </div>
          </div>`;
        $('body').append(modal);
        var m = new bootstrap.Modal(document.getElementById('claimModal'));
        m.show();
      });
    });

    // submit claim
    $(document).on('submit', '#claimForm', function(e){
      e.preventDefault();
      var fd = $(this).serialize();
      $.post('api/claim_order.php', fd).done(function(res){
        if (res.ok) {
          $('#claimModal').modal('hide');
          $('#claimModal').remove();
          Swal.fire({icon:'success',title:'Berhasil',text:'Pesanan diklaim'});
          table.ajax.reload(null,false);
        } else Swal.fire({icon:'error',title:'Gagal',text:res.error || 'Error'});
      }).fail(function(){ Swal.fire({icon:'error',title:'Gagal',text:'Server error'}); });
    });

    // accept
    $(document).off('click', '.accept-btn').on('click', '.accept-btn', function(){
      var id = $(this).data('id');
      Swal.fire({title:'Terima pesanan?',icon:'question',showCancelButton:true}).then(function(x){
        if (x.isConfirmed) {
          $.post('api/accept_order.php',{_csrf:csrfToken,order_id:id}).done(function(res){
            if (res.ok) { Swal.fire({icon:'success',title:'Diterima'}); table.ajax.reload(null,false); }
            else Swal.fire({icon:'error',title:'Gagal',text:res.error||'Error'});
          }).fail(function(){ Swal.fire({icon:'error',title:'Gagal',text:'Server error'}); });
        }
      });
    });

    // cancel (admin + supplier + user) - bind immediately
    $(document).off('click', '.cancel-btn').on('click', '.cancel-btn', function(){
      var id = $(this).data('id');
      Swal.fire({title:'Batalkan pesanan ini?',text:'Hanya admin dapat membatalkan sebelum supplier approve.',icon:'warning',showCancelButton:true}).then(function(x){
        if (x.isConfirmed) {
          // read token fresh in case session/token rotated; fall back to server token
          var tokenNow = $('input[name="_csrf"]').first().val() || csrfTokenServer;
          console.log('cancel: id=', id, 'csrfLen=', (tokenNow||'').length, 'role=', role, 'me=', me);
          Swal.fire({title:'Mengirim permintaan...',allowOutsideClick:false,didOpen: function(){ Swal.showLoading(); }});
          $.post('api/cancel_order.php',{_csrf:tokenNow,order_id:id}).done(function(res){
            Swal.close();
            if (res.ok) { Swal.fire({icon:'success',title:'Dibatalkan'}); table.ajax.reload(null,false); }
            else Swal.fire({icon:'error',title:'Gagal',text:res.error||res.msg||'Error'});
          }).fail(function(xhr){
            Swal.close();
            console.error('cancel failed', xhr && xhr.status, xhr && xhr.responseText);
            var msg = 'Server error';
            try {
              var body = xhr && (xhr.responseJSON || JSON.parse(xhr.responseText || '{}')) || {};
              var code = body.error || body.msg || '';
              if (code === 'already_accepted') msg = 'Tidak bisa membatalkan: pesanan sudah diterima supplier.';
              else if (code === 'forbidden') msg = 'Anda tidak berhak membatalkan pesanan ini.';
              else if (code === 'notfound') msg = 'Pesanan tidak ditemukan.';
              else if (code === 'unauth') msg = 'Sesi berakhir, silakan login lagi.';
              else if (code === 'csrf') msg = 'Token CSRF tidak valid atau sesi telah kadaluarsa.';
              else if (code) msg = code;
            } catch (e) {
              console.error('cancel parse error', e, xhr && xhr.responseText);
            }
            Swal.fire({icon:'error',title:'Gagal',text:msg});
          });
        }
      });
    });

    // reopen (supplier) - bind immediately
    $(document).off('click', '.reopen-btn').on('click', '.reopen-btn', function(){
      var id = $(this).data('id');
      Swal.fire({title:'Buka kembali approval?',icon:'question',showCancelButton:true}).then(function(x){
        if (x.isConfirmed) {
          $.post('api/reopen_order.php',{_csrf:csrfToken,order_id:id}).done(function(res){
            if (res.ok) { Swal.fire({icon:'success',title:'Dibuka kembali'}); table.ajax.reload(null,false); }
            else Swal.fire({icon:'error',title:'Gagal',text:res.error||'Error'});
          }).fail(function(){ Swal.fire({icon:'error',title:'Gagal',text:'Server error'}); });
        }
      });
    });
  });
</script>

<?php require 'includes/footer.php'; ?>
