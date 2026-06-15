<?php $pageTitle = 'Sản phẩm'; ?>

<div class="container py-4">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?case=home" class="text-decoration-none">Trang chủ</a></li>
      <li class="breadcrumb-item active">Sản phẩm</li>
    </ol>
  </nav>

  <div class="row g-4">

    <!-- ── SIDEBAR LỌC ──────────────────────────────────────── -->
    <div class="col-lg-3 d-none d-lg-block">
      <div class="filter-card">
        <form method="GET" action="" id="filter-form">
          <input type="hidden" name="case" value="products">
          <?php if (!empty($filters['keyword'])): ?>
            <input type="hidden" name="q" value="<?= htmlspecialchars($filters['keyword']) ?>">
          <?php endif; ?>

          <!-- Danh mục -->
          <div class="mb-4">
            <div class="filter-title"><i class="bi bi-grid me-2 text-primary"></i>Danh mục</div>
            <?php foreach ($categories as $cat): ?>
              <?php if ($cat['parent_id'] === null): ?>
                <div class="filter-item <?= ($filters['category_id'] == $cat['id']) ? 'active' : '' ?>"
                     onclick="setFilter('category_id', <?= $cat['id'] ?>)">
                  <i class="bi bi-chevron-right small me-1"></i><?= htmlspecialchars($cat['name']) ?>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
            <input type="hidden" name="cat" id="cat-input" value="<?= (int)$filters['category_id'] ?>">
          </div>

          <!-- Thương hiệu -->
          <div class="mb-4">
            <div class="filter-title"><i class="bi bi-building me-2 text-primary"></i>Thương hiệu</div>
            <?php foreach (['Apple','Samsung','Xiaomi','OPPO','Vivo','Dell','HP','Lenovo','Asus'] as $brand): ?>
              <div class="filter-item <?= ($filters['brand'] === $brand) ? 'active' : '' ?>"
                   onclick="setFilter('brand', '<?= $brand ?>')">
                <i class="bi bi-chevron-right small me-1"></i><?= $brand ?>
              </div>
            <?php endforeach; ?>
            <input type="hidden" name="brand" id="brand-input" value="<?= htmlspecialchars($filters['brand']) ?>">
          </div>

          <!-- Khoảng giá -->
          <div class="mb-4">
            <div class="filter-title"><i class="bi bi-currency-exchange me-2 text-primary"></i>Khoảng giá</div>
            <div class="d-flex gap-2 price-range">
              <input type="number" name="pmin" class="form-control form-control-sm"
                     placeholder="Từ" value="<?= $filters['price_min'] ?: '' ?>">
              <input type="number" name="pmax" class="form-control form-control-sm"
                     placeholder="Đến" value="<?= $filters['price_max'] ?: '' ?>">
            </div>
            <!-- Quick price ranges -->
            <div class="mt-2 d-flex flex-column gap-1">
              <?php
                $priceRanges = [
                  ['Dưới 5 triệu', 0, 5000000],
                  ['5 – 10 triệu',  5000000, 10000000],
                  ['10 – 20 triệu', 10000000, 20000000],
                  ['Trên 20 triệu', 20000000, 0],
                ];
              ?>
              <?php foreach ($priceRanges as [$label, $min, $max]): ?>
                <button type="button" class="btn btn-outline-secondary btn-sm text-start"
                        onclick="setPriceRange(<?= $min ?>, <?= $max ?>)">
                  <?= $label ?>
                </button>
              <?php endforeach; ?>
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-100 rounded-pill">
            <i class="bi bi-funnel me-1"></i>Áp dụng lọc
          </button>
          <a href="?case=products" class="btn btn-outline-secondary w-100 rounded-pill mt-2">
            <i class="bi bi-x-circle me-1"></i>Xóa bộ lọc
          </a>
        </form>
      </div>
    </div>

    <!-- ── DANH SÁCH SẢN PHẨM ───────────────────────────────── -->
    <div class="col-lg-9">

      <!-- Thanh sort + kết quả -->
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 bg-white rounded-card p-3 shadow-card">
        <div class="text-muted small">
          Tìm thấy <strong class="text-dark"><?= $total ?></strong> sản phẩm
          <?php if (!empty($filters['keyword'])): ?>
            cho từ khóa "<strong><?= htmlspecialchars($filters['keyword']) ?></strong>"
          <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="text-muted small">Sắp xếp:</span>
          <div class="btn-group btn-group-sm">
            <?php
              $sorts = ['newest'=>'Mới nhất','best_sell'=>'Bán chạy','price_asc'=>'Giá tăng','price_desc'=>'Giá giảm'];
            ?>
            <?php foreach ($sorts as $val => $label): ?>
              <a href="?case=products&<?= http_build_query(array_merge(
                   array_filter(['q'=>$filters['keyword'],'cat'=>$filters['category_id'],
                                 'brand'=>$filters['brand'],'pmin'=>$filters['price_min'],'pmax'=>$filters['price_max']]),
                   ['sort'=>$val])) ?>"
                 class="btn btn-outline-primary <?= ($filters['sort'] ?? 'newest') === $val ? 'active' : '' ?>">
                <?= $label ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Grid sản phẩm -->
      <?php if (empty($products)): ?>
        <div class="text-center py-5 bg-white rounded-card shadow-card">
          <i class="bi bi-search text-muted" style="font-size:3rem"></i>
          <h5 class="mt-3 text-muted">Không tìm thấy sản phẩm nào</h5>
          <p class="text-muted small">Thử thay đổi bộ lọc hoặc tìm từ khóa khác</p>
          <a href="?case=products" class="btn btn-primary rounded-pill mt-2">Xem tất cả sản phẩm</a>
        </div>
      <?php else: ?>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-3 row-cols-xl-4 g-3" id="product-grid">
          <?php foreach ($products as $p): ?>
            <?php include __DIR__ . '/../shared/product_card.php'; ?>
          <?php endforeach; ?>
        </div>

        <!-- Phân trang -->
        <?php if ($total_pages > 1): ?>
          <nav class="mt-4" aria-label="Phân trang">
            <ul class="pagination justify-content-center">
              <?php if ($current > 1): ?>
                <li class="page-item">
                  <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current - 1])) ?>">
                    <i class="bi bi-chevron-left"></i>
                  </a>
                </li>
              <?php endif; ?>

              <?php
                $start = max(1, $current - 2);
                $end   = min($total_pages, $current + 2);
              ?>
              <?php if ($start > 1): ?>
                <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>1])) ?>">1</a></li>
                <?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
              <?php endif; ?>

              <?php for ($i = $start; $i <= $end; $i++): ?>
                <li class="page-item <?= $i === $current ? 'active' : '' ?>">
                  <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
              <?php endfor; ?>

              <?php if ($end < $total_pages): ?>
                <?php if ($end < $total_pages - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$total_pages])) ?>"><?= $total_pages ?></a></li>
              <?php endif; ?>

              <?php if ($current < $total_pages): ?>
                <li class="page-item">
                  <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $current + 1])) ?>">
                    <i class="bi bi-chevron-right"></i>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function setFilter(name, value) {
  document.getElementById(name + '-input').value = value;
  document.getElementById('filter-form').submit();
}
function setPriceRange(min, max) {
  const form = document.getElementById('filter-form');
  form.querySelector('[name="pmin"]').value = min || '';
  form.querySelector('[name="pmax"]').value = max || '';
  form.submit();
}
</script>
