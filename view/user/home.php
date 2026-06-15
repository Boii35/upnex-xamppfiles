<!-- Hero Section (đẹp mắt + hiệu ứng động + 1 màn hình trượt giới thiệu sản phẩm) -->
<section class="hero-section hero-wolf">
  <div class="hero-orb orb-1"></div>
  <div class="hero-orb orb-2"></div>
  <div class="hero-orb orb-3"></div>

  <div class="container position-relative" style="z-index:2;">
    <div class="row align-items-center g-4">
      <div class="col-lg-5">
        <div class="badge-tag hero-badge-wrap">
          <span class="hero-pulse"></span>
          Deal nhanh mỗi ngày — chuẩn công nghệ
        </div>

        <h1 class="hero-title mt-3 mb-3">
          Mua <span class="highlight">công nghệ</span>
          <br>chuẩn nhanh — chuẩn bền.
        </h1>

        <p class="hero-subtitle mb-4">
          Điện thoại, laptop, tablet, phụ kiện — giá tốt + hỗ trợ tận tâm.
        </p>

        <div class="d-flex gap-2 flex-wrap">
          <a class="btn btn-primary rounded-pill px-4 fw-semibold" href="?case=products&sort=best_sell">
            Xem bán chạy
          </a>
          <a class="btn btn-outline-primary rounded-pill px-4 fw-semibold" href="?case=products">
            Tất cả sản phẩm
          </a>
        </div>

        <div class="mt-4">
          <div class="hero-typing fw-semibold text-primary">iPhone • Samsung • Xiaomi • MacBook • Gaming</div>
        </div>
      </div>

      <div class="col-lg-7">
        <div id="heroCarousel" class="hero-carousel carousel slide" data-bs-ride="carousel" data-bs-interval="4200">
          <div class="carousel-indicators position-relative m-0 px-3" style="z-index:3;">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
          </div>

          <div class="carousel-inner">
            <?php
              // Lấy dữ liệu gợi ý từ $featured/$newArrive nếu có
              $heroSlides = [
                ['title'=>'iPhone & flagship', 'subtitle'=>'Săn deal + trả góp nhẹ', 'cat'=>5],
                ['title'=>'Laptop cấu hình xịn', 'subtitle'=>'Gaming/đồ họa mượt', 'cat'=>2],
                ['title'=>'Phụ kiện đầy đủ', 'subtitle'=>'Tai nghe, sạc, cáp xịn', 'cat'=>4],
              ];

              $slideProducts = array_merge(array_slice($featured ?? [], 0, 6), array_slice($newArrive ?? [], 0, 6));
            ?>

            <?php foreach ($heroSlides as $idx => $s): ?>
              <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                <a class="stretched-link" href="?case=products&cat=<?= (int)$s['cat'] ?>"></a>
                <div class="hero-slide-bg"></div>

                <div class="d-flex justify-content-center gap-3 hero-slide-products">
                  <?php
                    $p0 = $slideProducts[$idx*2] ?? null;
                    $p1 = $slideProducts[$idx*2+1] ?? null;
                  ?>

                  <?php if ($p0): ?>
                    <div class="hero-product-pill">
                      <img
                        src="<?= !empty($p0['main_image']) ? BASE_URL.'/uploads/products/'.htmlspecialchars($p0['main_image']) : BASE_URL.'/public/images/placeholder.png' ?>"
                        data-main-image="<?= htmlspecialchars((string)($p0['main_image'] ?? '')) ?>"

                        alt="" class="hero-product-img"
                        onerror="this.src='<?= BASE_URL ?>/public/images/placeholder.png'">
                      <div class="hero-product-meta p-3">
                        <div class="small text-white-50">Gợi ý</div>
                        <div class="fw-semibold text-white" style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                          <?= htmlspecialchars($p0['name']) ?>
                        </div>
                        <div class="small text-primary fw-bold">Giá: <?= number_format($p0['final_price'] ?? $p0['sale_price'] ?? $p0['price']) ?>đ</div>
                      </div>
                    </div>
                  <?php endif; ?>

                  <?php if ($p1): ?>
                    <div class="hero-product-pill pill-2">
                      <img
                        src="<?= !empty($p1['main_image']) ? BASE_URL.'/uploads/products/'.htmlspecialchars($p1['main_image']) : BASE_URL.'/public/images/placeholder.png' ?>"
                        data-main-image="<?= htmlspecialchars((string)($p1['main_image'] ?? '')) ?>"

                        alt="" class="hero-product-img"
                        onerror="this.src='<?= BASE_URL ?>/public/images/placeholder.png'">
                      <div class="hero-product-meta p-3">
                        <div class="small text-white-50">Gợi ý</div>
                        <div class="fw-semibold text-white" style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                          <?= htmlspecialchars($p1['name']) ?>
                        </div>
                        <div class="small text-primary fw-bold">Giá: <?= number_format($p1['final_price'] ?? $p1['sale_price'] ?? $p1['price']) ?>đ</div>
                      </div>
                    </div>
                  <?php endif; ?>
                </div>

                <div class="hero-slide-text mt-3">
                  <div class="small text-white-50">Danh mục</div>
                  <div class="h4 text-white fw-bold"><?= htmlspecialchars($s['title']) ?></div>
                  <div class="small text-white-50"><?= htmlspecialchars($s['subtitle']) ?></div>
                  <div class="mt-3">
                    <span class="btn btn-primary btn-sm rounded-pill">Xem sản phẩm →</span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>




<!-- ── DANH MỤC (kept existing) ─────────────────────────────── -->
<section class="category-section">
  <div class="container">
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3">
      <?php
        $catIcons = [
          'Điện thoại' => 'bi-phone',   'Laptop'  => 'bi-laptop',
          'Tablet'     => 'bi-tablet',  'Phụ kiện'=> 'bi-headphones',
          'iPhone'     => 'bi-apple',   'Samsung' => 'bi-phone-flip',
          'MacBook'    => 'bi-apple',   'iPad'    => 'bi-tablet',
          'Tai nghe'   => 'bi-headphones', 'Sạc & Cáp' => 'bi-lightning-charge',
        ];
      ?>
      <?php foreach ($categories as $cat): ?>
        <div class="col">
          <a href="?case=products&cat=<?= $cat['id'] ?>" class="cat-card text-center">
            <div class="cat-icon">
              <i class="bi <?= $catIcons[$cat['name']] ?? 'bi-grid' ?>"></i>
            </div>
            <span class="cat-label"><?= htmlspecialchars($cat['name']) ?></span>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── PROMO BANNERS ─────────────────────────────────────────── -->
<section class="py-4">
  <div class="container">
    <div class="row g-3">
      <div class="col-md-6">
        <a href="?case=products&cat=1" class="promo-banner promo-banner-1">
          <div>
            <div class="small text-info fw-semibold mb-1">iPhone 16 Series</div>
            <div class="h5 text-white fw-bold mb-1">Giảm đến 5 triệu</div>
            <div class="small text-white-50">Chỉ trong tháng 6</div>
            <span class="btn btn-primary btn-sm mt-2">Xem ngay →</span>
          </div>
          <i class="bi bi-phone text-primary opacity-25" style="font-size:5rem;line-height:1"></i>
        </a>
      </div>
      <div class="col-md-6">
        <a href="?case=products&cat=2" class="promo-banner promo-banner-2">
          <div>
            <div class="small text-warning fw-semibold mb-1">MacBook & Laptop</div>
            <div class="h5 text-white fw-bold mb-1">Trả góp 0%</div>
            <div class="small text-white-50">Lãi suất 0% 12 tháng</div>
            <span class="btn btn-warning btn-sm mt-2 text-dark">Xem ngay →</span>
          </div>
          <i class="bi bi-laptop text-warning opacity-25" style="font-size:5rem;line-height:1"></i>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ── SẢN PHẨM BÁN CHẠY ─────────────────────────────────────── -->
<section class="py-4">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">🔥 Sản phẩm bán chạy</h2>
      <a href="?case=products&sort=best_sell" class="btn btn-outline-primary btn-sm rounded-pill">Xem tất cả</a>
    </div>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-3">
      <?php foreach (array_slice($featured, 0, 6) as $p): ?>
        <?php include __DIR__ . '/../shared/product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── HÀNG MỚI VỀ ───────────────────────────────────────────── -->
<section class="py-4 pb-5">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="section-title mb-0">✨ Hàng mới về</h2>
      <a href="?case=products&sort=newest" class="btn btn-outline-primary btn-sm rounded-pill">Xem tất cả</a>
    </div>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-6 g-3">
      <?php foreach (array_slice($newArrive, 0, 6) as $p): ?>
        <?php include __DIR__ . '/../shared/product_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── WHY UPNEX ─────────────────────────────────────────────── -->
<section class="py-5" style="background:#fff">
  <div class="container">
    <h2 class="section-title text-center mb-2" style="text-align:center!important">Tại sao chọn UPNEX?</h2>
    <p class="text-muted text-center mb-5">Cam kết mang đến trải nghiệm mua sắm tốt nhất</p>
    <div class="row g-4 text-center">
      <?php
        $features = [
          ['bi-shield-check','text-primary','Hàng chính hãng 100%','Tất cả sản phẩm đều có tem chính hãng, hóa đơn VAT đầy đủ'],
          ['bi-truck','text-success','Giao hàng toàn quốc','Giao nhanh 2h nội thành, 1-3 ngày tỉnh thành khác'],
          ['bi-arrow-counterclockwise','text-warning','Đổi trả 30 ngày','Không hài lòng hoàn tiền 100%, không hỏi lý do'],
          ['bi-headset','text-danger','Hỗ trợ 24/7','Đội ngũ tư vấn luôn sẵn sàng hỗ trợ bạn mọi lúc'],
        ];
      ?>
      <?php foreach ($features as [$icon, $color, $title, $desc]): ?>
        <div class="col-md-3 col-6">
          <div class="p-3">
            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width:64px;height:64px">
              <i class="bi <?= $icon ?> <?= $color ?> fs-3"></i>
            </div>
            <h6 class="fw-bold"><?= $title ?></h6>
            <p class="text-muted small mb-0"><?= $desc ?></p>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
