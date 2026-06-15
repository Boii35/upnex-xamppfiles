<?php $pageTitle = 'Chi tiết đơn hàng #' . htmlspecialchars($order['order_code']); ?>

<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?case=home" class="text-decoration-none">Trang chủ</a></li>
      <li class="breadcrumb-item"><a href="?case=order_history" class="text-decoration-none">Đơn hàng</a></li>
      <li class="breadcrumb-item active"><?= htmlspecialchars($order['order_code']) ?></li>
    </ol>
  </nav>

  <?php if (!empty($_GET['pay_error'])): ?>
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-x-circle-fill fs-5"></i>
      <div><strong>Thanh toán thất bại!</strong> <?= htmlspecialchars($_GET['pay_error']) ?></div>
    </div>
  <?php endif; ?>

  <?php if (!empty($_GET['paid'])): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <div>
        <strong>Thanh toán MoMo thành công!</strong> Đơn hàng đã được xác nhận và đang được xử lý.
        Email xác nhận đã được gửi đến hộp thư của bạn.
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
      <i class="bi bi-check-circle-fill fs-5"></i>
      <div>
        <strong>Đặt hàng thành công!</strong> Mã đơn hàng: <strong><?= htmlspecialchars($order['order_code']) ?></strong>.
        Chúng tôi sẽ liên hệ xác nhận trong vòng 24 giờ.
      </div>
    </div>
  <?php endif; ?>

  <?php
    $statusLabels = [
      'pending'   => 'Chờ xác nhận',
      'confirmed' => 'Đã xác nhận',
      'shipping'  => 'Đang giao',
      'delivered' => 'Đã giao',
      'completed' => 'Hoàn thành',
      'cancelled' => 'Đã hủy',
    ];
    $statusFlow = ['pending','confirmed','shipping','delivered','completed'];
    $currentIdx = array_search($order['status'], $statusFlow);
  ?>

  <div class="row g-4">

    <!-- Trái: thông tin đơn hàng -->
    <div class="col-lg-8">

      <!-- Trạng thái tiến trình -->
      <?php if ($order['status'] !== 'cancelled'): ?>
        <div class="bg-white rounded-card shadow-card p-4 mb-4">
          <h6 class="fw-bold mb-4">Tiến trình đơn hàng</h6>
          <div class="d-flex justify-content-between position-relative">
            <div class="progress position-absolute" style="top:20px;left:10%;right:10%;height:3px;z-index:0">
              <div class="progress-bar bg-primary"
                   style="width:<?= $currentIdx >= 0 ? min(100, ($currentIdx / (count($statusFlow)-1)) * 100) : 0 ?>%">
              </div>
            </div>
            <?php foreach ($statusFlow as $idx => $step): ?>
              <?php $done = $currentIdx !== false && $idx <= $currentIdx; ?>
              <div class="text-center" style="z-index:1;flex:1">
                <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-2
                             <?= $done ? 'bg-primary text-white' : 'bg-light border' ?>"
                     style="width:40px;height:40px">
                  <i class="bi <?= match($step){
                    'pending'=>'bi-clock','confirmed'=>'bi-check','shipping'=>'bi-truck',
                    'delivered'=>'bi-box','completed'=>'bi-trophy',default=>'bi-circle'
                  } ?> small"></i>
                </div>
                <div class="small fw-semibold <?= $done ? 'text-primary' : 'text-muted' ?>" style="font-size:.7rem">
                  <?= $statusLabels[$step] ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
          <i class="bi bi-x-circle-fill fs-5"></i>
          <strong>Đơn hàng đã bị hủy</strong>
        </div>
      <?php endif; ?>

      <!-- Sản phẩm -->
      <div class="bg-white rounded-card shadow-card p-4 mb-4">
        <h6 class="fw-bold mb-3">Sản phẩm đã đặt</h6>
        <?php foreach ($order['items'] as $item): ?>
          <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
            <img src="<?= $item['image'] ? '/upnex/uploads/products/'.htmlspecialchars($item['image']) : '/upnex/public/images/placeholder.png' ?>"
                 alt="" style="width:60px;height:60px;object-fit:contain;background:#f8f9fa;border-radius:8px;padding:4px">
            <div class="flex-grow-1">
              <div class="fw-semibold small"><?= htmlspecialchars($item['product_name']) ?></div>
              <div class="text-muted small">
                <?= number_format($item['unit_price']) ?>đ × <?= $item['quantity'] ?>
              </div>
            </div>
            <div class="fw-bold text-danger small"><?= number_format($item['subtotal']) ?>đ</div>
          </div>
        <?php endforeach; ?>

        <!-- Tổng kết -->
        <div class="d-flex justify-content-end">
          <table class="small" style="min-width:220px">
            <tr>
              <td class="text-muted pe-4 py-1">Tạm tính:</td>
              <td class="text-end fw-semibold"><?= number_format($order['subtotal']) ?>đ</td>
            </tr>
            <?php if ($order['discount_amount'] > 0): ?>
              <tr>
                <td class="text-muted pe-4 py-1">Giảm giá:</td>
                <td class="text-end text-success fw-semibold">-<?= number_format($order['discount_amount']) ?>đ</td>
              </tr>
            <?php endif; ?>
            <tr>
              <td class="text-muted pe-4 py-1">Phí ship:</td>
              <td class="text-end fw-semibold"><?= number_format($order['shipping_fee']) ?>đ</td>
            </tr>
            <tr class="border-top">
              <td class="pe-4 py-2 fw-bold">Tổng cộng:</td>
              <td class="text-end fw-bold text-danger fs-6"><?= number_format($order['total']) ?>đ</td>
            </tr>
          </table>
        </div>
      </div>

      <!-- Lịch sử trạng thái -->
      <?php if (!empty($order['status_log'])): ?>
        <div class="bg-white rounded-card shadow-card p-4">
          <h6 class="fw-bold mb-3">Lịch sử cập nhật</h6>
          <div class="order-timeline">
            <?php foreach (array_reverse($order['status_log']) as $log): ?>
              <div class="timeline-step">
                <div class="timeline-dot done"></div>
                <div class="timeline-label"><?= $statusLabels[$log['status']] ?? $log['status'] ?></div>
                <?php if ($log['note']): ?>
                  <div class="text-muted small"><?= htmlspecialchars($log['note']) ?></div>
                <?php endif; ?>
                <div class="timeline-time"><?= date('d/m/Y H:i', strtotime($log['changed_at'])) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Phải: thông tin giao hàng -->
    <div class="col-lg-4">
      <div class="bg-white rounded-card shadow-card p-4 mb-3">
        <h6 class="fw-bold mb-3">Thông tin đơn hàng</h6>
        <table class="small w-100">
          <tr><td class="text-muted py-1">Mã đơn:</td><td class="fw-semibold text-end"><?= htmlspecialchars($order['order_code']) ?></td></tr>
          <tr><td class="text-muted py-1">Ngày đặt:</td><td class="fw-semibold text-end"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td></tr>
          <tr><td class="text-muted py-1">Thanh toán:</td><td class="fw-semibold text-end">
            <?= match($order['payment_method']){
              'cod'=>'COD', 'bank_transfer'=>'Chuyển khoản', 'momo'=>'MoMo', 'vnpay'=>'VNPay', default=>$order['payment_method']
            } ?>
          </td></tr>
          <tr><td class="text-muted py-1">Trạng thái TT:</td>
            <td class="text-end">
              <span class="badge <?= $order['payment_status']==='paid' ? 'bg-success' : 'bg-warning text-dark' ?>">
                <?= $order['payment_status']==='paid' ? 'Đã thanh toán' : 'Chờ thanh toán' ?>
              </span>
            </td>
          </tr>
        </table>
      </div>

      <div class="bg-white rounded-card shadow-card p-4 mb-3">
        <h6 class="fw-bold mb-3">Địa chỉ giao hàng</h6>
        <p class="small mb-1 fw-semibold"><?= htmlspecialchars($order['user_name']) ?></p>
        <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
        <?php if ($order['note']): ?>
          <p class="small text-muted mt-2"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($order['note']) ?></p>
        <?php endif; ?>
      </div>

      <div class="d-grid gap-2">
        <?php if ($order['status'] === 'pending'): ?>
          <button class="btn btn-outline-danger rounded-pill"
                  onclick="UPNEX.cancelOrder(<?= $order['id'] ?>)">
            <i class="bi bi-x-circle me-1"></i>Hủy đơn hàng
          </button>
        <?php endif; ?>
        <a href="?case=order_history" class="btn btn-outline-secondary rounded-pill">
          <i class="bi bi-arrow-left me-1"></i>Về danh sách đơn
        </a>
        <a href="?case=products" class="btn btn-primary rounded-pill">
          <i class="bi bi-bag me-1"></i>Tiếp tục mua sắm
        </a>
      </div>
    </div>
  </div>
</div>
