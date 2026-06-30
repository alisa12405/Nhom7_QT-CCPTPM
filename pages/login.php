<?php
/**
 * pages/login.php
 */
$pageTitle = 'Đăng nhập — LuxStay';

require_once __DIR__ . '/../includes/header.php';

$error = htmlspecialchars(strip_tags($_GET['error'] ?? ''));
$msg   = htmlspecialchars(strip_tags($_GET['msg']   ?? ''));

// if ($isLoggedIn) {
//     header('Location: ' . BASE_URL . '/pages/index.php');
//     exit;
// }
?>

<!-- ════════════════════════════════════════════════════
     NỘI DUNG TRANG ĐĂNG NHẬP
════════════════════════════════════════════════════ -->
<section class="auth-section">
  <div class="auth-card">

    <!-- Header card -->
    <div class="auth-card__header">
      <h1 class="auth-card__title">Đăng nhập</h1>
      <p class="auth-card__subtitle">Chào mừng trở lại LuxStay</p>
    </div>

    <!-- Thông báo lỗi / thành công -->
    <?php if ($error): ?>
      <div class="alert alert-error" role="alert">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" aria-hidden="true"
             style="flex-shrink:0">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8"  x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <?= $error ?>
      </div>
    <?php endif; ?>

    <?php if ($msg): ?>
      <div class="alert alert-success" role="status">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2" aria-hidden="true"
             style="flex-shrink:0">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        <?= $msg ?>
      </div>
    <?php endif; ?>

    <!-- Form đăng nhập -->
    <form method="POST"
          action="<?= BASE_URL ?>/api/auth.php?action=login"
          novalidate
          id="login-form">

      <div class="form-group">
        <label class="form-label" for="email">Email</label>
        <input class="form-input"
               type="email"
               id="email"
               name="email"
               placeholder="your@email.com"
               required
               autocomplete="email"
               autofocus>
      </div>

      <div class="form-group">
        <label class="form-label" for="password">Mật khẩu</label>
        <div style="position:relative">
          <input class="form-input"
                 type="password"
                 id="password"
                 name="password"
                 placeholder="••••••••"
                 required
                 autocomplete="current-password"
                 style="padding-right:var(--space-10)">
          <!-- Toggle hiện/ẩn mật khẩu -->
          <button type="button"
                  id="toggle-pw"
                  aria-label="Hiện/ẩn mật khẩu"
                  style="position:absolute;right:var(--space-3);top:50%;
                         transform:translateY(-50%);
                         color:var(--color-text-faint);padding:var(--space-1)">
            <svg id="pw-eye" width="18" height="18" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full" style="margin-top:var(--space-2)">
        Đăng nhập
      </button>

    </form>

    <!-- Footer card -->
    <p class="auth-card__footer-text">
      Chưa có tài khoản?
      <a href="<?= BASE_URL ?>/pages/register.php">Đăng ký ngay</a>
    </p>


  </div><!-- /.auth-card -->
</section>

<!-- CSS riêng cho trang auth -->
<style>
.auth-section {
  min-height: calc(100dvh - 64px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: var(--space-8) var(--space-4);
  background:
    radial-gradient(ellipse 80% 60% at 20% 80%, oklch(from var(--color-primary) l c h / 0.08) 0%, transparent 60%),
    var(--color-bg);
}
.auth-card {
  background: var(--color-surface-2);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-xl);
  padding: var(--space-10);
  width: 100%;
  max-width: 420px;
  box-shadow: var(--shadow-lg);
}
.auth-card__header { margin-bottom: var(--space-6); }
.auth-card__title {
  font-family: var(--font-display);
  font-size: var(--text-xl);
  font-style: italic;
  font-weight: 600;
  margin-bottom: var(--space-1);
}
.auth-card__subtitle {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}
.auth-card__footer-text {
  text-align: center;
  margin-top: var(--space-6);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}
.auth-card__footer-text a {
  color: var(--color-primary);
  font-weight: 600;
}
.alert { display: flex; align-items: flex-start; gap: var(--space-2); }

/* Demo hint */
.demo-hint {
  margin-top: var(--space-5);
  border: 1px solid var(--color-divider);
  border-radius: var(--radius-md);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  overflow: hidden;
}
.demo-hint summary {
  padding: var(--space-2) var(--space-3);
  cursor: pointer;
  user-select: none;
  background: var(--color-surface-offset);
}
.demo-hint div {
  padding: var(--space-3);
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  background: var(--color-surface);
}
.demo-hint p { max-width: none; }

@media (max-width: 480px) {
  .auth-card { padding: var(--space-6); }
}
</style>

<!-- Script toggle password -->
<script>
(function () {
  var btn   = document.getElementById('toggle-pw');
  var input = document.getElementById('password');
  if (!btn || !input) return;
  btn.addEventListener('click', function () {
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.innerHTML = show
      ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
      : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
    btn.setAttribute('aria-label', show ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
  });
})();
</script>

<?php require_once '../includes/footer.php'; ?>
