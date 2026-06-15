<?php $pageTitle = 'Quản lý đơn hàng'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"><i class="bi bi-receipt me-2 text-primary"></i>Quản lý đơn hàng</h5>
  <span class="badge bg-primary rounded-pill"><?= number_format($total) ?> đơn</span>
</div>

<!-- Filter tabs -->
<?php
  $statusMap = [
    ''          => 'Tất cả',
    'pending'   => 'Chờ xác nhận',
    'confirmed' => 'Đã xác nhận',
    'shipping'  => 'Đang giao',
    'delivered' => 'Đã giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy',
  ];
  $statusStyle = [
    'pending'   => 'status-pending',
    'confirmed' => 'status-confirmed',
    'shipping'  => 'status-shipping',
    'delivered' => 'status-delivered',
    'completed' => 'status-completed',
    'cancelled' => 'status-cancelled',
  ];
?>
<ul class="nav nav-pills mb-3 gap-1 flex-wrap">
  <?php foreach ($statusMap as $val => $label): ?>
    <li class="nav-item">
      <a class="nav-link <?= $status === $val ? 'active' : '' ?>"
         href="?case=admin_orders&status=<?= $val ?>"><?= $label ?></a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="bg-white rounded-card shadow-card">
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>Mã đơn</th>
          <th>Khách hàng</th>
          <th>Tổng tiền</th>
          <th>Thanh toán</th>
          <th>Trạng thái</th>
          <th>Ngày đặt</th>
          <th class="text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Không có đơn hàng nào.</td></tr>
        <?php endif; ?>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td>
              <span class="fw-semibold text-primary small"><?= htmlspecialchars($o['order_code']) ?></span>
            </td>
            <td>
              <div class="small fw-semibold"><?= htmlspecialchars($o['user_name']) ?></div>
            </td>
            <td><span class="fw-bold text-danger small"><?= number_format($o['total']) ?>đ</span></td>
            <td>
              <span class="badge bg-light text-dark border small"><?= strtoupper($o['payment_method']) ?></span>
              <span class="badge <?= $o['payment_status']==='paid'?'bg-success':'bg-warning text-dark' ?> small ms-1">
                <?= $o['payment_status']==='paid'?'Đã TT':'Chờ TT' ?>
              </span>
            </td>
            <td>
              <span class="status-badge <?= $statusStyle[$o['status']] ?? '' ?>">
                <?= $statusMap[$o['status']] ?? $o['status'] ?>
              </span>
            </td>
            <td><span class="small text-muted"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></span></td>
            <td class="text-center">
              <button class="btn btn-sm btn-primary rounded-pill"
                      onclick="openUpdateModal(<?= $o['id'] ?>, '<?= htmlspecialchars($o['order_code']) ?>', '<?= $o['status'] ?>')">
                <i class="bi bi-pencil me-1"></i>Cập nhật
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Phân trang -->
<?php if ($total_pages > 1): ?>
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= $i === $current ? 'active' : '' ?>">
          <a class="page-link" href="?case=admin_orders&status=<?= $status ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<!-- Modal cập nhật trạng thái -->
<div class="modal fade" id="updateModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold">Cập nhật đơn hàng</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-3">Mã đơn: <strong id="modal-order-code"></strong></p>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Trạng thái mới</label>
          <select class="form-select form-select-sm" id="modal-status">
            <option value="confirmed">Xác nhận đơn hàng</option>
            <option value="shipping">Đang giao hàng</option>
            <option value="delivered">Đã giao hàng</option>
            <option value="completed">Hoàn thành</option>
            <option value="cancelled">Hủy đơn</option>
          </select>
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold">Ghi chú (không bắt buộc)</label>
          <textarea class="form-control form-control-sm" id="modal-note" rows="2"
                    placeholder="Lý do cập nhật, ghi chú giao hàng..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Hủy</button>
        <button type="button" class="btn btn-primary btn-sm rounded-pill" onclick="doUpdateOrder()">
          <i class="bi bi-check2 me-1"></i>Cập nhật
        </button>
      </div>
    </div>
  </div>
</div>

<script>
let currentOrderId = null;
let updateModal = null;

function getUpdateModal() {
  if (!updateModal) {
    updateModal = new bootstrap.Modal(document.getElementById('updateModal'));
  }
  return updateModal;
}

function openUpdateModal(id, code, currentStatus) {
  currentOrderId = id;
  document.getElementById('modal-order-code').textContent = code;
  document.getElementById('modal-status').value = currentStatus;
  document.getElementById('modal-note').value   = '';
  getUpdateModal().show();
}

async function doUpdateOrder() {
  if (!currentOrderId) {
    adminShowToast('Lỗi: Không xác định được đơn hàng.', 'danger');
    return;
  }
  
  const status = document.getElementById('modal-status').value;
  const note   = document.getElementById('modal-note').value;
  
  // Gửi request
  const fd = new FormData();
  fd.append('csrf_token', getCsrfToken());
  fd.append('order_id', currentOrderId);
  fd.append('status', status);
  fd.append('note', note);
  
  try {
    const res = await fetch('?case=admin_order_update', { method: 'POST', body: fd });
    const data = await res.json();
    
    if (data.success) {
      adminShowToast('Cập nhật đơn hàng thành công!', 'success');
      getUpdateModal().hide();
      setTimeout(() => window.location.reload(), 1000);
    } else {
      adminShowToast(data.message || 'Lỗi cập nhật đơn hàng.', 'danger');
    }
  } catch (err) {
    adminShowToast('Lỗi kết nối: ' + err.message, 'danger');
  }
}
</script>
