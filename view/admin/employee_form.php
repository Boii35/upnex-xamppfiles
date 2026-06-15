<?php $pageTitle = 'Thêm nhân viên'; ?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="?case=admin_employees" class="btn btn-outline-secondary btn-sm rounded-pill">
    <i class="bi bi-arrow-left me-1"></i>Quay lại
  </a>
  <h5 class="fw-bold mb-0">Thêm nhân viên mới</h5>
</div>

<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="bg-white rounded-card shadow-card p-4">

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger small">
          <i class="bi bi-exclamation-circle me-1"></i>
          <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="?case=admin_employee_add" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <div class="mb-3">
          <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
              <i class="bi bi-person text-muted"></i>
            </span>
            <input type="text" name="name" class="form-control border-start-0 ps-0"
                   placeholder="Nguyễn Văn B" required
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Email <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
              <i class="bi bi-envelope text-muted"></i>
            </span>
            <input type="email" name="email" class="form-control border-start-0 ps-0"
                   placeholder="nhanvien@upnex.vn" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Số điện thoại</label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
              <i class="bi bi-telephone text-muted"></i>
            </span>
            <input type="tel" name="phone" class="form-control border-start-0 ps-0"
                   placeholder="0901234567"
                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Chức vụ</label>
          <select name="position" class="form-select">
            <?php
              $positions = ['Quản trị viên','Nhân viên bán hàng','Nhân viên kho','Nhân viên giao hàng','Kế toán'];
            ?>
            <?php foreach ($positions as $pos): ?>
              <option value="<?= $pos ?>" <?= ($_POST['position'] ?? '') === $pos ? 'selected' : '' ?>>
                <?= $pos ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
          <div class="input-group">
            <span class="input-group-text bg-light border-end-0">
              <i class="bi bi-lock text-muted"></i>
            </span>
            <input type="password" name="password" id="emp-pw"
                   class="form-control border-start-0 ps-0 border-end-0"
                   placeholder="Ít nhất 8 ký tự" required minlength="8">
            <button type="button" class="input-group-text bg-light" onclick="togglePw()">
              <i class="bi bi-eye" id="pw-icon"></i>
            </button>
          </div>
          <div class="form-text">Mật khẩu ít nhất 8 ký tự, nên có chữ hoa và số.</div>
        </div>

        <div class="mb-4">
          <label class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
          <input type="password" id="emp-pw2" class="form-control"
                 placeholder="Nhập lại mật khẩu" required>
          <div class="text-danger small mt-1" id="pw-match-msg" style="display:none">
            <i class="bi bi-exclamation-circle me-1"></i>Mật khẩu không khớp.
          </div>
        </div>

        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-primary rounded-pill py-2 fw-semibold" id="submit-btn">
            <i class="bi bi-person-plus me-2"></i>Thêm nhân viên
          </button>
          <a href="?case=admin_employees" class="btn btn-outline-secondary rounded-pill">Hủy</a>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function togglePw() {
  const f = document.getElementById('emp-pw');
  const i = document.getElementById('pw-icon');
  f.type = f.type === 'password' ? 'text' : 'password';
  i.className = f.type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}

// Validate xác nhận mật khẩu real-time
document.getElementById('emp-pw2').addEventListener('input', function() {
  const pw1 = document.getElementById('emp-pw').value;
  const msg = document.getElementById('pw-match-msg');
  const btn = document.getElementById('submit-btn');
  if (this.value && this.value !== pw1) {
    msg.style.display = 'block';
    this.classList.add('is-invalid');
    btn.disabled = true;
  } else {
    msg.style.display = 'none';
    this.classList.remove('is-invalid');
    btn.disabled = false;
  }
});

// Sync password confirm vào input hidden trước submit
document.querySelector('form').addEventListener('submit', function(e) {
  const pw1 = document.getElementById('emp-pw').value;
  const pw2 = document.getElementById('emp-pw2').value;
  if (pw1 !== pw2) {
    e.preventDefault();
    document.getElementById('pw-match-msg').style.display = 'block';
  }
});
</script>
