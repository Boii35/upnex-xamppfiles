<?php $pageTitle = 'Gửi Voucher qua Email'; ?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="?case=admin_vouchers" class="btn btn-outline-secondary btn-sm rounded-pill">
    <i class="bi bi-arrow-left me-1"></i>Quay lại
  </a>
  <h5 class="fw-bold mb-0"><i class="bi bi-envelope-heart me-2 text-primary"></i>Gửi Voucher qua Email</h5>
</div>

<?php if (!MAIL_ENABLED): ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    <strong>Email chưa được kích hoạt.</strong>
    Mở file <code>config/config.php</code>, cấu hình Gmail và đặt <code>MAIL_ENABLED = true</code>.
  </div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="bg-white rounded-card shadow-card p-4">
      <h6 class="fw-semibold mb-4">Gửi voucher cho nhóm khách hàng</h6>
      <form method="POST" action="?case=admin_send_voucher" id="send-voucher-form">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="mb-3">
          <label class="form-label small fw-semibold">Chọn voucher <span class="text-danger">*</span></label>
          <select name="voucher_id" class="form-select" required>
            <option value="">-- Chọn voucher --</option>
            <?php foreach ($vouchers as $v): ?>
              <?php if ($v['is_active'] && strtotime($v['expires_at']) > time()): ?>
                <option value="<?= $v['id'] ?>">
                  <?= htmlspecialchars($v['code']) ?> —
                  <?= $v['discount_type']==='percent' ? $v['discount_value'].'%' : number_format($v['discount_value']).'đ' ?>
                  (còn <?= $v['max_uses'] - $v['used_count'] ?> lượt)
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Gửi cho nhóm <span class="text-danger">*</span></label>
          <div class="d-flex flex-column gap-2">
            <div class="form-check p-3 border rounded-3">
              <input class="form-check-input" type="radio" name="target" value="all" id="t-all" checked>
              <label class="form-check-label" for="t-all">
                <span class="fw-semibold">Tất cả khách hàng</span>
                <small class="text-muted d-block">Gửi đến tất cả tài khoản đang hoạt động</small>
              </label>
            </div>
            <div class="form-check p-3 border rounded-3">
              <input class="form-check-input" type="radio" name="target" value="Gold" id="t-gold">
              <label class="form-check-label" for="t-gold">
                <span class="fw-semibold">⭐ Khách Gold</span>
                <small class="text-muted d-block">Chi tiêu từ 5 triệu trở lên</small>
              </label>
            </div>
            <div class="form-check p-3 border rounded-3">
              <input class="form-check-input" type="radio" name="target" value="Diamond" id="t-diamond">
              <label class="form-check-label" for="t-diamond">
                <span class="fw-semibold">💎 Khách Diamond</span>
                <small class="text-muted d-block">Chi tiêu từ 20 triệu trở lên</small>
              </label>
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold"
                <?= !MAIL_ENABLED ? 'disabled' : '' ?>
                onclick="return confirm('Xác nhận gửi email voucher?')">
          <i class="bi bi-send me-2"></i>Gửi voucher
        </button>
      </form>
    </div>
  </div>

  <!-- Hướng dẫn cấu hình email -->
  <div class="col-lg-5">
    <div class="bg-white rounded-card shadow-card p-4">
      <h6 class="fw-semibold mb-3"><i class="bi bi-gear me-2 text-primary"></i>Hướng dẫn cấu hình Gmail</h6>
      <ol class="small text-muted ps-3" style="line-height:2">
        <li>Vào <strong>myaccount.google.com</strong></li>
        <li>Chọn <strong>Bảo mật</strong> → <strong>Xác minh 2 bước</strong> (bật lên)</li>
        <li>Chọn <strong>Mật khẩu ứng dụng</strong></li>
        <li>Chọn <em>Ứng dụng khác</em>, đặt tên <em>UPNEX</em></li>
        <li>Copy 16 ký tự được tạo ra</li>
        <li>Dán vào <code>config/config.php</code>:<br>
          <code>MAIL_PASSWORD = 'xxxx xxxx xxxx xxxx'</code>
        </li>
        <li>Đặt <code>MAIL_ENABLED = true</code></li>
      </ol>

      <hr>

      <h6 class="fw-semibold mb-3"><i class="bi bi-phone me-2 text-danger"></i>Tài khoản test MoMo Sandbox</h6>
      <table class="table table-sm small mb-0">
        <tr><td class="text-muted">Số điện thoại:</td><td><code>0000000001</code></td></tr>
        <tr><td class="text-muted">PIN:</td><td><code>000000</code></td></tr>
        <tr><td class="text-muted">OTP:</td><td><code>000000</code></td></tr>
        <tr><td class="text-muted">ATM test:</td><td><code>9704 0000 0000 0018</code></td></tr>
      </table>
    </div>
  </div>
</div>
