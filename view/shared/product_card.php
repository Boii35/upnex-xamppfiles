<?php
// product_card.php — partial dùng chung
// Yêu cầu biến $p là array sản phẩm từ ProductModel
$finalPrice    = $p['final_price']    ?? $p['price'];
$originalPrice = $p['sale_price']     ? $p['price'] : null;
$mainImage     = $p['main_image']     ?? '';
$avgRating     = $p['avg_rating']     ?? 0;
$salePercent   = ($originalPrice && $originalPrice > $finalPrice)
    ? round((1 - $finalPrice / $originalPrice) * 100) : 0;
?>
<div class="col">
  <div class="product-card">
    <?php if ($salePercent >= 5): ?>
      <span class="badge-sale">-<?= $salePercent ?>%</span>
    <?php elseif (!empty($p['is_featured'])): ?>
      <span class="badge-new">HOT</span>
    <?php endif; ?>

    <!-- Ảnh -->
    <a href="?case=product_detail&id=<?= $p['id'] ?>" class="d-block product-img-wrap">
      <img src="<?= $mainImage ? BASE_URL.'/uploads/products/'.htmlspecialchars($mainImage) : BASE_URL.'/public/images/placeholder.png' ?>"
           alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
    </a>

    <!-- Thông tin -->
    <div class="product-body">
      <?php if (!empty($p['brand'])): ?>
        <div class="product-brand"><?= htmlspecialchars($p['brand']) ?></div>
      <?php endif; ?>
      <a href="?case=product_detail&id=<?= $p['id'] ?>" class="text-decoration-none">
        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
      </a>
      <div class="product-price-wrap">
        <span class="price-final"><?= number_format($finalPrice) ?>đ</span>
        <?php if ($originalPrice): ?>
          <span class="price-original"><?= number_format($originalPrice) ?>đ</span>
        <?php endif; ?>
      </div>
      <?php if ($avgRating > 0): ?>
        <div class="product-rating">
          <span class="stars">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <i class="bi <?= $i <= round($avgRating) ? 'bi-star-fill' : 'bi-star' ?>"></i>
            <?php endfor; ?>
          </span>
          <span class="rating-count">(<?= number_format($avgRating, 1) ?>)</span>
        </div>
      <?php endif; ?>
    </div>

    <!-- Nút thao tác -->
    <div class="product-footer">
      <button class="btn-cart" onclick="UPNEX.addToCart(<?= $p['id'] ?>, 1, this)">
        <i class="bi bi-cart-plus me-1"></i>Thêm vào giỏ
      </button>
      <a href="?case=product_detail&id=<?= $p['id'] ?>" class="btn-detail" title="Xem chi tiết">
        <i class="bi bi-eye"></i>
      </a>
    </div>
  </div>
</div>
