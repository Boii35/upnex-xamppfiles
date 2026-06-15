<?php $pageTitle = 'Tài khoản của tôi'; ?>

<div class="container py-4">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?case=home" class="text-decoration-none">Trang chủ</a></li>
      <li class="breadcrumb-item active">Tài khoản</li>
    </ol>
  </nav>

  <?php if (!empty($_GET['updated'])): ?>
    <div class="alert alert-success d-flex align-items-center gap-2">
      <i class="bi bi-check-circle-fill"></i>Cập nhật thông tin thành công!
    </div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- Sidebar thông tin -->
    <div class="col-lg-3">
      <!-- Avatar + tier card -->
      <div class="bg-white rounded-card shadow-card p-4 text-center mb-3">
        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
             style="width:80px;height:80px;font-size:2rem;font-weight:800">
          <?= mb_strtoupper(mb_substr($user['name'], 0, 1)) ?>
        </div>
        <h6 class="fw-bold mb-1"><?= htmlspecialchars($user['name']) ?></h6>
        <div class="text-muted small mb-2"><?= htmlspecialchars($user['email']) ?></div>

        <?php
          $tierClass = match($user['tier']) {
            'Diamond' => 'tier-diamond',
            'Gold'    => 'tier-gold',
            default   => 'tier-silver',
          };
          $tierIcon  = match($user['tier']) {
            'Diamond' => 'bi-gem',
            'Gold'    => 'bi-star-fill',
            default   => 'bi-award',
          };
          $tierDesc  = match($user['tier']) {
            'Diamond' => 'Miễn phí ship + ưu tiên xử lý',
            'Gold'    => 'Giảm thêm 2% khi thanh toán online',
            default   => 'Tích điểm để lên hạng Gold',
          };
        ?>
        <span class="badge <?= $tierClass ?> px-3 py-2 mb-2">
          <i class="bi <?= $tierIcon ?> me-1"></i><?= $user['tier'] ?> Member
        </span>
        <div class="text-muted" style="font-size:.75rem"><?= $tierDesc ?></div>
      </div>

      <!-- Tiến độ tier -->
      <div class="bg-white rounded-card shadow-card p-3 mb-3">
        <div class="small fw-semibold mb-2">Tiến độ hạng thành viên</div>
        <?php
          $spent      = (float)$user['total_spent'];
          $nextTier   = '';
          $nextTarget = 0;
          $progress   = 0;
          if ($user['tier'] === 'Silver') {
            $nextTier = 'Gold'; $nextTarget = TIER_GOLD;
            $progress = min(100, round($spent / TIER_GOLD * 100));
          } elseif ($user['tier'] === 'Gold') {
            $nextTier = 'Diamond'; $nextTarget = TIER_DIAMOND;
            $progress = min(100, round($spent / TIER_DIAMOND * 100));
          } else {
            $progress = 100;
          }
        ?>
        <?php if ($user['tier'] !== 'Diamond'): ?>
          <div class="d-flex justify-content-between small text-muted mb-1">
            <span><?= number_format($spent) ?>đ</span>
            <span><?= number_format($nextTarget) ?>đ</span>
          </div>
          <div class="progress mb-1" style="height:8px;border-radius:4px">
            <div class="progress-bar <?= $user['tier']==='Silver'?'bg-warning':'bg-info' ?>"
                 style="width:<?= $progress ?>%"></div>
          </div>
          <div class="text-muted" style="font-size:.72rem">
            Còn <strong><?= number_format(max(0, $nextTarget - $spent)) ?>đ</strong> nữa lên <strong><?= $nextTier ?></strong>
          </div>
        <?php else: ?>
          <div class="progress mb-1" style="height:8px">
            <div class="progress-bar bg-info" style="width:100%"></div>
          </div>
          <div class="text-info small fw-semibold"><i class="bi bi-gem me-1"></i>Hạng cao nhất!</div>
        <?php endif; ?>
      </div>

      <!-- Menu tài khoản -->
      <div class="bg-white rounded-card shadow-card overflow-hidden">
        <a href="?case=profile" class="d-flex align-items-center gap-3 p-3 border-bottom text-decoration-none text-primary fw-semibold" style="background:#f0f4ff">
          <i class="bi bi-person-circle"></i>Thông tin cá nhân
        </a>
        <a href="?case=order_history" class="d-flex align-items-center gap-3 p-3 border-bottom text-decoration-none text-dark">
          <i class="bi bi-receipt text-muted"></i>Đơn hàng của tôi
        </a>
        <a href="?case=logout" class="d-flex align-items-center gap-3 p-3 text-decoration-none text-danger">
          <i class="bi bi-box-arrow-right"></i>Đăng xuất
        </a>
      </div>
    </div>

    <!-- Main content -->
    <div class="col-lg-9">

      <!-- Form cập nhật thông tin -->
      <div class="bg-white rounded-card shadow-card p-4 mb-4">
        <h5 class="fw-bold mb-4"><i class="bi bi-person me-2 text-primary"></i>Thông tin cá nhân</h5>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger small">
            <?php foreach ($errors as $err): ?>
              <div><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="?case=profile" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':'' ?>"
                     value="<?= htmlspecialchars($user['name']) ?>" required>
              <?php if (isset($errors['name'])): ?>
                <div class="invalid-feedback"><?= $errors['name'] ?></div>
              <?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"
                     disabled title="Không thể thay đổi email">
              <div class="form-text">Email không thể thay đổi.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Số điện thoại</label>
              <input type="tel" name="phone" class="form-control <?= isset($errors['phone'])?'is-invalid':'' ?>"
                     value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                     placeholder="0901234567">
              <?php if (isset($errors['phone'])): ?>
                <div class="invalid-feedback"><?= $errors['phone'] ?></div>
              <?php endif; ?>
            </div>

            <div class="col-md-6">
              <label class="form-label">Ngày tham gia</label>
              <input type="text" class="form-control" disabled
                     value="<?= date('d/m/Y', strtotime($user['created_at'])) ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Địa chỉ mặc định</label>
              <textarea name="address" class="form-control" rows="2"
                        placeholder="Số nhà, đường, phường, quận, tỉnh/thành..."><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
          </div>

          <div class="mt-4">
            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-semibold">
              <i class="bi bi-check2 me-2"></i>Lưu thay đổi
            </button>
          </div>
        </form>
      </div>

      <!-- Đổi mật khẩu -->
      <div class="bg-white rounded-card shadow-card p-4">
        <h5 class="fw-bold mb-4"><i class="bi bi-lock me-2 text-primary"></i>Đổi mật khẩu</h5>
        <form id="change-pw-form" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Mật khẩu hiện tại</label>
              <input type="password" id="old-pw" name="old_password" class="form-control"
                     placeholder="Mật khẩu hiện tại">
            </div>
            <div class="col-md-4">
              <label class="form-label">Mật khẩu mới</label>
              <input type="password" id="new-pw" name="new_password" class="form-control"
                     placeholder="Ít nhất 8 ký tự">
            </div>
            <div class="col-md-4">
              <label class="form-label">Xác nhận mật khẩu mới</label>
              <input type="password" id="confirm-pw" name="confirm_password" class="form-control"
                     placeholder="Nhập lại mật khẩu mới">
            </div>
          </div>
          <div id="pw-change-msg" class="small mt-2"></div>
          <div class="mt-3">
            <button type="button" class="btn btn-warning rounded-pill px-4 fw-semibold"
                    onclick="changePassword()">
              <i class="bi bi-key me-2"></i>Đổi mật khẩu
            </button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
async function changePassword() {
  const oldPw  = document.getElementById('old-pw').value.trim();
  const newPw  = document.getElementById('new-pw').value.trim();
  const conPw  = document.getElementById('confirm-pw').value.trim();
  const msgEl  = document.getElementById('pw-change-msg');

  if (!oldPw || !newPw || !conPw) {
    msgEl.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Vui lòng điền đầy đủ thông tin.</span>';
    return;
  }
  if (newPw.length < 8) {
    msgEl.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Mật khẩu mới tối thiểu 8 ký tự.</span>';
    return;
  }
  if (newPw !== conPw) {
    msgEl.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>Mật khẩu xác nhận không khớp.</span>';
    return;
  }

  const fd = new FormData();
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
  fd.append('old_password', oldPw);
  fd.append('new_password', newPw);

  const res  = await fetch('?case=change_password', { method: 'POST', body: fd });
  const data = await res.json();

  if (data.success) {
    msgEl.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>Đổi mật khẩu thành công!</span>';
    document.getElementById('change-pw-form').reset();
  } else {
    msgEl.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>${data.message}</span>`;
  }
}
</script>
