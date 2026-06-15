<?php $pageTitle = 'Giỏ hàng'; ?>

<div class="container py-4">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?case=home" class="text-decoration-none">Trang chủ</a></li>
      <li class="breadcrumb-item active">Giỏ hàng</li>
    </ol>
  </nav>

  <h2 class="fw-bold mb-4"><i class="bi bi-cart3 me-2 text-primary"></i>Giỏ hàng của bạn</h2>

  <?php if (empty($items)): ?>
    <!-- Giỏ trống -->
    <div class="text-center py-5 bg-white rounded-card shadow-card">
      <i class="bi bi-cart-x text-muted" style="font-size:4rem"></i>
      <h5 class="mt-3 fw-bold">Giỏ hàng trống</h5>
      <p class="text-muted">Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
      <a href="?case=products" class="btn btn-primary rounded-pill px-4 mt-2">
        <i class="bi bi-grid me-2"></i>Tiếp tục mua sắm
      </a>
    </div>

  <?php else: ?>
    <div class="row g-4">

      <!-- Danh sách sản phẩm -->
      <div class="col-lg-8">
        <div id="cart-items">
          <?php foreach ($items as $item): ?>
            <div class="cart-item d-flex align-items-center gap-3" id="cart-row-<?= $item['id'] ?>">
              <!-- Ảnh -->
              <a href="?case=product_detail&id=<?= $item['product_id'] ?>">
                <img src="<?= $item['image'] ? '/upnex/uploads/products/'.htmlspecialchars($item['image']) : '/upnex/public/images/placeholder.png' ?>"
                     alt="<?= htmlspecialchars($item['name']) ?>" class="cart-img">
              </a>
              <!-- Tên + giá -->
              <div class="flex-grow-1 min-w-0">
                <a href="?case=product_detail&id=<?= $item['product_id'] ?>" class="text-decoration-none text-dark">
                  <div class="fw-semibold text-truncate"><?= htmlspecialchars($item['name']) ?></div>
                </a>
                <div class="text-danger fw-bold mt-1"><?= number_format($item['unit_price']) ?>đ</div>
                <div class="text-muted small">Còn lại: <?= $item['stock'] ?></div>
              </div>
              <!-- Số lượng -->
              <div class="qty-control flex-shrink-0">
                <button class="qty-btn" onclick="updateQty(<?= $item['id'] ?>, -1, <?= $item['unit_price'] ?>)">−</button>
                <input type="number" class="qty-input" id="qty-<?= $item['id'] ?>"
                       value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>"
                       onchange="setQty(<?= $item['id'] ?>, this.value, <?= $item['unit_price'] ?>)">
                <button class="qty-btn" onclick="updateQty(<?= $item['id'] ?>, 1, <?= $item['unit_price'] ?>)">+</button>
              </div>
              <!-- Thành tiền -->
              <div class="text-end flex-shrink-0" style="min-width:90px">
                <div class="fw-bold text-danger small" id="sub-<?= $item['id'] ?>">
                  <?= number_format($item['unit_price'] * $item['quantity']) ?>đ
                </div>
              </div>
              <!-- Xóa -->
              <button class="btn btn-sm btn-outline-danger rounded-circle flex-shrink-0"
                      style="width:32px;height:32px;padding:0"
                      onclick="UPNEX.removeFromCart(<?= $item['id'] ?>)"
                      title="Xóa">
                <i class="bi bi-x"></i>
              </button>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-between mt-3">
          <a href="?case=products" class="btn btn-outline-primary rounded-pill btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Tiếp tục mua sắm
          </a>
        </div>
      </div>

      <!-- Tóm tắt đơn hàng -->
      <div class="col-lg-4">
        <div class="order-summary-card">
          <h5 class="fw-bold mb-4">Tóm tắt đơn hàng</h5>

          <!-- Voucher -->
          <div class="mb-3">
            <label class="form-label small fw-semibold">Mã giảm giá</label>
            <div class="input-group input-group-sm">
              <input type="text" id="voucher-code" class="form-control text-uppercase"
                     placeholder="Nhập mã voucher...">
              <button class="btn btn-outline-primary" onclick="UPNEX.applyVoucher()">Áp dụng</button>
            </div>
            <div id="voucher-msg" class="small mt-1"></div>
          </div>

          <hr>

          <!-- Tổng tiền -->
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Tạm tính</span>
            <span id="subtotal-display" data-value="<?= $subtotal ?>"><?= number_format($subtotal) ?>đ</span>
          </div>
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Giảm giá</span>
            <span id="discount-amount" class="text-success">-0đ</span>
          </div>
          <div class="d-flex justify-content-between mb-2 small">
            <span class="text-muted">Phí vận chuyển</span>
            <span>30.000đ</span>
          </div>
          <hr>
          <div class="d-flex justify-content-between fw-bold mb-4">
            <span>Tổng cộng</span>
            <span class="text-danger fs-5" id="total-display"><?= number_format($subtotal + 30000) ?>đ</span>
          </div>

          <input type="hidden" id="subtotal-value" data-value="<?= $subtotal ?>">
          <input type="hidden" id="hidden-voucher" value="">
          <input type="hidden" id="hidden-discount" value="0">

          <a href="?case=checkout" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">
            <i class="bi bi-credit-card me-2"></i>Tiến hành thanh toán
          </a>

          <!-- Trust badges nhỏ -->
          <div class="mt-3 d-flex justify-content-center gap-3">
            <span class="text-muted small"><i class="bi bi-shield-check text-success me-1"></i>Bảo mật</span>
            <span class="text-muted small"><i class="bi bi-truck text-primary me-1"></i>Giao nhanh</span>
            <span class="text-muted small"><i class="bi bi-arrow-return-left text-warning me-1"></i>Đổi trả</span>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
const SHIPPING = 30000;

function updateQty(cartId, delta, unitPrice) {
  const input = document.getElementById('qty-' + cartId);
  const newQty = Math.max(1, parseInt(input.value) + delta);
  input.value = newQty;
  sendUpdateCart(cartId, newQty, unitPrice);
}

function setQty(cartId, qty, unitPrice) {
  sendUpdateCart(cartId, parseInt(qty) || 1, unitPrice);
}

async function sendUpdateCart(cartId, qty, unitPrice) {
  const fd = new FormData();
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
  fd.append('cart_id', cartId);
  fd.append('quantity', qty);
  const res  = await fetch('?case=update_cart', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    // Cập nhật thành tiền dòng
    const subEl = document.getElementById('sub-' + cartId);
    if (subEl) subEl.textContent = formatVND(unitPrice * qty) + 'đ';
    recalcSummary();
  } else {
    UPNEX.showToast(data.message, 'warning');
    location.reload();
  }
}

function recalcSummary() {
  let subtotal = 0;
  document.querySelectorAll('[id^="sub-"]').forEach(el => {
    subtotal += parseInt(el.textContent.replace(/[^\d]/g, '')) || 0;
  });
  const discount = parseInt(document.getElementById('hidden-discount').value) || 0;
  const total    = subtotal - discount + SHIPPING;

  document.getElementById('subtotal-display').textContent = formatVND(subtotal) + 'đ';
  document.getElementById('subtotal-value').dataset.value  = subtotal;
  document.getElementById('total-display').textContent    = formatVND(total) + 'đ';
}

function formatVND(n) { return parseInt(n).toLocaleString('vi-VN'); }
</script>
