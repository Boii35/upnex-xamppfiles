<?php $pageTitle = htmlspecialchars($product['name']); ?>

<div class="container py-4">

  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="?case=home" class="text-decoration-none">Trang chủ</a></li>
      <li class="breadcrumb-item"><a href="?case=products&cat=<?= $product['category_id'] ?>" class="text-decoration-none"><?= htmlspecialchars($product['category_name']) ?></a></li>
      <li class="breadcrumb-item active text-truncate" style="max-width:240px"><?= htmlspecialchars($product['name']) ?></li>
    </ol>
  </nav>

  <!-- ── MAIN INFO ───────────────────────────────────────────── -->
  <div class="row g-4 mb-5">

    <!-- Gallery -->
    <div class="col-lg-5">
      <div class="product-detail-img-main mb-3" id="main-img-wrap">
        <?php $mainImg = $product['images'][0]['image_path'] ?? ''; ?>
        <img id="main-img"
             src="<?= $mainImg ? '/upnex/uploads/products/'.htmlspecialchars($mainImg) : '/upnex/public/images/placeholder.png' ?>"
             alt="<?= htmlspecialchars($product['name']) ?>">
      </div>
      <!-- Thumbnails -->
      <?php if (count($product['images']) > 1): ?>
        <div class="d-flex gap-2 flex-wrap">
          <?php foreach ($product['images'] as $idx => $img): ?>
            <div class="product-thumb <?= $idx === 0 ? 'active' : '' ?>"
                 onclick="switchImg(this, '/upnex/uploads/products/<?= htmlspecialchars($img['image_path']) ?>')">
              <img src="/upnex/uploads/products/<?= htmlspecialchars($img['image_path']) ?>"
                   alt="Ảnh <?= $idx + 1 ?>">
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Chi tiết -->
    <div class="col-lg-7">
      <?php if (!empty($product['brand'])): ?>
        <div class="text-muted small text-uppercase fw-semibold mb-1 text-primary"><?= htmlspecialchars($product['brand']) ?></div>
      <?php endif; ?>
      <h1 class="h3 fw-bold mb-2"><?= htmlspecialchars($product['name']) ?></h1>

      <!-- Rating -->
      <?php
        $avgRating = (float)($product['reviews'] ? array_sum(array_column($product['reviews'], 'rating')) / count($product['reviews']) : 0);
        $reviewCount = count($product['reviews']);
      ?>
      <div class="d-flex align-items-center gap-2 mb-3">
        <div class="stars">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="bi <?= $i <= round($avgRating) ? 'bi-star-fill' : 'bi-star' ?> text-warning"></i>
          <?php endfor; ?>
        </div>
        <span class="text-muted small"><?= number_format($avgRating, 1) ?> (<?= $reviewCount ?> đánh giá)</span>
        <span class="text-muted small">|</span>
        <span class="text-muted small">Đã bán: <strong><?= number_format($product['sold_count']) ?></strong></span>
      </div>

      <!-- Giá -->
      <div class="mb-3 p-3 rounded-3" style="background:#fff5f5">
        <div class="d-flex align-items-baseline gap-3 flex-wrap">
          <span class="product-price-big"><?= number_format($product['final_price']) ?>đ</span>
          <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
            <span class="product-price-old"><?= number_format($product['price']) ?>đ</span>
            <?php $disc = round((1 - $product['sale_price']/$product['price'])*100); ?>
            <span class="badge bg-danger">-<?= $disc ?>%</span>
          <?php endif; ?>
        </div>
        <?php if (!empty($currentUser)): ?>
          <?php if ($currentUser['tier'] === 'Diamond'): ?>
            <div class="small text-info mt-1"><i class="bi bi-gem me-1"></i>Diamond: Miễn phí vận chuyển + ưu tiên xử lý đơn</div>
          <?php elseif ($currentUser['tier'] === 'Gold'): ?>
            <div class="small text-warning mt-1"><i class="bi bi-star-fill me-1"></i>Gold: Giảm thêm 2% khi thanh toán online</div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <!-- Tồn kho -->
      <div class="mb-3">
        <?php if ($product['stock'] > 0): ?>
          <span class="badge bg-success-subtle text-success border border-success-subtle">
            <i class="bi bi-check-circle me-1"></i>Còn hàng (<?= $product['stock'] ?> sản phẩm)
          </span>
        <?php else: ?>
          <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
            <i class="bi bi-x-circle me-1"></i>Hết hàng
          </span>
        <?php endif; ?>
      </div>

      <!-- Số lượng + nút thêm giỏ -->
      <?php if ($product['stock'] > 0): ?>
        <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
          <div class="qty-control">
            <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
            <input type="number" id="qty-input" class="qty-input" value="1" min="1" max="<?= $product['stock'] ?>">
            <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
          </div>
          <button class="btn btn-primary btn-lg px-4 rounded-pill flex-grow-1"
                  id="btn-add-cart"
                  onclick="addToCartWithQty(<?= $product['id'] ?>, this)">
            <i class="bi bi-cart-plus me-2"></i>Thêm vào giỏ hàng
          </button>
          <a href="?case=checkout" class="btn btn-danger btn-lg px-4 rounded-pill flex-grow-1">
            <i class="bi bi-lightning-fill me-2"></i>Mua ngay
          </a>
        </div>
      <?php endif; ?>

      <!-- Cam kết -->
      <div class="row g-2 mb-3">
        <?php
          $commits = [
            ['bi-shield-check','text-success','Bảo hành chính hãng 12 tháng'],
            ['bi-truck','text-primary','Giao hàng toàn quốc 1-3 ngày'],
            ['bi-arrow-counterclockwise','text-warning','Đổi trả 30 ngày'],
            ['bi-credit-card','text-info','Thanh toán an toàn, bảo mật'],
          ];
        ?>
        <?php foreach ($commits as [$icon, $color, $text]): ?>
          <div class="col-6">
            <div class="d-flex align-items-center gap-2 small">
              <i class="bi <?= $icon ?> <?= $color ?>"></i>
              <span><?= $text ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ── TABS: Mô tả / Thông số / Đánh giá ────────────────────── -->
  <div class="bg-white rounded-card shadow-card mb-5">
    <ul class="nav nav-tabs px-3 pt-3 border-0" id="detail-tabs">
      <li class="nav-item">
        <button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-desc">
          Mô tả sản phẩm
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-specs">
          Thông số kỹ thuật
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#tab-reviews">
          Đánh giá (<?= $reviewCount ?>)
        </button>
      </li>
    </ul>

    <div class="tab-content p-4">
      <!-- Mô tả -->
      <div class="tab-pane fade show active" id="tab-desc">
        <?php if (!empty($product['description'])): ?>
          <div class="product-desc"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
        <?php else: ?>
          <p class="text-muted">Chưa có mô tả sản phẩm.</p>
        <?php endif; ?>
      </div>

      <!-- Thông số -->
      <div class="tab-pane fade" id="tab-specs">
        <?php if (!empty($product['specs'])): ?>
          <table class="table spec-table">
            <tbody>
              <?php foreach ($product['specs'] as $spec): ?>
                <tr>
                  <td><?= htmlspecialchars($spec['spec_key']) ?></td>
                  <td><?= htmlspecialchars($spec['spec_value']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <p class="text-muted">Chưa có thông số kỹ thuật.</p>
        <?php endif; ?>
      </div>

      <!-- Đánh giá -->
      <div class="tab-pane fade" id="tab-reviews">

        <!-- Form viết đánh giá -->
        <?php if (!empty($currentUser)): ?>
          <div class="card border-0 bg-light rounded-3 p-3 mb-4">
            <h6 class="fw-bold mb-3">Viết đánh giá của bạn</h6>
            <div class="mb-2">
              <label class="form-label small fw-semibold">Đánh giá sao:</label>
              <div class="star-picker d-flex gap-1" id="star-picker">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="bi bi-star fs-4 text-warning" style="cursor:pointer"
                     data-val="<?= $i ?>" onclick="pickStar(<?= $i ?>)"></i>
                <?php endfor; ?>
              </div>
              <input type="hidden" id="review-rating" value="5">
            </div>
            <textarea id="review-comment" class="form-control mb-2" rows="3"
                      placeholder="Nhận xét về sản phẩm..."></textarea>
            <button class="btn btn-primary btn-sm rounded-pill px-4"
                    onclick="submitReview(<?= $product['id'] ?>)">
              <i class="bi bi-send me-1"></i>Gửi đánh giá
            </button>
          </div>
        <?php else: ?>
          <div class="alert alert-info small">
            <a href="?case=login" class="fw-semibold">Đăng nhập</a> để viết đánh giá sản phẩm.
          </div>
        <?php endif; ?>

        <!-- Danh sách đánh giá -->
        <?php if (empty($product['reviews'])): ?>
          <p class="text-muted">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
        <?php else: ?>
          <div id="review-list">
            <?php foreach ($product['reviews'] as $rv): ?>
              <div class="d-flex gap-3 mb-3 pb-3 border-bottom">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:40px;height:40px;font-weight:700">
                  <?= mb_strtoupper(mb_substr($rv['user_name'], 0, 1)) ?>
                </div>
                <div class="flex-grow-1">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="fw-semibold small"><?= htmlspecialchars($rv['user_name']) ?></span>
                    <span class="stars small">
                      <?php for ($i=1;$i<=5;$i++): ?>
                        <i class="bi <?= $i<=$rv['rating']?'bi-star-fill':'bi-star' ?> text-warning"></i>
                      <?php endfor; ?>
                    </span>
                    <span class="text-muted small"><?= date('d/m/Y', strtotime($rv['created_at'])) ?></span>
                  </div>
                  <?php if ($rv['comment']): ?>
                    <p class="mb-0 small"><?= nl2br(htmlspecialchars($rv['comment'])) ?></p>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── SẢN PHẨM LIÊN QUAN ─────────────────────────────────── -->
  <?php if (!empty($related)): ?>
    <h2 class="section-title mb-4">Sản phẩm tương tự</h2>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-xl-5 g-3">
      <?php foreach (array_slice($related, 0, 5) as $p): ?>
        <?php if ($p['id'] !== $product['id']): ?>
          <?php include __DIR__ . '/../shared/product_card.php'; ?>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
// Chuyển ảnh thumbnail
function switchImg(thumb, src) {
  document.getElementById('main-img').src = src;
  document.querySelectorAll('.product-thumb').forEach(t => t.classList.remove('active'));
  thumb.classList.add('active');
}

// Thay đổi số lượng
function changeQty(delta) {
  const input = document.getElementById('qty-input');
  const max   = parseInt(input.max) || 99;
  input.value = Math.max(1, Math.min(max, parseInt(input.value) + delta));
}

// Thêm vào giỏ với số lượng
function addToCartWithQty(productId, btn) {
  const qty = parseInt(document.getElementById('qty-input').value) || 1;
  UPNEX.addToCart(productId, qty, btn);
}

// Chọn sao đánh giá
function pickStar(val) {
  document.getElementById('review-rating').value = val;
  document.querySelectorAll('#star-picker i').forEach((el, idx) => {
    el.className = idx < val ? 'bi bi-star-fill fs-4 text-warning' : 'bi bi-star fs-4 text-warning';
    el.style.cursor = 'pointer';
  });
}
// Init 5 sao
pickStar(5);

// Gửi đánh giá
async function submitReview(productId) {
  const rating  = document.getElementById('review-rating').value;
  const comment = document.getElementById('review-comment').value.trim();
  if (!comment) { UPNEX.showToast('Vui lòng nhập nội dung đánh giá.', 'warning'); return; }

  const fd = new FormData();
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
  fd.append('product_id', productId);
  fd.append('rating', rating);
  fd.append('comment', comment);

  const res  = await fetch('?case=add_review', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    UPNEX.showToast('Đánh giá đã được gửi!', 'success');
    setTimeout(() => location.reload(), 1200);
  } else {
    UPNEX.showToast(data.message || 'Không thể gửi đánh giá.', 'danger');
  }
}
</script>
