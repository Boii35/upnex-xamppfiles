<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
  <title>Đăng nhập Admin — UPNEX</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/public/css/upnex.css" rel="stylesheet">
  <style>
    body { background: linear-gradient(135deg,#0f0f1a 0%,#1a1a3e 100%); min-height:100vh; display:flex; align-items:center; }
  </style>
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="text-center mb-4">
        <div class="logo-icon mx-auto mb-3" style="width:56px;height:56px"><span style="font-size:1.5rem">U</span></div>
        <h3 class="text-white fw-bold">UPNEX Admin</h3>
        <p class="text-white-50 small">Đăng nhập vào trang quản trị</p>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger small"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="form-card">
        <form method="POST" action="?case=admin_login">
          <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
              <input type="email" name="email" class="form-control border-start-0 ps-0"
                     placeholder="admin@upnex.vn" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label">Mật khẩu</label>
            <div class="input-group">
              <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
              <input type="password" name="password" id="admin-pw" class="form-control border-start-0 ps-0 border-end-0" placeholder="Mật khẩu" required>
              <button type="button" class="input-group-text bg-light" onclick="toggleAdminPw()"><i class="bi bi-eye" id="admin-pw-icon"></i></button>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold">
            <i class="bi bi-shield-lock me-2"></i>Đăng nhập
          </button>
        </form>
        <div class="text-center mt-3">
          <a href="?case=home" class="text-muted small text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i>Về trang chủ
          </a>
        </div>
      </div>
      <p class="text-center text-white-50 small mt-3">
        Tài khoản mặc định: admin@upnex.vn / Admin@123
      </p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleAdminPw() {
  const f = document.getElementById('admin-pw');
  const i = document.getElementById('admin-pw-icon');
  f.type = f.type==='password' ? 'text' : 'password';
  i.className = f.type==='password' ? 'bi bi-eye' : 'bi bi-eye-slash';
}
</script>
</body>
</html>
