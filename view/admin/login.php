<?php $pageTitle = 'Đăng nhập Admin'; ?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">

      <div class="text-center mb-4">
        <a href="?case=home" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3">
          <div class="logo-icon"><span>U</span></div>
          <span class="logo-text">UPNEX</span>
        </a>
        <h2 class="fw-bold">Đăng nhập Admin</h2>
        <p class="text-muted small">Chào mừng quay lại trang quản trị!</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger small"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="form-card">
        <form method="POST" action="?case=admin_login">
          <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

          <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
              <input type="email" name="email" class="form-control border-start-0 ps-0"
                     placeholder="admin@upnex.vn" required
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
          </div>

          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label mb-0">Mật khẩu</label>
            </div>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
              <input type="password" name="password" id="pw-field" class="form-control border-start-0 ps-0 border-end-0"
                     placeholder="Mật khẩu" required>
              <button type="button" class="input-group-text bg-light" onclick="togglePw()">
                <i class="bi bi-eye" id="pw-icon"></i>
              </button>
            </div>
          </div>

          <div class="mb-4">
          </div>

          <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">
            <i class="bi bi-shield-lock me-2"></i>Đăng nhập
          </button>
        </form>

        <div class="text-center mt-4">
          <span class="text-muted small">Tài khoản: admin@upnex.vn / Admin@123</span>
        </div>
      </div>

      <div class="text-center mt-3">
        <a href="?case=home" class="text-muted small text-decoration-none">
          <i class="bi bi-arrow-left me-1"></i>Về trang chủ
        </a>
      </div>
    </div>
  </div>
</div>

<script>
function togglePw() {
  const f = document.getElementById('pw-field');
  const i = document.getElementById('pw-icon');
  f.type = f.type === 'password' ? 'text' : 'password';
  i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
