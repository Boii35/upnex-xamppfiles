<?php $pageTitle = 'Lịch sử đơn hàng'; ?>

<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?case=home" class="text-decoration-none">Trang chủ</a></li>
      <li class="breadcrumb-item active">Đơn hàng của tôi</li>
    </ol>
  </nav>

  <h2 class="fw-bold mb-4"><i class="bi bi-receipt me-2 text-primary"></i>Đơn hàng của tôi</h2>

  <?php
    $statusLabels = [
      'pending'   => ['Chờ xác nhận',  'status-pending'],
      'confirmed' => ['Đã xác nhận',   'status-confirmed'],
      'shipping'  => ['Đang giao',      'status-shipping'],
      'delivered' => ['Đã giao',        'status-delivered'],
      'completed' => ['Hoàn thành',     'status-completed'],
      'cancelled' => ['Đã hủy',         'status-cancelled'],
    ];
  ?>

  <?php if (empty($orders)): ?>
    <div class="text-center py-5 bg-white rounded-card shadow-card">
      <i class="bi bi-bag-x text-muted" style="font-size:3.5rem"></i>
      <h5 class="mt-3 fw-bold">Chưa có đơn hàng nào</h5>
      <p class="text-muted">Mua sắm ngay để có đơn hàng đầu tiên!</p>
      <a href="?case=products" class="btn btn-primary rounded-pill px-4 mt-2">Mua ngay</a>
    </div>
  <?php else: ?>
    <!-- Filter tabs -->
    <ul class="nav nav-pills mb-4 gap-1 flex-wrap" id="order-tabs">
      <li class="nav-item">
        <button class="nav-link active" onclick="filterOrders('all', this)">Tất cả (<?= count($orders) ?>)</button>
      </li>
      <?php foreach ($statusLabels as $key => [$label, $cls]): ?>
        <?php $cnt = count(array_filter($orders, fn($o) => $o['status'] === $key)); ?>
        <?php if ($cnt > 0): ?>
          <li class="nav-item">
            <button class="nav-link" onclick="filterOrders('<?= $key ?>', this)"><?= $label ?> (<?= $cnt ?>)</button>
          </li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>

    <div id="order-list">
      <?php foreach ($orders as $order): ?>
        <?php [$statusLabel, $statusClass] = $statusLabels[$order['status']] ?? ['Không rõ','status-pending']; ?>
        <div class="bg-white rounded-card shadow-card mb-3 order-item" data-status="<?= $order['status'] ?>">
          <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <span class="fw-bold"><?= htmlspecialchars($order['order_code']) ?></span>
              <span class="text-muted small ms-2"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span>
              <?php if ($order['status'] === 'pending'): ?>
                <button class="btn btn-outline-danger btn-sm rounded-pill"
                        onclick="UPNEX.cancelOrder(<?= $order['id'] ?>)">
                  <i class="bi bi-x-circle me-1"></i>Hủy đơn
                </button>
              <?php endif; ?>
              <a href="?case=order_detail&id=<?= $order['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill">
                <i class="bi bi-eye me-1"></i>Chi tiết
              </a>
            </div>
          </div>
          <div class="p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">
              <i class="bi bi-credit-card me-1"></i>
              <?= match($order['payment_method']) {
                'cod' => 'Thanh toán khi nhận hàng',
                'bank_transfer' => 'Chuyển khoản ngân hàng',
                'momo' => 'Ví MoMo',
                'vnpay' => 'VNPay',
                default => $order['payment_method']
              } ?>
            </div>
            <div class="fw-bold text-danger">
              Tổng: <?= number_format($order['total']) ?>đ
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
function filterOrders(status, btn) {
  document.querySelectorAll('#order-tabs .nav-link').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.order-item').forEach(el => {
    el.style.display = (status === 'all' || el.dataset.status === status) ? '' : 'none';
  });
}
</script>
