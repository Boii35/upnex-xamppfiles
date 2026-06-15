<?php $pageTitle = 'Quản lý khách hàng'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Quản lý khách hàng</h5>
  <span class="badge bg-primary rounded-pill"><?= number_format($total) ?> khách</span>
</div>

<!-- Thống kê tier -->
<div class="row g-3 mb-4">
  <?php
    $tierStats = [
      ['Silver',  'tier-silver',  'bi-award',       '#adb5bd'],
      ['Gold',    'tier-gold',    'bi-star-fill',   '#ffc107'],
      ['Diamond', 'tier-diamond', 'bi-gem',         '#0dcaf0'],
    ];
  ?>
  <?php foreach ($tierStats as [$tier, $cls, $icon, $color]): ?>
    <div class="col-md-4">
      <div class="stat-card" style="border-left-color:<?= $color ?>">
        <div class="stat-icon" style="background:<?= $color ?>22">
          <i class="bi <?= $icon ?>" style="color:<?= $color ?>"></i>
        </div>
        <div>
          <div class="stat-value">
            <?php
              $cnt = count(array_filter($users, fn($u) => $u['tier'] === $tier));
              echo $cnt;
            ?>
          </div>
          <div class="stat-label">Khách <?= $tier ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="bg-white rounded-card shadow-card">
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Khách hàng</th>
          <th>Số điện thoại</th>
          <th>Hạng thành viên</th>
          <th>Tổng chi tiêu</th>
          <th>Ngày đăng ký</th>
          <th>Trạng thái</th>
          <th class="text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">Chưa có khách hàng nào.</td></tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
          <tr>
            <td><span class="text-muted small"><?= $u['id'] ?></span></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width:34px;height:34px;font-size:.8rem;font-weight:700">
                  <?= mb_strtoupper(mb_substr($u['name'],0,1)) ?>
                </div>
                <div>
                  <div class="small fw-semibold"><?= htmlspecialchars($u['name']) ?></div>
                  <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars($u['email']) ?></div>
                </div>
              </div>
            </td>
            <td><span class="small"><?= htmlspecialchars($u['phone'] ?? '—') ?></span></td>
            <td>
              <?php
                $tierClass = match($u['tier']) {
                  'Diamond' => 'tier-diamond',
                  'Gold'    => 'tier-gold',
                  default   => 'tier-silver',
                };
                $tierIcon = match($u['tier']) {
                  'Diamond' => 'bi-gem',
                  'Gold'    => 'bi-star-fill',
                  default   => 'bi-award',
                };
              ?>
              <span class="badge <?= $tierClass ?> d-flex align-items-center gap-1" style="width:fit-content">
                <i class="bi <?= $tierIcon ?>"></i><?= $u['tier'] ?>
              </span>
            </td>
            <td>
              <span class="small fw-semibold text-danger">
                <?= number_format($u['total_spent']) ?>đ
              </span>
            </td>
            <td><span class="small text-muted"><?= date('d/m/Y', strtotime($u['created_at'])) ?></span></td>
            <td>
              <span class="badge <?= $u['is_locked'] ? 'bg-danger' : 'bg-success' ?>">
                <?= $u['is_locked'] ? 'Đã khóa' : 'Hoạt động' ?>
              </span>
            </td>
            <td class="text-center">
              <button class="btn btn-sm rounded-pill <?= $u['is_locked'] ? 'btn-outline-success' : 'btn-outline-danger' ?>"
                      data-locked="<?= $u['is_locked'] ?>"
                      onclick="UPNEX.adminToggleUser(<?= $u['id'] ?>, this)">
                <?= $u['is_locked'] ? 'Mở khóa' : 'Khóa' ?>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Phân trang -->
<?php if (ceil($total / 20) > 1): ?>
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= ceil($total/20); $i++): ?>
        <li class="page-item <?= $i === $page ? 'active':'' ?>">
          <a class="page-link" href="?case=admin_users&page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>
