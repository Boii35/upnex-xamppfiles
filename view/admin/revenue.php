<?php $pageTitle = 'Báo cáo doanh thu'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h5 class="fw-bold mb-0"><i class="bi bi-bar-chart-line me-2 text-primary"></i>Báo cáo doanh thu</h5>
  <!-- Filter kỳ -->
  <div class="btn-group btn-group-sm">
    <?php foreach (['week'=>'Tuần','month'=>'Tháng','year'=>'Năm'] as $val => $label): ?>
      <a href="?case=admin_revenue&period=<?= $val ?>"
         class="btn btn-outline-primary <?= $period === $val ? 'active' : '' ?>">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<?php
  // Tổng doanh thu kỳ này
  $totalRevenue = array_sum(array_column($stats, 'revenue'));
  $totalOrders  = array_sum(array_column($stats, 'order_count'));
?>

<!-- Summary cards -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="stat-card stat-card-2">
      <div class="stat-icon bg-success bg-opacity-10">
        <i class="bi bi-currency-exchange text-success"></i>
      </div>
      <div>
        <div class="stat-value"><?= number_format($totalRevenue) ?>đ</div>
        <div class="stat-label">Tổng doanh thu (<?= match($period){'week'=>'tuần','year'=>'năm',default=>'12 tháng'} ?>)</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card stat-card-1">
      <div class="stat-icon bg-primary bg-opacity-10">
        <i class="bi bi-receipt text-primary"></i>
      </div>
      <div>
        <div class="stat-value"><?= number_format($totalOrders) ?></div>
        <div class="stat-label">Đơn hàng hoàn thành</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card stat-card-3">
      <div class="stat-icon bg-warning bg-opacity-10">
        <i class="bi bi-graph-up text-warning"></i>
      </div>
      <div>
        <div class="stat-value">
          <?= $totalOrders > 0 ? number_format($totalRevenue / $totalOrders) : 0 ?>đ
        </div>
        <div class="stat-label">Giá trị đơn trung bình</div>
      </div>
    </div>
  </div>
</div>

<!-- Biểu đồ doanh thu -->
<div class="bg-white rounded-card shadow-card p-4 mb-4">
  <h6 class="fw-bold mb-4">
    Biểu đồ doanh thu theo
    <?= match($period){'week'=>'tuần','year'=>'năm',default=>'tháng'} ?>
  </h6>
  <canvas id="revenue-chart" height="70"></canvas>
</div>

<div class="row g-4">
  <!-- Top bán chạy -->
  <div class="col-lg-6">
    <div class="bg-white rounded-card shadow-card p-4">
      <h6 class="fw-bold mb-3">
        <i class="bi bi-fire text-danger me-2"></i>Top 10 sản phẩm bán chạy
      </h6>
      <?php if (empty($bestSellers)): ?>
        <p class="text-muted small">Chưa có dữ liệu.</p>
      <?php else: ?>
        <?php foreach ($bestSellers as $idx => $p): ?>
          <?php $maxSold = $bestSellers[0]['total_sold'] ?? 1; ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div class="d-flex align-items-center gap-2 min-w-0">
                <span class="badge <?= $idx < 3 ? 'bg-warning text-dark' : 'bg-light text-dark border' ?> rounded-pill"
                      style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:.7rem;flex-shrink:0">
                  <?= $idx + 1 ?>
                </span>
                <span class="small fw-semibold text-truncate"><?= htmlspecialchars($p['name']) ?></span>
              </div>
              <span class="small text-muted text-nowrap ms-2"><?= number_format($p['total_sold']) ?> sp</span>
            </div>
            <div class="progress" style="height:5px">
              <div class="progress-bar bg-danger"
                   style="width:<?= $maxSold > 0 ? round($p['total_sold']/$maxSold*100) : 0 ?>%">
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Sản phẩm bán ế -->
  <div class="col-lg-6">
    <div class="bg-white rounded-card shadow-card p-4">
      <h6 class="fw-bold mb-3">
        <i class="bi bi-emoji-frown text-secondary me-2"></i>Top 10 sản phẩm bán ế
      </h6>
      <?php if (empty($worstSellers)): ?>
        <p class="text-muted small">Chưa có dữ liệu.</p>
      <?php else: ?>
        <?php foreach ($worstSellers as $idx => $p): ?>
          <div class="d-flex align-items-center gap-3 mb-3 pb-2 border-bottom">
            <span class="text-muted small fw-bold" style="width:20px"><?= $idx + 1 ?></span>
            <div class="flex-grow-1 min-w-0">
              <div class="small fw-semibold text-truncate"><?= htmlspecialchars($p['name']) ?></div>
              <div class="text-muted" style="font-size:.75rem">
                Tồn kho: <?= $p['stock'] ?> | Đã bán: <?= number_format($p['total_sold'] ?? 0) ?>
              </div>
            </div>
            <div class="text-end flex-shrink-0">
              <div class="small fw-bold text-danger"><?= number_format($p['sale_price'] ?? $p['price']) ?>đ</div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
const labels  = <?= json_encode(array_reverse(array_column($stats, 'period'))) ?>;
const revenue = <?= json_encode(array_map('floatval', array_reverse(array_column($stats, 'revenue')))) ?>;
const orders  = <?= json_encode(array_map('intval',   array_reverse(array_column($stats, 'order_count')))) ?>;

new Chart(document.getElementById('revenue-chart'), {
  type: 'bar',
  data: {
    labels,
    datasets: [
      {
        label: 'Doanh thu',
        data: revenue,
        backgroundColor: 'rgba(13,110,253,.18)',
        borderColor: 'rgba(13,110,253,.9)',
        borderWidth: 2,
        borderRadius: 6,
        yAxisID: 'y',
      },
      {
        label: 'Số đơn',
        data: orders,
        type: 'line',
        borderColor: 'rgba(220,53,69,.8)',
        backgroundColor: 'rgba(220,53,69,.08)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: '#dc3545',
        tension: 0.3,
        yAxisID: 'y1',
      }
    ]
  },
  options: {
    responsive: true,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { position: 'top', labels: { font: { size: 12 } } },
      tooltip: {
        callbacks: {
          label: ctx => ctx.datasetIndex === 0
            ? 'Doanh thu: ' + parseInt(ctx.raw).toLocaleString('vi-VN') + 'đ'
            : 'Đơn hàng: ' + ctx.raw
        }
      }
    },
    scales: {
      y: {
        type: 'linear', position: 'left',
        ticks: { callback: v => (v/1000000).toFixed(1)+'tr', font:{ size:11 } },
        grid: { color: 'rgba(0,0,0,.04)' }
      },
      y1: {
        type: 'linear', position: 'right',
        ticks: { font:{ size:11 } },
        grid: { drawOnChartArea: false }
      },
      x: { grid: { display: false }, ticks: { font:{ size:11 } } }
    }
  }
});
</script>
