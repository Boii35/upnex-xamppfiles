<?php $pageTitle = 'Quản lý Voucher'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"><i class="bi bi-ticket-perforated me-2 text-primary"></i>Quản lý Voucher</h5>
  <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addVoucherModal">
    <i class="bi bi-plus-lg me-1"></i>Tạo voucher
  </button>
</div>

<?php if (!empty($_GET['added'])): ?>
  <div class="alert alert-success small"><i class="bi bi-check-circle me-1"></i>Tạo voucher thành công!</div>
<?php endif; ?>
<?php if (!empty($_GET['error'])): ?>
  <div class="alert alert-danger small"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="bg-white rounded-card shadow-card">
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>Mã voucher</th>
          <th>Loại giảm</th>
          <th>Giá trị</th>
          <th>Đơn tối thiểu</th>
          <th>Đã dùng / Tổng</th>
          <th>Hết hạn</th>
          <th>Trạng thái</th>
          <th class="text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($vouchers)): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">Chưa có voucher nào.</td></tr>
        <?php endif; ?>
        <?php foreach ($vouchers as $v): ?>
          <?php $expired = strtotime($v['expires_at']) < time(); ?>
          <tr>
            <td>
              <span class="badge bg-dark fs-6 fw-bold font-monospace"><?= htmlspecialchars($v['code']) ?></span>
            </td>
            <td>
              <span class="badge <?= $v['discount_type']==='percent'?'bg-info text-dark':'bg-warning text-dark' ?>">
                <?= $v['discount_type']==='percent' ? 'Phần trăm' : 'Số tiền cố định' ?>
              </span>
            </td>
            <td>
              <span class="fw-bold text-danger">
                <?= $v['discount_type']==='percent'
                    ? $v['discount_value'].'%'
                    : number_format($v['discount_value']).'đ' ?>
              </span>
            </td>
            <td><span class="small"><?= number_format($v['min_order_value']) ?>đ</span></td>
            <td>
              <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height:6px;min-width:60px">
                  <div class="progress-bar <?= $v['used_count']>=$v['max_uses']?'bg-danger':'bg-success' ?>"
                       style="width:<?= $v['max_uses']>0 ? min(100,round($v['used_count']/$v['max_uses']*100)) : 0 ?>%">
                  </div>
                </div>
                <span class="small text-muted"><?= $v['used_count'] ?>/<?= $v['max_uses'] ?></span>
              </div>
            </td>
            <td>
              <span class="small <?= $expired?'text-danger':'text-muted' ?>">
                <?= date('d/m/Y H:i', strtotime($v['expires_at'])) ?>
              </span>
            </td>
            <td>
              <?php if ($expired): ?>
                <span class="badge bg-secondary">Hết hạn</span>
              <?php elseif (!$v['is_active']): ?>
                <span class="badge bg-danger">Tắt</span>
              <?php elseif ($v['used_count'] >= $v['max_uses']): ?>
                <span class="badge bg-warning text-dark">Hết lượt</span>
              <?php else: ?>
                <span class="badge bg-success">Đang hoạt động</span>
              <?php endif; ?>
            </td>
            <td class="text-center">
              <button class="btn btn-sm btn-outline-danger rounded-pill"
                      onclick="deleteVoucher(<?= $v['id'] ?>, this)">
                <i class="bi bi-trash"></i>
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal thêm voucher -->
<div class="modal fade" id="addVoucherModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold"><i class="bi bi-ticket-perforated me-2"></i>Tạo voucher mới</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="?case=admin_voucher_add">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label small fw-semibold">Mã voucher <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="text" name="code" class="form-control text-uppercase font-monospace"
                       placeholder="VD: UPNEX10" required maxlength="50">
                <button type="button" class="btn btn-outline-secondary" onclick="genCode()">Tạo ngẫu nhiên</button>
              </div>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Loại giảm</label>
              <select name="discount_type" class="form-select" id="discount-type-sel" onchange="updatePlaceholder()">
                <option value="percent">Phần trăm (%)</option>
                <option value="fixed">Số tiền cố định (đ)</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Giá trị giảm <span class="text-danger">*</span></label>
              <input type="number" name="discount_value" id="discount-value-input"
                     class="form-control" placeholder="VD: 10" min="1" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Đơn tối thiểu (đ)</label>
              <input type="number" name="min_order_value" class="form-control" placeholder="0" min="0" value="0">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Số lượt dùng</label>
              <input type="number" name="max_uses" class="form-control" placeholder="100" min="1" value="100">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Ngày hết hạn <span class="text-danger">*</span></label>
              <input type="datetime-local" name="expires_at" class="form-control" required
                     min="<?= date('Y-m-d\TH:i') ?>">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4">
            <i class="bi bi-plus-lg me-1"></i>Tạo voucher
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
async function deleteVoucher(id, btn) {
  if (!confirm('Xóa voucher này?')) return;
  const fd = new FormData();
  fd.append('csrf_token', getCsrfToken());
  fd.append('id', id);
  const res  = await fetch('?case=admin_voucher_delete', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.success) { btn.closest('tr').remove(); adminShowToast('Đã xóa voucher.','success'); }
}

function genCode() {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  let code    = 'UPNEX';
  for (let i = 0; i < 5; i++) code += chars[Math.floor(Math.random()*chars.length)];
  document.querySelector('[name="code"]').value = code;
}

function updatePlaceholder() {
  const type = document.getElementById('discount-type-sel').value;
  document.getElementById('discount-value-input').placeholder = type==='percent' ? 'VD: 10 (%)' : 'VD: 50000 (đ)';
}
</script>
