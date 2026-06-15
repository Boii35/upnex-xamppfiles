<?php $pageTitle = 'Đăng ký'; ?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">

      <div class="text-center mb-4">
        <a href="?case=home" class="d-inline-flex align-items-center gap-2 text-decoration-none mb-3">
          <div class="logo-icon"><span>U</span></div>
          <span class="logo-text">UPNEX</span>
        </a>
        <h2 class="fw-bold">Tạo tài khoản</h2>
        <p class="text-muted small">Tham gia UPNEX để mua sắm dễ dàng hơn</p>
      </div>

      <div class="form-card">
        <form method="POST" action="?case=register" id="register-form" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

          <!-- Họ tên -->
          <div class="mb-3">
            <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
              <input type="text" name="name"
                     class="form-control border-start-0 ps-0 <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                     placeholder="Nguyễn Văn A"
                     value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
            </div>
            <?php if (isset($errors['name'])): ?>
              <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i><?= $errors['name'] ?></div>
            <?php endif; ?>
          </div>

          <!-- Email -->
          <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
              <input type="email" name="email"
                     class="form-control border-start-0 ps-0 <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                     placeholder="example@email.com"
                     value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
            </div>
            <?php if (isset($errors['email'])): ?>
              <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i><?= $errors['email'] ?></div>
            <?php endif; ?>
          </div>

          <!-- Số điện thoại -->
          <div class="mb-3">
            <label class="form-label">Số điện thoại</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-telephone text-muted"></i></span>
              <input type="tel" name="phone"
                     class="form-control border-start-0 ps-0 <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                     placeholder="0901234567"
                     value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
            </div>
            <?php if (isset($errors['phone'])): ?>
              <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i><?= $errors['phone'] ?></div>
            <?php endif; ?>
          </div>

          <!-- Mật khẩu -->
          <div class="mb-3">
            <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
              <input type="password" name="password" id="pw1"
                     class="form-control border-start-0 ps-0 border-end-0 <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                     placeholder="Ít nhất 8 ký tự" required>
              <button type="button" class="input-group-text bg-light" onclick="togglePw('pw1','icon1')">
                <i class="bi bi-eye" id="icon1"></i>
              </button>
            </div>
            <?php if (isset($errors['password'])): ?>
              <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i><?= $errors['password'] ?></div>
            <?php endif; ?>
            <!-- Strength bar -->
            <div class="mt-1" id="pw-strength-wrap" style="display:none">
              <div class="progress" style="height:4px">
                <div class="progress-bar" id="pw-strength-bar" style="width:0"></div>
              </div>
              <small id="pw-strength-label" class="text-muted"></small>
            </div>
          </div>

          <!-- Xác nhận mật khẩu -->
          <div class="mb-4">
            <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock-fill text-muted"></i></span>
              <input type="password" name="confirm_password" id="pw2"
                     class="form-control border-start-0 ps-0 border-end-0 <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                     placeholder="Nhập lại mật khẩu" required>
              <button type="button" class="input-group-text bg-light" onclick="togglePw('pw2','icon2')">
                <i class="bi bi-eye" id="icon2"></i>
              </button>
            </div>
            <?php if (isset($errors['confirm_password'])): ?>
              <div class="text-danger small mt-1"><i class="bi bi-exclamation-circle me-1"></i><?= $errors['confirm_password'] ?></div>
            <?php endif; ?>
          </div>

          <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">
            <i class="bi bi-person-plus me-2"></i>Tạo tài khoản
          </button>
        </form>

        <div class="text-center mt-4">
          <span class="text-muted small">Đã có tài khoản?</span>
          <a href="?case=login" class="text-primary fw-semibold ms-1 text-decoration-none small">Đăng nhập</a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function togglePw(fieldId, iconId) {
  const f = document.getElementById(fieldId);
  const i = document.getElementById(iconId);
  f.type = f.type === 'password' ? 'text' : 'password';
  i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

// Password strength meter
document.getElementById('pw1').addEventListener('input', function() {
  const v   = this.value;
  const wrap = document.getElementById('pw-strength-wrap');
  const bar  = document.getElementById('pw-strength-bar');
  const lbl  = document.getElementById('pw-strength-label');
  if (!v) { wrap.style.display = 'none'; return; }
  wrap.style.display = 'block';

  let score = 0;
  if (v.length >= 8)  score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;

  const levels = [
    [25,'bg-danger','Yếu'],
    [50,'bg-warning','Trung bình'],
    [75,'bg-info','Khá mạnh'],
    [100,'bg-success','Mạnh'],
  ];
  const [w, cls, text] = levels[score - 1] || levels[0];
  bar.style.width = w + '%';
  bar.className   = 'progress-bar ' + cls;
  lbl.textContent = text;
});
</script>
