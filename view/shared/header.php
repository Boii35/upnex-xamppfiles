<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>UPNEX</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- UPNEX custom CSS -->
  <link href="<?= BASE_URL ?>/public/css/upnex.css" rel="stylesheet">

</head>
<body>

<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index:1100"></div>

<!-- ── HEADER (Topbar + Navbar) ────────────────────────────── -->
<header class="upnex-header">
  <!-- TOPBAR -->
  <div class="topbar py-1 text-white small">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
      <div class="d-flex align-items-center gap-3">
        <span class="d-flex align-items-center gap-2"><i class="bi bi-telephone-fill"></i>Hotline: <strong>1800 6868</strong></span>
        <span class="d-none d-md-inline d-flex align-items-center gap-2"><i class="bi bi-geo-alt-fill"></i>TP. Hồ Chí Minh</span>
      </div>

      <div class="d-flex align-items-center gap-2">
        <span class="vr vr-dark d-none d-md-inline"></span>


        <?php if (!empty($_SESSION[SESSION_USER])): ?>
          <?php
            $tier = $_SESSION[SESSION_USER]['tier'];
            $tierClass = match($tier) { 'Diamond'=>'tier-diamond', 'Gold'=>'tier-gold', default=>'tier-silver' };
          ?>
          <a href="?case=profile" class="text-white text-decoration-none d-flex align-items-center gap-2">
            <i class="bi bi-person-circle"></i>
            <span>Xin chào, <strong><?= htmlspecialchars($_SESSION[SESSION_USER]['name']) ?></strong></span>
            <span class="badge <?= $tierClass ?>"><?= htmlspecialchars($tier) ?></span>
          </a>
          <span class="ms-md-2">|</span>
          <a href="?case=logout" class="text-white text-decoration-none">Đăng xuất</a>
        <?php else: ?>
          <a href="?case=login" class="text-white text-decoration-none"><i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập</a>
          <span class="mx-2 d-none d-md-inline">|</span>
          <a href="?case=register" class="text-white text-decoration-none">Đăng ký</a>
        <?php endif; ?>

        <!-- Theme toggle -->
        <button class="nav-icon-btn theme-toggle-btn ms-1" id="theme-toggle" type="button" title="Chuyển giao diện">
          <i class="bi bi-moon-stars theme-toggle-icon"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">

    <!-- Logo -->
    <a class="navbar-brand d-flex align-items-center gap-2" href="?case=home">
      <div class="logo-icon"><span>U</span></div>
      <span class="logo-text">UPNEX</span>
    </a>

    <!-- Search bar -->
    <div class="search-wrapper flex-grow-1 mx-4 position-relative d-none d-lg-block">
      <div class="input-group">
        <input type="text" id="search-input" class="form-control search-input"
               placeholder="Tìm kiếm điện thoại, laptop, tablet..."
               value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
        <a href="?case=products&q=" id="search-btn" class="btn btn-primary px-3">
          <i class="bi bi-search"></i>
        </a>
      </div>
      <!-- Autocomplete dropdown -->
      <div id="search-suggest" class="search-suggest bg-white border rounded shadow-sm"
           style="display:none; position:absolute; top:100%; left:0; right:0; z-index:999; max-height:360px; overflow-y:auto"></div>
    </div>

    <!-- Cart + toggle -->
    <div class="d-flex align-items-center gap-3">
      <a href="?case=order_history" class="nav-icon-btn d-none d-md-flex" title="Đơn hàng">
        <i class="bi bi-receipt"></i>
      </a>
      <a href="?case=cart" class="nav-icon-btn position-relative" title="Giỏ hàng">
        <i class="bi bi-cart3 fs-5"></i>
        <?php if ($cartCount > 0): ?>
          <span class="cart-badge badge bg-danger rounded-pill position-absolute"
                style="top:-6px;right:-8px;font-size:10px"><?= $cartCount ?></span>
        <?php else: ?>
          <span class="cart-badge badge bg-danger rounded-pill position-absolute"
                style="top:-6px;right:-8px;font-size:10px;display:none">0</span>
        <?php endif; ?>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>

    <!-- Nav links -->
    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav ms-auto mt-3 mt-lg-0 align-items-lg-center gap-lg-1">

        <li class="nav-item">
          <a class="nav-link" href="?case=home"><i class="bi bi-house"></i> Trang chủ</a>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
            <i class="bi bi-grid"></i> Tất cả danh mục
          </a>
          <ul class="dropdown-menu shadow-sm" style="min-width:260px">
            <li>
              <a class="dropdown-item" href="?case=products">
                <i class="bi bi-bag me-2"></i>Tất cả sản phẩm
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <?php foreach ($categories as $cat): ?>
              <li>
                <a class="dropdown-item" href="?case=products&cat=<?= $cat['id'] ?>">
                  <i class="bi bi-chevron-right small text-muted me-1"></i>
                  <?= htmlspecialchars($cat['name']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>

        <li class="nav-item d-lg-none">
          <a class="nav-link" href="?case=products&cat=1"><i class="bi bi-phone"></i> Điện thoại</a>
        </li>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="?case=products&cat=2"><i class="bi bi-laptop"></i> Laptop</a>
        </li>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="?case=products&cat=3"><i class="bi bi-tablet"></i> Máy tính bảng</a>
        </li>
        <li class="nav-item d-lg-none">
          <a class="nav-link" href="?case=products&cat=4"><i class="bi bi-headphones"></i> Phụ kiện</a>
        </li>

        <li class="nav-item d-none d-lg-flex">
          <a class="nav-link" href="?case=products&cat=1"><i class="bi bi-phone"></i> Điện thoại</a>
        </li>
        <li class="nav-item d-none d-lg-flex">
          <a class="nav-link" href="?case=products&cat=2"><i class="bi bi-laptop"></i> Laptop</a>
        </li>
        <li class="nav-item d-none d-lg-flex">
          <a class="nav-link" href="?case=products&cat=3"><i class="bi bi-tablet"></i> Máy tính bảng</a>
        </li>
        <li class="nav-item d-none d-lg-flex">
          <a class="nav-link" href="?case=products&cat=4"><i class="bi bi-headphones"></i> Phụ kiện</a>
        </li>

      </ul>

      <!-- Search mobile -->
      <div class="d-lg-none mt-2 px-2">
        <div class="input-group">
          <input type="text" class="form-control" placeholder="Tìm kiếm..." id="search-input-mobile" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
          <button class="btn btn-primary" onclick="window.location.href='?case=products&q='+encodeURIComponent(document.getElementById('search-input-mobile').value.trim())">
            <i class="bi bi-search"></i>
          </button>
        </div>
        <div id="search-suggest-mobile" class="search-suggest bg-white border rounded shadow-sm mt-2" style="display:none; max-height:360px; overflow-y:auto"></div>
      </div>

    </div>
  </div>
</nav>

<!-- ── MAIN CONTENT ───────────────────────────────────────── -->
<main class="main-content">

