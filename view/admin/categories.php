<?php $pageTitle = 'Quản lý danh mục'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"><i class="bi bi-grid me-2 text-primary"></i>Quản lý danh mục</h5>
  <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addCatModal">
    <i class="bi bi-plus-lg me-1"></i>Thêm danh mục
  </button>
</div>

<?php if (!empty($_GET['added'])): ?>
  <div class="alert alert-success small"><i class="bi bi-check-circle me-1"></i>Thêm danh mục thành công!</div>
<?php endif; ?>

<div class="bg-white rounded-card shadow-card">
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Tên danh mục</th>
          <th>Slug</th>
          <th>Loại</th>
          <th>Thứ tự</th>
          <th class="text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($categories)): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Chưa có danh mục nào.</td></tr>
        <?php endif; ?>

        <?php
          // Nhóm: cha trước, con thụt lề vào sau
          $parents  = array_filter($categories, fn($c) => $c['parent_id'] === null);
          $children = array_filter($categories, fn($c) => $c['parent_id'] !== null);
        ?>

        <?php foreach ($parents as $cat): ?>
          <tr>
            <td><span class="text-muted small"><?= $cat['id'] ?></span></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="rounded" style="width:8px;height:8px;background:var(--upnex-primary)"></div>
                <span class="fw-semibold"><?= htmlspecialchars($cat['name']) ?></span>
              </div>
            </td>
            <td><code class="small text-muted"><?= htmlspecialchars($cat['slug']) ?></code></td>
            <td><span class="badge bg-primary-subtle text-primary border border-primary-subtle small">Danh mục cha</span></td>
            <td><span class="small text-muted"><?= $cat['sort_order'] ?></span></td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-danger rounded-pill"
                      onclick="deleteCat(<?= $cat['id'] ?>, this)"
                      title="Xóa danh mục">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>

          <?php foreach ($children as $child): ?>
            <?php if ($child['parent_id'] == $cat['id']): ?>
              <tr style="background:#fafbff">
                <td><span class="text-muted small"><?= $child['id'] ?></span></td>
                <td>
                  <div class="d-flex align-items-center gap-2 ps-3">
                    <i class="bi bi-arrow-return-right text-muted small"></i>
                    <span class="small"><?= htmlspecialchars($child['name']) ?></span>
                  </div>
                </td>
                <td><code class="small text-muted"><?= htmlspecialchars($child['slug']) ?></code></td>
                <td><span class="badge bg-secondary-subtle text-secondary border small">Danh mục con</span></td>
                <td><span class="small text-muted"><?= $child['sort_order'] ?></span></td>
                <td class="text-center">
                  <button class="btn btn-sm btn-outline-danger rounded-pill"
                          onclick="deleteCat(<?= $child['id'] ?>, this)">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal thêm danh mục -->
<div class="modal fade" id="addCatModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold"><i class="bi bi-grid me-2"></i>Thêm danh mục</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="?case=admin_category_add">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Tên danh mục <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control form-control-sm"
                   placeholder="VD: Gaming Laptop" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Danh mục cha</label>
            <select name="parent_id" class="form-select form-select-sm">
              <option value="">-- Không có (danh mục gốc) --</option>
              <?php foreach ($parents as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-1">
            <label class="form-label small fw-semibold">Thứ tự hiển thị</label>
            <input type="number" name="sort_order" class="form-control form-control-sm"
                   value="0" min="0" placeholder="0">
            <div class="form-text" style="font-size:.72rem">Số nhỏ hơn hiển thị trước.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
            <i class="bi bi-plus-lg me-1"></i>Thêm
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
async function deleteCat(id, btn) {
  if (!confirm('Xóa danh mục này? Các sản phẩm thuộc danh mục sẽ không bị xóa.')) return;
  const fd = new FormData();
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
  fd.append('id', id);
  const res  = await fetch('?case=admin_category_delete', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    btn.closest('tr').remove();
    UPNEX.showToast('Đã xóa danh mục.', 'success');
  } else {
    UPNEX.showToast('Không thể xóa danh mục này.', 'danger');
  }
}
</script>
