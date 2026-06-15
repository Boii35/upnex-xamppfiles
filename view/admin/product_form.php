<?php $pageTitle = $product ? 'Sửa sản phẩm' : 'Thêm sản phẩm'; ?>

<div class="d-flex align-items-center gap-3 mb-4">
  <a href="?case=admin_products" class="btn btn-outline-secondary btn-sm rounded-pill">
    <i class="bi bi-arrow-left me-1"></i>Quay lại
  </a>
  <h5 class="fw-bold mb-0"><?= $pageTitle ?></h5>
</div>

<form method="POST"
      action="?case=<?= $product ? 'admin_product_edit&id='.$product['id'] : 'admin_product_add' ?>"
      enctype="multipart/form-data" id="product-form" novalidate>
  <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="bg-white rounded-card shadow-card p-4 mb-4">
        <h6 class="fw-semibold mb-3">Thông tin cơ bản</h6>

        <div class="mb-3">
          <label class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid':'' ?>"
                 value="<?= htmlspecialchars($old['name'] ?? '') ?>" required placeholder="VD: iPhone 16 Pro Max 256GB">
          <?php if (isset($errors['name'])): ?><div class="invalid-feedback"><?= $errors['name'] ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label">Mô tả sản phẩm</label>
          <textarea name="description" class="form-control" rows="5"
                    placeholder="Mô tả chi tiết về sản phẩm..."><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
        </div>

        <!-- Thông số kỹ thuật -->
        <div class="mb-3">
          <label class="form-label">Thông số kỹ thuật</label>
          <div id="specs-wrap">
            <?php
              $specs = $product['specs'] ?? [['spec_key'=>'','spec_value'=>'']];
            ?>
            <?php foreach ($specs as $i => $spec): ?>
              <div class="d-flex gap-2 mb-2 spec-row">
                <input type="text" name="spec_key[]" class="form-control form-control-sm"
                       placeholder="Tên (VD: RAM)" value="<?= htmlspecialchars($spec['spec_key']) ?>">
                <input type="text" name="spec_value[]" class="form-control form-control-sm"
                       placeholder="Giá trị (VD: 8GB)" value="<?= htmlspecialchars($spec['spec_value']) ?>">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.spec-row').remove()">
                  <i class="bi bi-x"></i>
                </button>
              </div>
            <?php endforeach; ?>
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary mt-1 rounded-pill" onclick="addSpec()">
            <i class="bi bi-plus me-1"></i>Thêm thông số
          </button>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <!-- Giá & tồn kho -->
      <div class="bg-white rounded-card shadow-card p-4 mb-4">
        <h6 class="fw-semibold mb-3">Giá & Kho hàng</h6>

        <div class="mb-3">
          <label class="form-label">Giá gốc (đ) <span class="text-danger">*</span></label>
          <input type="number" name="price" class="form-control <?= isset($errors['price'])?'is-invalid':'' ?>"
                 value="<?= htmlspecialchars($old['price'] ?? '') ?>" min="0" required placeholder="VD: 25000000">
          <?php if (isset($errors['price'])): ?><div class="invalid-feedback"><?= $errors['price'] ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label">Giá khuyến mãi (đ)</label>
          <input type="number" name="sale_price" class="form-control <?= isset($errors['sale_price'])?'is-invalid':'' ?>"
                 value="<?= htmlspecialchars($old['sale_price'] ?? '') ?>" min="0" placeholder="Để trống nếu không giảm">
          <?php if (isset($errors['sale_price'])): ?><div class="invalid-feedback"><?= $errors['sale_price'] ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label">Tồn kho <span class="text-danger">*</span></label>
          <input type="number" name="stock" class="form-control <?= isset($errors['stock'])?'is-invalid':'' ?>"
                 value="<?= htmlspecialchars($old['stock'] ?? 0) ?>" min="0" required>
          <?php if (isset($errors['stock'])): ?><div class="invalid-feedback"><?= $errors['stock'] ?></div><?php endif; ?>
        </div>
      </div>

      <!-- Phân loại -->
      <div class="bg-white rounded-card shadow-card p-4 mb-4">
        <h6 class="fw-semibold mb-3">Phân loại</h6>

        <div class="mb-3">
          <label class="form-label">Danh mục <span class="text-danger">*</span></label>
          <select name="category_id" class="form-select <?= isset($errors['category_id'])?'is-invalid':'' ?>" required>
            <option value="">-- Chọn danh mục --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"
                      <?= ($old['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                <?= ($cat['parent_id'] ? '└ ' : '') . htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['category_id'])): ?><div class="invalid-feedback"><?= $errors['category_id'] ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label">Thương hiệu</label>
          <input type="text" name="brand" class="form-control"
                 value="<?= htmlspecialchars($old['brand'] ?? '') ?>" placeholder="VD: Apple, Samsung...">
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1"
                   <?= !empty($old['is_featured']) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="is_featured">Sản phẩm nổi bật (HOT)</label>
          </div>
        </div>

        <?php if ($product): ?>
          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                     <?= !empty($old['is_active']) ? 'checked' : '' ?>>
              <label class="form-check-label small" for="is_active">Hiển thị sản phẩm</label>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Upload ảnh -->
      <div class="bg-white rounded-card shadow-card p-4 mb-4">
        <h6 class="fw-semibold mb-3">Ảnh sản phẩm</h6>
        <?php if ($product && !empty($product['images'])): ?>
          <div class="d-flex flex-wrap gap-2 mb-3">
            <?php foreach ($product['images'] as $img): ?>
              <img src="/upnex/uploads/products/<?= htmlspecialchars($img['image_path']) ?>"
                   alt="" style="width:60px;height:60px;object-fit:contain;border-radius:6px;background:#f8f9fa;padding:3px;border:1px solid #dee2e6"
                   <?= $img['is_primary'] ? 'style="border-color:#0d6efd!important"' : '' ?>>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <input type="file" name="images[]" class="form-control form-control-sm" multiple accept="image/*" id="img-input">
        <div class="form-text">Tối đa 5MB/ảnh. JPG, PNG, WebP. Ảnh đầu tiên sẽ là ảnh chính.</div>
        <!-- Preview -->
        <div class="d-flex flex-wrap gap-2 mt-2" id="img-preview"></div>
      </div>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary rounded-pill py-2 fw-semibold">
          <i class="bi bi-check2 me-2"></i><?= $product ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm' ?>
        </button>
        <a href="?case=admin_products" class="btn btn-outline-secondary rounded-pill">Hủy</a>
      </div>
    </div>
  </div>
</form>

<script>
function addSpec() {
  const wrap = document.getElementById('specs-wrap');
  wrap.insertAdjacentHTML('beforeend', `
    <div class="d-flex gap-2 mb-2 spec-row">
      <input type="text" name="spec_key[]" class="form-control form-control-sm" placeholder="Tên (VD: RAM)">
      <input type="text" name="spec_value[]" class="form-control form-control-sm" placeholder="Giá trị (VD: 8GB)">
      <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.spec-row').remove()">
        <i class="bi bi-x"></i>
      </button>
    </div>`);
}

// Preview ảnh
document.getElementById('img-input')?.addEventListener('change', function() {
  const preview = document.getElementById('img-preview');
  preview.innerHTML = '';
  [...this.files].forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      preview.insertAdjacentHTML('beforeend',
        `<img src="${e.target.result}" style="width:60px;height:60px;object-fit:contain;border-radius:6px;background:#f8f9fa;padding:3px;border:1px solid #dee2e6">`
      );
    };
    reader.readAsDataURL(file);
  });
});
</script>
