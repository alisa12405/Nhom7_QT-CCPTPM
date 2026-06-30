<?php
$pageTitle = 'Đăng ký — LuxStay';
require_once __DIR__ . '/../includes/header.php';
$error = htmlspecialchars($_GET['error'] ?? '');
?>
<section style="min-height:calc(100vh - 64px);display:flex;align-items:flex-start;
  justify-content:center;padding:3rem 1rem;
  background:linear-gradient(160deg,var(--color-bg),var(--color-primary-highlight))">
  <div style="background:var(--color-surface-2);border:1px solid var(--color-border);
    border-radius:1rem;padding:2.5rem;width:100%;max-width:520px;box-shadow:var(--shadow-lg)">

    <h1 style="font-family:var(--font-display);font-size:var(--text-xl);
      font-style:italic;margin-bottom:.5rem">Tạo tài khoản</h1>
    <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:2rem">
      Đăng ký để đặt phòng nhanh hơn</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASEURL ?>api/auth.php?action=register" id="reg-form">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="full_name">Họ và tên *</label>
          <input class="form-input" type="text" id="full_name" name="full_name" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="phone">Số điện thoại</label>
          <input class="form-input" type="tel" id="phone" name="phone">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="email">Email *</label>
        <input class="form-input" type="email" id="email" name="email" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="password">Mật khẩu *</label>
          <input class="form-input" type="password" id="password" name="password"
                 placeholder="Tối thiểu 6 ký tự" required minlength="6">
        </div>
        <div class="form-group">
          <label class="form-label" for="confirm_password">Xác nhận MK *</label>
          <input class="form-input" type="password" id="confirm_password"
                 name="confirm_password" required>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-full"
              style="padding:var(--space-4);margin-top:var(--space-2)">
        Tạo tài khoản
      </button>
    </form>
    <p style="text-align:center;margin-top:1.5rem;font-size:var(--text-sm);
       color:var(--color-text-muted)">
      Đã có tài khoản?
      <a href="<?= BASEURL ?>pages/login.php" style="color:var(--color-primary);font-weight:600">Đăng nhập</a>
    </p>
  </div>
</section>
<script>
document.getElementById('reg-form').addEventListener('submit', function(e) {
  const pw  = document.getElementById('password').value;
  const cfm = document.getElementById('confirm_password').value;
  if (pw !== cfm) { e.preventDefault(); alert('Mật khẩu xác nhận không khớp!'); }
});
</script>
<?php require_once '../includes/footer.php'; ?>