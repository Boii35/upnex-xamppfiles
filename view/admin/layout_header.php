<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle).' — ' : '' ?>UPNEX Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/public/css/upnex.css" rel="stylesheet">
  
  <!-- Admin helpers - load trước content để scripts có thể sử dụng -->
  <script>
    function getCsrfToken() {
      return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }
    
    function adminShowToast(msg, type = 'info') {
      if (typeof UPNEX !== 'undefined' && UPNEX.showToast) {
        UPNEX.showToast(msg, type);
      } else {
        console.log('[Toast]', type.toUpperCase(), msg);
      }
    }
  </script>
</head>
<body>
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index:1100"></div>

<!-- ── SIDEBAR ────────────────────────────────────────────────── -->
<aside class="admin-sidebar" id="admin-sidebar">
  <div class="sidebar-brand">
    <div class="d-flex align-items-center gap-2">
      <div class="logo-icon logo-icon-sm"><span>U</span></div>
      <div>
        <div class="text-white fw-bold" style="font-size:.95rem;line-height:1.1">UPNEX Admin</div>
        <div class="text-white-50" style="font-size:.7rem"><?= htmlspecialchars($admin['name'] ?? '') ?></div>
      </div>
    </div>
  </div>

  <nav class="py-2 flex-grow-1 overflow-auto">
    <?php
      $case = $_GET['case'] ?? 'admin';
      function sidebarLink($href, $icon, $label, $currentCase, $matches = []) {
        $active = in_array($currentCase, $matches) ? 'active' : '';
        echo "<a href=\"$href\" class=\"nav-link $active\"><i class=\"bi $icon\"></i>$label</a>";
      }
    ?>
    <div class="sidebar-section">Tổng quan</div>
    <?php sidebarLink('?case=admin', 'bi-speedometer2', 'Dashboard', $case, ['admin','admin_dashboard']); ?>

    <div class="sidebar-section mt-2">Quản lý</div>
    <?php sidebarLink('?case=admin_products',   'bi-box-seam',     'Sản phẩm',     $case, ['admin_products','admin_product_add','admin_product_edit']); ?>
    <?php sidebarLink('?case=admin_categories', 'bi-grid',         'Danh mục',     $case, ['admin_categories']); ?>
    <?php sidebarLink('?case=admin_orders',     'bi-receipt',      'Đơn hàng',     $case, ['admin_orders']); ?>
    <?php sidebarLink('?case=admin_users',      'bi-people',       'Khách hàng',   $case, ['admin_users']); ?>
    <?php sidebarLink('?case=admin_employees',  'bi-person-badge', 'Nhân viên',    $case, ['admin_employees','admin_employee_add']); ?>

    <div class="sidebar-section mt-2">Marketing</div>
    <?php sidebarLink('?case=admin_vouchers', 'bi-ticket-perforated', 'Voucher', $case, ['admin_vouchers']); ?>
    <?php sidebarLink('?case=admin_reviews',  'bi-star',              'Đánh giá', $case, ['admin_reviews']); ?>
    <?php sidebarLink('?case=admin_send_voucher', 'bi-envelope-heart', 'Gửi Voucher', $case, ['admin_send_voucher']); ?>

    <div class="sidebar-section mt-2">Báo cáo</div>
    <?php sidebarLink('?case=admin_revenue', 'bi-bar-chart-line', 'Doanh thu', $case, ['admin_revenue']); ?>

    <div class="mt-auto py-2">
      <a href="?case=home" class="nav-link" target="_blank"><i class="bi bi-box-arrow-up-right"></i>Xem website</a>
      <a href="?case=admin_logout" class="nav-link text-danger-emphasis"><i class="bi bi-box-arrow-right"></i>Đăng xuất</a>
    </div>
  </nav>
</aside>

<!-- ── MAIN ──────────────────────────────────────────────────── -->
<div class="admin-main">

  <!-- Topbar -->
  <header class="admin-topbar">
    <button class="btn btn-sm btn-light d-lg-none" onclick="document.getElementById('admin-sidebar').classList.toggle('show')">
      <i class="bi bi-list fs-5"></i>
    </button>
    <div class="flex-grow-1">
      <h6 class="mb-0 fw-bold text-dark"><?= $pageTitle ?? 'Dashboard' ?></h6>
    </div>
    <div class="d-flex align-items-center gap-3">
      <span class="text-muted small d-none d-md-inline">
        <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y') ?>
      </span>
      <div class="dropdown">
        <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
          <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
               style="width:28px;height:28px;font-size:.75rem;font-weight:700">
            <?= mb_strtoupper(mb_substr($admin['name'] ?? '',0,1)) ?>
          </div>
          <span class="d-none d-md-inline small fw-semibold"><?= htmlspecialchars($admin['name'] ?? '') ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
          <li><span class="dropdown-item-text small text-muted"><?= htmlspecialchars($admin['position'] ?? '') ?></span></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item small" href="?case=admin_logout"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a></li>
        </ul>
      </div>
    </div>
  </header>

  <!-- Content area bắt đầu -->
  <div class="admin-content">
