<?php $pageTitle = 'Quản lý đánh giá'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"><i class="bi bi-star me-2 text-primary"></i>Quản lý đánh giá</h5>
  <span class="badge bg-primary rounded-pill"><?= count($reviews) ?> đánh giá</span>
</div>

<div class="bg-white rounded-card shadow-card">
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>Sản phẩm</th>
          <th>Khách hàng</th>
          <th class="text-center">Sao</th>
          <th>Nội dung</th>
          <th>Ngày</th>
          <th class="text-center">Hiển thị</th>
          <th class="text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($reviews)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Chưa có đánh giá nào.</td></tr>
        <?php endif; ?>
        <?php foreach ($reviews as $rv): ?>
          <tr id="review-row-<?= $rv['id'] ?>">
            <td>
              <a href="?case=product_detail&id=<?= $rv['product_id'] ?>" target="_blank"
                 class="small fw-semibold text-decoration-none text-primary text-truncate d-block" style="max-width:180px">
                <?= htmlspecialchars($rv['product_name']) ?>
              </a>
            </td>
            <td>
              <div class="small fw-semibold"><?= htmlspecialchars($rv['user_name']) ?></div>
            </td>
            <td class="text-center">
              <div class="d-flex justify-content-center gap-1">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="bi <?= $i <= $rv['rating'] ? 'bi-star-fill text-warning' : 'bi-star text-muted' ?>"
                     style="font-size:.75rem"></i>
                <?php endfor; ?>
              </div>
              <div class="small fw-bold"><?= $rv['rating'] ?>/5</div>
            </td>
            <td>
              <span class="small text-truncate d-block" style="max-width:220px">
                <?= $rv['comment'] ? htmlspecialchars($rv['comment']) : '<em class="text-muted">Không có nội dung</em>' ?>
              </span>
            </td>
            <td>
              <span class="small text-muted"><?= date('d/m/Y H:i', strtotime($rv['created_at'])) ?></span>
            </td>
            <td class="text-center">
              <span class="badge <?= $rv['is_visible'] ? 'bg-success' : 'bg-secondary' ?>"
                    id="review-badge-<?= $rv['id'] ?>">
                <?= $rv['is_visible'] ? 'Hiển thị' : 'Đã ẩn' ?>
              </span>
            </td>
            <td class="text-center">
              <button class="btn btn-sm rounded-pill <?= $rv['is_visible'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>"
                      data-visible="<?= $rv['is_visible'] ?>"
                      id="review-btn-<?= $rv['id'] ?>"
                      onclick="UPNEX.adminToggleReview(<?= $rv['id'] ?>, this)"
                      title="<?= $rv['is_visible'] ? 'Ẩn đánh giá' : 'Hiện đánh giá' ?>">
                <i class="bi <?= $rv['is_visible'] ? 'bi-eye-slash' : 'bi-eye' ?>"></i>
                <?= $rv['is_visible'] ? 'Ẩn' : 'Hiện' ?>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Override adminToggleReview để cập nhật UI sau khi toggle
const _origToggle = UPNEX.adminToggleReview.bind(UPNEX);
UPNEX.adminToggleReview = async function(id, btn) {
  const fd = new FormData();
  fd.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
  fd.append('id', id);
  const res  = await fetch('?case=admin_review_toggle', { method: 'POST', body: fd });
  const data = await res.json();
  if (!data.success) return;

  const visible = btn.dataset.visible === '1';
  const newVis  = !visible;
  btn.dataset.visible = newVis ? '1' : '0';

  // Cập nhật badge
  const badge = document.getElementById('review-badge-' + id);
  badge.textContent = newVis ? 'Hiển thị' : 'Đã ẩn';
  badge.className   = 'badge ' + (newVis ? 'bg-success' : 'bg-secondary');

  // Cập nhật nút
  btn.className = 'btn btn-sm rounded-pill ' + (newVis ? 'btn-outline-secondary' : 'btn-outline-success');
  btn.innerHTML = `<i class="bi ${newVis ? 'bi-eye-slash' : 'bi-eye'}"></i> ${newVis ? 'Ẩn' : 'Hiện'}`;

  UPNEX.showToast(newVis ? 'Đã hiện đánh giá.' : 'Đã ẩn đánh giá.', 'info');
};
</script>
