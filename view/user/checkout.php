<?php $pageTitle = 'Thanh toán'; ?>

<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?case=home" class="text-decoration-none">Trang chủ</a></li>
      <li class="breadcrumb-item"><a href="?case=cart" class="text-decoration-none">Giỏ hàng</a></li>
      <li class="breadcrumb-item active">Thanh toán</li>
    </ol>
  </nav>

  <?php if (!empty($_GET['error'])): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_GET['error']) ?></div>
  <?php endif; ?>

  <form method="POST" action="?case=place_order" id="checkout-form">
    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
    <input type="hidden" name="voucher_code" id="co-voucher-code" value="">

    <div class="row g-4">

      <!-- Thông tin giao hàng -->
      <div class="col-lg-7">
        <div class="form-card mb-4">
          <h5 class="fw-bold mb-4"><i class="bi bi-geo-alt me-2 text-primary"></i>Thông tin giao hàng</h5>

          <div class="mb-3">
            <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" disabled>
            <div class="form-text">Tên sẽ được dùng cho đơn hàng. <a href="?case=profile">Chỉnh sửa</a></div>
          </div>

          <div class="mb-3">
            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
            <input type="tel" name="phone" class="form-control"
                   value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                   placeholder="0901234567" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Địa chỉ giao hàng <span class="text-danger">*</span></label>
            <textarea name="shipping_address" class="form-control" rows="2"
                      placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Ghi chú đơn hàng</label>
            <textarea name="note" class="form-control" rows="2"
                      placeholder="Ghi chú cho người giao hàng (không bắt buộc)"></textarea>
          </div>
        </div>

        <!-- Phương thức thanh toán -->
        <div class="form-card">
          <h5 class="fw-bold mb-4"><i class="bi bi-credit-card me-2 text-primary"></i>Phương thức thanh toán</h5>

          <?php
            $methods = [
              'cod'           => ['bi-cash-stack',     'Thanh toán khi nhận hàng (COD)', 'Trả tiền mặt khi nhận hàng, không mất phí'],
              'bank_transfer' => ['bi-bank',            'Chuyển khoản ngân hàng',         'STK: 1234 5678 — UPNEX — Vietcombank'],
              'momo'          => ['bi-phone-fill',      'Ví MoMo',                         'Quét QR hoặc chuyển đến SĐT 0901234567'],
              'vnpay'         => ['bi-credit-card-fill','VNPay',                           'Thanh toán qua cổng VNPay an toàn'],
            ];
          ?>
          <div class="d-flex flex-column gap-2" id="payment-methods">
            <?php foreach ($methods as $val => [$icon, $label, $desc]): ?>
              <label class="payment-method-option d-flex align-items-center gap-3 p-3 border rounded-3 cursor-pointer
                            <?= $val === 'cod' ? 'border-primary bg-primary-soft' : '' ?>"
                     style="cursor:pointer" id="pm-label-<?= $val ?>">
                <input type="radio" name="payment_method" value="<?= $val ?>"
                       class="form-check-input mt-0 flex-shrink-0"
                       <?= $val === 'cod' ? 'checked' : '' ?>
                       onchange="selectPayment('<?= $val ?>')">
                <i class="bi <?= $icon ?> fs-4 text-primary flex-shrink-0"></i>
                <div>
                  <div class="fw-semibold small"><?= $label ?></div>
                  <div class="text-muted" style="font-size:.78rem"><?= $desc ?></div>
                </div>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Tóm tắt đơn hàng -->
      <div class="col-lg-5">
        <div class="order-summary-card">
          <h5 class="fw-bold mb-4"><i class="bi bi-bag-check me-2 text-primary"></i>Đơn hàng của bạn</h5>

          <!-- Danh sách sản phẩm -->
          <div class="mb-3" style="max-height:280px;overflow-y:auto">
            <?php foreach ($items as $item): ?>
              <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                <img src="<?= $item['image'] ? '/upnex/uploads/products/'.htmlspecialchars($item['image']) : '/upnex/public/images/placeholder.png' ?>"
                     alt="" style="width:48px;height:48px;object-fit:contain;background:#f8f9fa;border-radius:6px;padding:3px">
                <div class="flex-grow-1 min-w-0">
                  <div class="small fw-semibold text-truncate"><?= htmlspecialchars($item['name']) ?></div>
                  <div class="text-muted" style="font-size:.78rem">x<?= $item['quantity'] ?></div>
                </div>
                <div class="text-danger small fw-bold text-nowrap">
                  <?= number_format($item['unit_price'] * $item['quantity']) ?>đ
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Voucher -->
          <div class="mb-3">
            <label class="form-label small fw-semibold">Mã giảm giá</label>
            <div class="input-group input-group-sm">
              <input type="text" id="co-voucher-input" class="form-control text-uppercase"
                     placeholder="UPNEX10...">
              <button type="button" class="btn btn-outline-primary" onclick="applyCoVoucher()">Áp dụng</button>
            </div>
            <div id="co-voucher-msg" class="small mt-1"></div>
          </div>

          <hr>

          <!-- Tổng -->
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Tạm tính</span>
            <span><?= number_format($subtotal) ?>đ</span>
          </div>
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Giảm giá</span>
            <span id="co-discount-display" class="text-success">-0đ</span>
          </div>
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Phí vận chuyển</span>
            <span>30.000đ</span>
          </div>
          <hr>
          <div class="d-flex justify-content-between fw-bold mb-4">
            <span>Tổng cộng</span>
            <span class="text-danger fs-5" id="co-total"><?= number_format($subtotal + 30000) ?>đ</span>
          </div>

          <button type="submit" class="btn btn-danger w-100 py-2 rounded-pill fw-bold fs-6"
                  onclick="return confirmOrder()">
            <i class="bi bi-bag-check me-2"></i>Đặt hàng ngay
          </button>
          <p class="text-muted text-center mt-2 small">
            <i class="bi bi-shield-lock me-1"></i>Thông tin được bảo mật tuyệt đối
          </p>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
const SUBTOTAL = <?= $subtotal ?>;
const SHIPPING = 30000;

function selectPayment(val) {
  document.querySelectorAll('.payment-method-option').forEach(el => {
    el.classList.remove('border-primary', 'bg-primary-soft');
  });
  document.getElementById('pm-label-' + val)?.classList.add('border-primary', 'bg-primary-soft');
}

async function applyCoVoucher() {
  const code = document.getElementById('co-voucher-input').value.trim();
  const msgEl = document.getElementById('co-voucher-msg');
  if (!code) return;

  const fd = new FormData();
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
  fd.append('code', code);
  fd.append('subtotal', SUBTOTAL);
  const res  = await fetch('?case=apply_voucher', { method:'POST', body: fd });
  const data = await res.json();

  msgEl.textContent = data.message;
  msgEl.className   = 'small mt-1 ' + (data.success ? 'text-success' : 'text-danger');

  if (data.success) {
    document.getElementById('co-voucher-code').value  = code;
    document.getElementById('co-discount-display').textContent = '-' + parseInt(data.discount).toLocaleString('vi-VN') + 'đ';
    const total = SUBTOTAL - data.discount + SHIPPING;
    document.getElementById('co-total').textContent = parseInt(total).toLocaleString('vi-VN') + 'đ';
  }
}

function confirmOrder() {
  const addr = document.querySelector('[name="shipping_address"]').value.trim();
  if (!addr) { UPNEX.showToast('Vui lòng nhập địa chỉ giao hàng.', 'warning'); return false; }
  return confirm('Xác nhận đặt hàng?');
}
</script>
