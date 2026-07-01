<?php
// includes/footer.php
?>
</main><!-- /#main-content -->
//binh sua footer
<!-- ======================================================
     FOOTER
====================================================== -->
<footer class="site-footer">
  <div class="container">
    <div class="footer__grid">

      <div>
        <p class="footer__brand-name">LuxStay</p>
        <p style="max-width:36ch;line-height:1.7">
          Trải nghiệm lưu trú đẳng cấp tại những căn phòng được chọn lọc kỹ lưỡng.
        </p>
        <p style="margin-top:var(--space-4);font-size:var(--text-xs)">
          ✉&nbsp;support@luxstay.vn &nbsp;·&nbsp; ☎&nbsp;1800 123 456
        </p>
      </div>

      <nav aria-label="Liên kết nhanh">
        <p class="footer__heading">Khám phá</p>
        <ul class="footer__links">
          <li><a href="<?= BASE_URL ?>/pages/index.php">Trang chủ</a></li>
          <li><a href="<?= BASE_URL ?>/pages/rooms.php">Danh sách phòng</a></li>
          <li><a href="<?= BASE_URL ?>/pages/amenities.php">Tiện ích</a></li>
          <?php if (isset($_SESSION['user'])): ?>
            <li><a href="<?= BASE_URL ?>/pages/my_bookings.php">Đặt phòng của tôi</a></li>
            <li><a href="<?= BASE_URL ?>/pages/profile.php">Tài khoản</a></li>
          <?php else: ?>
            <li><a href="<?= BASE_URL ?>/pages/login.php">Đăng nhập</a></li>
            <li><a href="<?= BASE_URL ?>/pages/register.php">Đăng ký</a></li>
          <?php endif; ?>
        </ul>
      </nav>

      <nav aria-label="Hỗ trợ">
        <p class="footer__heading">Hỗ trợ</p>
        <ul class="footer__links">
          <li><a href="#">Chính sách đặt phòng</a></li>
          <li><a href="#">Chính sách hủy phòng</a></li>
          <li><a href="#">Câu hỏi thường gặp</a></li>
          <li><a href="#">Liên hệ</a></li>
        </ul>
      </nav>

    </div>

    <div class="footer__bottom">
      <p>&copy; <?= date('Y') ?> LuxStay. Đã đăng ký bản quyền.</p>
      <p style="color:var(--color-text-faint)">Xây dựng bằng PHP &amp; MySQL</p>
    </div>
  </div>
</footer>

<!-- Lucide icons init -->
<script>if (typeof lucide !== 'undefined') lucide.createIcons();</script>

<!-- ======================================================
     GLOBAL SCRIPTS — dark mode, nav, dropdown
====================================================== -->
<script>
(function () {
  'use strict';
  var html    = document.documentElement;
  var themeBtn = document.querySelector('[data-theme-toggle]');
  var navToggle = document.getElementById('nav-toggle');
  var mainNav   = document.getElementById('main-nav');
  var menuBtn   = document.getElementById('user-menu-btn');
  var menuPanel = document.getElementById('user-menu');

  // ── Theme toggle ──────────────────────────────────────
  function getTheme() { return html.getAttribute('data-theme') || 'light'; }
  function setTheme(t) {
    html.setAttribute('data-theme', t);
    try { localStorage.setItem('luxstay_theme', t); } catch(e) {}
    renderThemeIcon(t);
  }
  function renderThemeIcon(t) {
    if (!themeBtn) return;
    themeBtn.innerHTML = t === 'dark'
      ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>'
      : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';
    themeBtn.setAttribute('aria-label', t === 'dark' ? 'Chuyển sang sáng' : 'Chuyển sang tối');
  }
  renderThemeIcon(getTheme());
  themeBtn && themeBtn.addEventListener('click', function () {
    setTheme(getTheme() === 'dark' ? 'light' : 'dark');
  });

  // ── Hamburger ─────────────────────────────────────────
  navToggle && navToggle.addEventListener('click', function () {
    var open = mainNav.classList.toggle('open');
    navToggle.setAttribute('aria-expanded', String(open));
  });

  // ── User dropdown ─────────────────────────────────────
  function closeMenu() {
    if (!menuPanel) return;
    menuPanel.classList.remove('open');
    menuBtn && menuBtn.setAttribute('aria-expanded', 'false');
  }
  menuBtn && menuBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    var isOpen = menuPanel.classList.toggle('open');
    menuBtn.setAttribute('aria-expanded', String(isOpen));
  });
  document.addEventListener('click', closeMenu);
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMenu();
  });
})();
</script>

</body>
</html>
