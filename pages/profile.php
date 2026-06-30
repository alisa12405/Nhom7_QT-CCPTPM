<?php
/**
 * pages/profile.php — Trang hồ sơ cá nhân
 */
$pageTitle = 'Hồ sơ của tôi — LuxStay';

require_once '../includes/auth_check.php';
requireLogin();

require_once '../includes/header.php';
require_once '../api/config/db.php';

// ── Lấy thông tin mới nhất từ DB ──────────────────
$db_obj = new Database();
$db     = $db_obj->getConnection();
$stmt   = $db->prepare("SELECT id, full_name, email, phone, role, created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$profile = $stmt->fetch();

if (!$profile) {
    // Tài khoản bị xóa trong lúc đang đăng nhập
    session_destroy();
    header('Location: ' . BASE_URL . '/pages/login.php?error=' . urlencode('Tài khoản không tồn tại'));
    exit;
}

$error = htmlspecialchars(strip_tags($_GET['error'] ?? ''));
$msg   = htmlspecialchars(strip_tags($_GET['msg']   ?? ''));
?>

<!-- ── BREADCRUMB ──────────────────────────────────────────────────────── -->
<div style="background:var(--color-surface);border-bottom:1px solid var(--color-border);
  padding:var(--space-3) 0">
  <div class="container">
    <nav aria-label="Breadcrumb" style="font-size:var(--text-xs);color:var(--color-text-muted);
      display:flex;align-items:center;gap:var(--space-2)">
      <a href="<?= BASE_URL ?>/pages/index.php">Trang chủ</a>
      <span aria-hidden="true">›</span>
      <span aria-current="page">Hồ sơ cá nhân</span>
    </nav>
  </div>
</div>

<!-- ── NỘI DUNG CHÍNH ─────────────────────────────────────────────────── -->
<section style="padding:var(--space-10) 0 var(--space-16)">
  <div class="container" style="max-width:780px">

    <!-- Tiêu đề trang -->
    <div style="margin-bottom:var(--space-8)">
      <h1 style="font-family:var(--font-display);font-size:var(--text-xl);
        font-style:italic;margin-bottom:var(--space-1)">Hồ sơ cá nhân</h1>
      <p style="color:var(--color-text-muted);font-size:var(--text-sm)">
        Quản lý thông tin tài khoản và mật khẩu của bạn
      </p>
    </div>

    <!-- Thông báo -->
    <?php if ($error): ?>
      <div class="alert alert-error" role="alert" style="margin-bottom:var(--space-6)">
        <i data-lucide="alert-circle" width="16" height="16" style="flex-shrink:0"></i>
        <?= $error ?>
      </div>
    <?php endif; ?>
    <?php if ($msg): ?>
      <div class="alert alert-success" role="status" style="margin-bottom:var(--space-6)">
        <i data-lucide="check-circle" width="16" height="16" style="flex-shrink:0"></i>
        <?= $msg ?>
      </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 2fr;gap:var(--space-6);
      align-items:start">

      <!-- ── Cột trái: Avatar + thông tin nhanh ─────────────────────── -->
      <div class="card">
        <div class="card-body" style="display:flex;flex-direction:column;align-items:center;
          text-align:center;gap:var(--space-4)">

          <!-- Avatar chữ cái -->
          <div style="width:80px;height:80px;border-radius:var(--radius-full);
            background:var(--color-primary-highlight);color:var(--color-primary);
            display:flex;align-items:center;justify-content:center;
            font-family:var(--font-display);font-size:var(--text-xl);
            font-style:italic;font-weight:600">
            <?= mb_strtoupper(mb_substr($profile['full_name'], 0, 1, 'UTF-8'), 'UTF-8') ?>
          </div>

          <div>
            <p style="font-weight:600;font-size:var(--text-base)">
              <?= htmlspecialchars($profile['full_name']) ?>
            </p>
            <p style="font-size:var(--text-xs);color:var(--color-text-muted);
              margin-top:var(--space-1)">
              <?= htmlspecialchars($profile['email']) ?>
            </p>
          </div>

          <!-- Badge vai trò -->
          <span class="badge <?= $profile['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
            <?= $profile['role'] === 'admin' ? 'Quản trị viên' : 'Người dùng' ?>
          </span>

          <!-- Ngày tạo tài khoản -->
          <div style="width:100%;padding-top:var(--space-4);
            border-top:1px solid var(--color-divider)">
            <p style="font-size:var(--text-xs);color:var(--color-text-faint)">Thành viên từ</p>
            <p style="font-size:var(--text-sm);font-weight:500;margin-top:2px">
              <?= date('d/m/Y', strtotime($profile['created_at'])) ?>
            </p>
          </div>

          <?php if ($profile['role'] === 'admin'): ?>
            <a href="<?= BASE_URL ?>/admin/dashboard.php"
               class="btn btn-secondary btn-full btn-sm">
              <i data-lucide="layout-dashboard" width="14" height="14"></i>
              Vào trang Admin
            </a>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── Cột phải: Form chỉnh sửa ──────────────────────────────── -->
      <div style="display:flex;flex-direction:column;gap:var(--space-5)">

        <!-- Form thông tin cơ bản -->
        <div class="card">
          <div class="card-body">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-bottom:var(--space-5)">
              Thông tin cơ bản
            </h2>
            <form method="POST"
                  action="<?= BASE_URL ?>/api/users.php?action=update_profile"
                  id="form-info">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="full_name">Họ và tên *</label>
                  <input class="form-input" type="text" id="full_name" name="full_name"
                         value="<?= htmlspecialchars($profile['full_name']) ?>"
                         required maxlength="100">
                </div>
                <div class="form-group">
                  <label class="form-label" for="phone">Số điện thoại</label>
                  <input class="form-input" type="tel" id="phone" name="phone"
                         value="<?= htmlspecialchars($profile['phone'] ?? '') ?>"
                         placeholder="0912 345 678" maxlength="20">
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-input" type="email"
                       value="<?= htmlspecialchars($profile['email']) ?>"
                       disabled
                       style="opacity:.6;cursor:not-allowed">
                <p class="form-help">Email không thể thay đổi</p>
              </div>
              <!-- Ẩn 2 field mật khẩu (bỏ trống = không đổi) -->
              <input type="hidden" name="password"         value="">
              <input type="hidden" name="confirm_password" value="">

              <div style="display:flex;justify-content:flex-end;margin-top:var(--space-2)">
                <button type="submit" class="btn btn-primary">
                  <i data-lucide="save" width="15" height="15"></i>
                  Lưu thay đổi
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Form đổi mật khẩu -->
        <div class="card">
          <div class="card-body">
            <h2 style="font-size:var(--text-lg);font-weight:600;margin-bottom:var(--space-1)">
              Đổi mật khẩu
            </h2>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);
              margin-bottom:var(--space-5)">
              Để trống nếu không muốn thay đổi mật khẩu
            </p>
            <form method="POST"
                  action="<?= BASE_URL ?>/api/users.php?action=update_profile"
                  id="form-password"
                  novalidate>
              <!-- Gửi kèm full_name + phone để API cập nhật đầy đủ -->
              <input type="hidden" name="full_name"
                     value="<?= htmlspecialchars($profile['full_name']) ?>">
              <input type="hidden" name="phone"
                     value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="new_password">Mật khẩu mới</label>
                  <input class="form-input" type="password" id="new_password"
                         name="password" placeholder="Tối thiểu 6 ký tự" minlength="6">
                </div>
                <div class="form-group">
                  <label class="form-label" for="confirm_password">Xác nhận mật khẩu</label>
                  <input class="form-input" type="password" id="confirm_password"
                         name="confirm_password" placeholder="Nhập lại mật khẩu mới">
                </div>
              </div>
              <div id="pw-match-hint" style="display:none;font-size:var(--text-xs);
                margin-top:calc(-1 * var(--space-3));margin-bottom:var(--space-4)"></div>

              <div style="display:flex;justify-content:flex-end">
                <button type="submit" class="btn btn-primary" id="btn-change-pw" disabled>
                  <i data-lucide="key-round" width="15" height="15"></i>
                  Cập nhật mật khẩu
                </button>
              </div>
            </form>
          </div>
        </div>

      </div><!-- /.col-right -->
    </div><!-- /.grid -->
  </div><!-- /.container -->
</section>

<style>
@media (max-width: 640px) {
  section > .container > div[style*="grid-template-columns"] {
    grid-template-columns: 1fr !important;
  }
}
.alert { display:flex;align-items:flex-start;gap:var(--space-2); }
</style>

<script>
// Validate mật khẩu khớp realtime
(function () {
  var pw      = document.getElementById('new_password');
  var cfm     = document.getElementById('confirm_password');
  var hint    = document.getElementById('pw-match-hint');
  var btn     = document.getElementById('btn-change-pw');

  function check() {
    var p = pw.value, c = cfm.value;
    if (!p && !c) {
      hint.style.display = 'none';
      btn.disabled = true;
      return;
    }
    hint.style.display = 'block';
    if (p.length < 6) {
      hint.textContent = '⚠ Mật khẩu phải từ 6 ký tự';
      hint.style.color = 'var(--color-warning)';
      btn.disabled = true;
    } else if (p !== c && c.length > 0) {
      hint.textContent = '✗ Mật khẩu xác nhận chưa khớp';
      hint.style.color = 'var(--color-error)';
      btn.disabled = true;
    } else if (p === c && c.length > 0) {
      hint.textContent = '✓ Mật khẩu khớp';
      hint.style.color = 'var(--color-success)';
      btn.disabled = false;
    } else {
      hint.textContent = '';
      btn.disabled = true;
    }
  }
  pw.addEventListener('input', check);
  cfm.addEventListener('input', check);

  // Ngăn submit form mật khẩu nếu chưa hợp lệ
  document.getElementById('form-password').addEventListener('submit', function (e) {
    var p = pw.value, c = cfm.value;
    if (!p) { e.preventDefault(); return; } // Bỏ trống = không đổi, submit form info thay
    if (p !== c || p.length < 6) { e.preventDefault(); }
  });
})();
</script>

<?php require_once '../includes/footer.php'; ?>
