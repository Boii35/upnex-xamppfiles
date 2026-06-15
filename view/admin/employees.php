<?php $pageTitle = 'Quản lý nhân viên'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"><i class="bi bi-person-badge me-2 text-primary"></i>Quản lý nhân viên</h5>
  <a href="?case=admin_employee_add" class="btn btn-primary btn-sm rounded-pill px-3">
    <i class="bi bi-plus-lg me-1"></i>Thêm nhân viên
  </a>
</div>

<?php if (!empty($_GET['added'])): ?>
  <div class="alert alert-success small"><i class="bi bi-check-circle me-1"></i>Thêm nhân viên thành công!</div>
<?php elseif (!empty($_GET['error'])): ?>
  <div class="alert alert-danger small"><i class="bi bi-exclamation-circle me-1"></i>Có lỗi xảy ra, vui lòng thử lại.</div>
<?php endif; ?>

<div class="bg-white rounded-card shadow-card">
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Nhân viên</th>
          <th>Số điện thoại</th>
          <th>Chức vụ</th>
          <th>Ngày tạo</th>
          <th>Trạng thái</th>
          <th class="text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($employees)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Chưa có nhân viên nào.</td></tr>
        <?php endif; ?>
        <?php foreach ($employees as $emp): ?>
          <?php $isSelf = ($emp['id'] === $_SESSION[SESSION_ADMIN]['id']); ?>
          <tr>
            <td><span class="text-muted small"><?= $emp['id'] ?></span></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0
                            <?= $isSelf ? 'bg-primary' : 'bg-secondary' ?> text-white"
                     style="width:34px;height:34px;font-size:.8rem;font-weight:700">
                  <?= mb_strtoupper(mb_substr($emp['name'],0,1)) ?>
                </div>
                <div>
                  <div class="small fw-semibold">
                    <?= htmlspecialchars($emp['name']) ?>
                    <?php if ($isSelf): ?>
                      <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1" style="font-size:.65rem">Bạn</span>
                    <?php endif; ?>
                  </div>
                  <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($emp['email']) ?></div>
                </div>
              </div>
            </td>
            <td><span class="small"><?= htmlspecialchars($emp['phone'] ?? '—') ?></span></td>
            <td>
              <span class="badge bg-light text-dark border small">
                <?= htmlspecialchars($emp['position'] ?? 'Nhân viên') ?>
              </span>
            </td>
            <td><span class="small text-muted"><?= date('d/m/Y', strtotime($emp['created_at'])) ?></span></td>
            <td>
              <span class="badge <?= $emp['is_locked'] ? 'bg-danger' : 'bg-success' ?>">
                <?= $emp['is_locked'] ? 'Đã khóa' : 'Hoạt động' ?>
              </span>
            </td>
            <td class="text-center">
              <?php if (!$isSelf): ?>
                <button class="btn btn-sm rounded-pill <?= $emp['is_locked'] ? 'btn-outline-success' : 'btn-outline-danger' ?>"
                        onclick="toggleEmployee(<?= $emp['id'] ?>, this)"
                        data-locked="<?= $emp['is_locked'] ?>">
                  <i class="bi <?= $emp['is_locked'] ? 'bi-unlock' : 'bi-lock' ?> me-1"></i>
                  <?= $emp['is_locked'] ? 'Mở khóa' : 'Khóa' ?>
                </button>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
async function toggleEmployee(id, btn) {
  if (!confirm('Xác nhận thay đổi trạng thái nhân viên này?')) return;
  const fd = new FormData();
  fd.append('csrf_token', getCsrfToken());
  fd.append('id', id);
  const res  = await fetch('?case=admin_employee_lock', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) {
    const locked = btn.dataset.locked === '1';
    btn.dataset.locked = locked ? '0' : '1';
    btn.className = 'btn btn-sm rounded-pill ' + (locked ? 'btn-outline-danger' : 'btn-outline-success');
    btn.innerHTML = locked
      ? '<i class="bi bi-lock me-1"></i>Khóa'
      : '<i class="bi bi-unlock me-1"></i>Mở khóa';
    // Cập nhật badge trạng thái
    const row   = btn.closest('tr');
    const badge = row.querySelector('.badge.bg-success, .badge.bg-danger');
    if (badge) {
      badge.textContent = locked ? 'Hoạt động' : 'Đã khóa';
      badge.className   = 'badge ' + (locked ? 'bg-success' : 'bg-danger');
    }
    adminShowToast(locked ? 'Đã mở khóa nhân viên.' : 'Đã khóa nhân viên.', 'info');
  }
}
</script>
