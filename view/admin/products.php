<?php $pageTitle = 'Quản lý sản phẩm'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"><i class="bi bi-box-seam me-2 text-primary"></i>Quản lý sản phẩm</h5>
  <a href="?case=admin_product_add" class="btn btn-primary rounded-pill btn-sm px-3">
    <i class="bi bi-plus-lg me-1"></i>Thêm sản phẩm
  </a>
</div>

<?php if (!empty($_GET['added'])): ?>
  <div class="alert alert-success small"><i class="bi bi-check-circle me-1"></i>Thêm sản phẩm thành công!</div>
<?php elseif (!empty($_GET['updated'])): ?>
  <div class="alert alert-success small"><i class="bi bi-check-circle me-1"></i>Cập nhật thành công!</div>
<?php endif; ?>

<!-- Search -->
<form method="GET" class="mb-3 d-flex gap-2">
  <input type="hidden" name="case" value="admin_products">
  <input type="text" name="q" class="form-control form-control-sm" style="max-width:280px"
         placeholder="Tìm sản phẩm..." value="<?= htmlspecialchars($keyword) ?>">
  <button class="btn btn-outline-primary btn-sm rounded-pill px-3"><i class="bi bi-search me-1"></i>Tìm</button>
  <?php if ($keyword): ?>
    <a href="?case=admin_products" class="btn btn-outline-secondary btn-sm rounded-pill">Xóa lọc</a>
  <?php endif; ?>
</form>

<div class="bg-white rounded-card shadow-card">
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th style="width:60px">Ảnh</th>
          <th>Tên sản phẩm</th>
          <th>Danh mục</th>
          <th>Giá</th>
          <th>Tồn kho</th>
          <th>Trạng thái</th>
          <th class="text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Không có sản phẩm nào.</td></tr>
        <?php endif; ?>
        <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <img src="<?= $p['main_image'] ? '/upnex/uploads/products/'.htmlspecialchars($p['main_image']) : '/upnex/public/images/placeholder.png' ?>"
                   alt="" style="width:48px;height:48px;object-fit:contain;background:#f8f9fa;border-radius:6px;padding:3px">
            </td>
            <td>
              <div class="small fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
              <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($p['brand'] ?? '') ?></div>
            </td>
            <td><span class="badge bg-light text-dark border small"><?= htmlspecialchars($p['category_name'] ?? '') ?></span></td>
            <td>
              <div class="small fw-bold text-danger"><?= number_format($p['sale_price'] ?? $p['price']) ?>đ</div>
              <?php if ($p['sale_price']): ?>
                <div class="text-muted text-decoration-line-through" style="font-size:.72rem"><?= number_format($p['price']) ?>đ</div>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge <?= $p['stock'] > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> border">
                <?= $p['stock'] ?>
              </span>
            </td>
            <td>
              <span class="badge <?= $p['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                <?= $p['is_active'] ? 'Đang bán' : 'Ẩn' ?>
              </span>
            </td>
            <td class="text-center">
              <div class="d-flex gap-1 justify-content-center">
                <a href="?case=admin_product_edit&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="Sửa">
                  <i class="bi bi-pencil"></i>
                </a>
                <button class="btn btn-sm btn-outline-danger rounded-pill" title="Xóa"
                        onclick="deleteProduct(<?= $p['id'] ?>, this)">
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Phân trang -->
<?php if ($total_pages > 1): ?>
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= $i === $current ? 'active' : '' ?>">
          <a class="page-link" href="?case=admin_products&q=<?= urlencode($keyword) ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<script>
async function deleteProduct(id, btn) {
  if (!confirm('Ẩn sản phẩm này?')) return;
  const fd = new FormData();
  fd.append('csrf_token', getCsrfToken());
  fd.append('id', id);
  const res  = await fetch('?case=admin_product_delete', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    btn.closest('tr').remove();
    adminShowToast('Đã ẩn sản phẩm.', 'success');
  }
}
</script>
