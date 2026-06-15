<?php $pageTitle = 'Dashboard'; ?>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
  <?php
    $cards = [
      ['stat-card-1','bi-receipt text-primary','bg-primary bg-opacity-10',       'Tổng đơn hàng',  number_format($stats['total_orders'])],
      ['stat-card-2','bi-clock text-warning','bg-warning bg-opacity-10',          'Chờ xác nhận',   number_format($stats['pending_orders'])],
      ['stat-card-3','bi-people text-success','bg-success bg-opacity-10',         'Khách hàng',     number_format($stats['total_users'])],
      ['stat-card-4','bi-currency-exchange text-danger','bg-danger bg-opacity-10','Doanh thu',      number_format($stats['total_revenue']).'đ'],
      ['stat-card-5','bi-box-seam text-purple','bg-primary bg-opacity-10',        'Sản phẩm',       number_format($stats['total_products'])],
    ];
  ?>
  <?php foreach ($cards as [$cls, $icon, $iconBg, $label, $value]): ?>
    <div class="col-6 col-md-4 col-xl">
      <div class="stat-card <?= $cls ?>">
        <div class="stat-icon <?= $iconBg ?>">
          <i class="bi <?= $icon ?>"></i>
        </div>
        <div>
          <div class="stat-value"><?= $value ?></div>
          <div class="stat-label"><?= $label ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
  <!-- Biểu đồ doanh thu -->
  <div class="col-lg-8">
    <div class="bg-white rounded-card shadow-card p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Doanh thu theo tháng</h6>
        <a href="?case=admin_revenue" class="btn btn-sm btn-outline-primary rounded-pill">Xem chi tiết</a>
      </div>
      <canvas id="revenue-chart" height="90"></canvas>
    </div>
  </div>

  <!-- Top bán chạy -->
  <div class="col-lg-4">
    <div class="bg-white rounded-card shadow-card p-4">
      <h6 class="fw-bold mb-3">Top 5 bán chạy</h6>
      <?php foreach ($bestSellers as $idx => $p): ?>
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="badge bg-primary rounded-pill" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:.7rem">
            <?= $idx + 1 ?>
          </span>
          <div class="flex-grow-1 min-w-0">
            <div class="small fw-semibold text-truncate"><?= htmlspecialchars($p['name']) ?></div>
            <div class="text-muted" style="font-size:.75rem">Đã bán: <?= number_format($p['total_sold'] ?? $p['sold_count']) ?></div>
          </div>
          <div class="text-danger small fw-bold text-nowrap"><?= number_format($p['sale_price'] ?? $p['price']) ?>đ</div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Đơn hàng gần đây -->
<div class="bg-white rounded-card shadow-card p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold mb-0">Đơn hàng gần đây</h6>
    <a href="?case=admin_orders" class="btn btn-sm btn-outline-primary rounded-pill">Xem tất cả</a>
  </div>
  <div class="table-responsive">
    <table class="table admin-table mb-0">
      <thead>
        <tr>
          <th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th>
          <th>Thanh toán</th><th>Trạng thái</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php
          $statusMap = [
            'pending'   => ['Chờ xác nhận', 'status-pending'],
            'confirmed' => ['Đã xác nhận',  'status-confirmed'],
            'shipping'  => ['Đang giao',     'status-shipping'],
            'delivered' => ['Đã giao',       'status-delivered'],
            'completed' => ['Hoàn thành',    'status-completed'],
            'cancelled' => ['Đã hủy',        'status-cancelled'],
          ];
        ?>
        <?php foreach ($recentOrders as $o): ?>
          <?php [$sLabel, $sClass] = $statusMap[$o['status']] ?? ['?','status-pending']; ?>
          <tr>
            <td><span class="fw-semibold text-primary small"><?= htmlspecialchars($o['order_code']) ?></span></td>
            <td><span class="small"><?= htmlspecialchars($o['user_name']) ?></span></td>
            <td><span class="fw-bold text-danger small"><?= number_format($o['total']) ?>đ</span></td>
            <td><span class="badge bg-light text-dark border small"><?= strtoupper($o['payment_method']) ?></span></td>
            <td><span class="status-badge <?= $sClass ?>"><?= $sLabel ?></span></td>
            <td>
              <a href="?case=admin_orders&view=<?= $o['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
                <i class="bi bi-eye"></i>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Biểu đồ doanh thu Chart.js
const labels  = <?= json_encode(array_reverse(array_column($revenueStats, 'period'))) ?>;
const revenue = <?= json_encode(array_reverse(array_column($revenueStats, 'revenue'))) ?>;

new Chart(document.getElementById('revenue-chart'), {
  type: 'bar',
  data: {
    labels,
    datasets: [{
      label: 'Doanh thu (đ)',
      data: revenue,
      backgroundColor: 'rgba(13,110,253,.15)',
      borderColor: 'rgba(13,110,253,.8)',
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => parseInt(ctx.raw).toLocaleString('vi-VN') + 'đ'
        }
      }
    },
    scales: {
      y: {
        ticks: { callback: v => (v/1000000).toFixed(1) + 'tr', font: { size: 11 } },
        grid: { color: 'rgba(0,0,0,.04)' }
      },
      x: { grid: { display: false }, ticks: { font: { size: 11 } } }
    }
  }
});
</script>
