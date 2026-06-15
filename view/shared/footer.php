</main>

<!-- ── FOOTER ─────────────────────────────────────────────── -->
<footer class="footer mt-5 pt-5 pb-3">
  <div class="container">
    <div class="row g-4">

      <!-- Thông tin -->
      <div class="col-lg-3 col-md-6">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="logo-icon logo-icon-sm"><span>U</span></div>
          <span class="logo-text text-white">UPNEX</span>
        </div>
        <p class="text-muted small">Chuyên cung cấp điện thoại, laptop, tablet và phụ kiện chính hãng với giá tốt nhất.</p>
        <div class="d-flex gap-2 mt-3">
          <a href="#" class="social-btn"><i class="bi bi-facebook"></i></a>
          <a href="#" class="social-btn"><i class="bi bi-instagram"></i></a>
          <a href="#" class="social-btn"><i class="bi bi-tiktok"></i></a>
          <a href="#" class="social-btn"><i class="bi bi-youtube"></i></a>
        </div>
      </div>

      <!-- Danh mục -->
      <div class="col-lg-2 col-md-6">
        <h6 class="text-white fw-semibold mb-3">Danh mục</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="?case=products&cat=1">Điện thoại</a></li>
          <li><a href="?case=products&cat=2">Laptop</a></li>
          <li><a href="?case=products&cat=3">Tablet</a></li>
          <li><a href="?case=products&cat=4">Phụ kiện</a></li>
        </ul>
      </div>

      <!-- Hỗ trợ -->
      <div class="col-lg-2 col-md-6">
        <h6 class="text-white fw-semibold mb-3">Hỗ trợ</h6>
        <ul class="list-unstyled footer-links">
          <li><a href="#">Chính sách đổi trả</a></li>
          <li><a href="#">Chính sách bảo hành</a></li>
          <li><a href="#">Hướng dẫn thanh toán</a></li>
          <li><a href="#">Tra cứu đơn hàng</a></li>
        </ul>
      </div>

      <!-- Liên hệ -->
      <div class="col-lg-2 col-md-6">
        <h6 class="text-white fw-semibold mb-3">Liên hệ</h6>
        <ul class="list-unstyled footer-links">
          <li><i class="bi bi-telephone me-2 text-primary"></i>1800 6868</li>
          <li><i class="bi bi-envelope me-2 text-primary"></i>support@upnex.vn</li>
          <li><i class="bi bi-clock me-2 text-primary"></i>8:00 – 22:00 mỗi ngày</li>
          <li><i class="bi bi-geo-alt me-2 text-primary"></i>TP. Hồ Chí Minh</li>
        </ul>
      </div>

      <!-- Google Maps -->
      <div class="col-lg-3">
        <h6 class="text-white fw-semibold mb-3">Tìm cửa hàng</h6>
        <div class="rounded overflow-hidden" style="height:160px">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.446463!2d106.6974!3d10.7762!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTDCsDQ2JzM0LjMiTiAxMDbCsDQxJzUwLjYiRQ!5e0!3m2!1svi!2svn!4v1700000000"
            width="100%" height="160" style="border:0" allowfullscreen="" loading="lazy"
            referrerpolicy="no-referrer-when-downgrade" title="UPNEX Store Location">
          </iframe>
        </div>
      </div>
    </div>

    <!-- Thanh toán & bảo hành -->
    <div class="row mt-4 pt-3 border-top border-secondary">
      <div class="col-md-6 mb-3 mb-md-0">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <small class="text-muted me-2">Thanh toán:</small>
          <span class="payment-badge">COD</span>
          <span class="payment-badge">Chuyển khoản</span>
          <span class="payment-badge">MoMo</span>
          <span class="payment-badge">VNPay</span>
        </div>
      </div>
      <div class="col-md-6 text-md-end">
        <small class="text-muted">© <?= date('Y') ?> UPNEX. Đã đăng ký bản quyền.</small>
      </div>
    </div>
  </div>
</footer>

<!-- Theme toggle script (sáng/tối) -->
<script>
(function(){
  const key = 'upnex_theme';
  const root = document.documentElement;
  const btn = document.getElementById('theme-toggle');

  const apply = (theme) => {
    if (theme === 'dark') {
      root.classList.add('theme-dark');
      root.classList.remove('theme-light');
      btn?.querySelector('i')?.classList.remove('bi-moon-stars');
      btn?.querySelector('i')?.classList.add('bi-sun');
    } else {
      root.classList.add('theme-light');
      root.classList.remove('theme-dark');
      btn?.querySelector('i')?.classList.remove('bi-sun');
      btn?.querySelector('i')?.classList.add('bi-moon-stars');
    }
  };

  const saved = localStorage.getItem(key);
  const systemPrefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
  const initial = saved ? saved : (systemPrefersDark ? 'dark' : 'light');
  apply(initial);

  btn?.addEventListener('click', () => {
    const isDark = root.classList.contains('theme-dark');
    const next = isDark ? 'light' : 'dark';
    localStorage.setItem(key, next);
    apply(next);
  });
})();
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- UPNEX JS -->
<script src="<?= BASE_URL ?>/public/js/upnex.js"></script>

<!-- Search button href động -->
<script>
document.getElementById('search-btn')?.addEventListener('click', function(e) {
  e.preventDefault();
  const q = document.getElementById('search-input').value.trim();
  window.location.href = '?case=products&q=' + encodeURIComponent(q);
});
document.getElementById('search-input')?.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') {
    window.location.href = '?case=products&q=' + encodeURIComponent(this.value.trim());
  }
});
</script>

</body>
</html>
