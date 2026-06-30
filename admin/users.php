<?php

$pageTitle = 'Quản lý người dùng — LuxStay Admin';

require_once '../includes/auth_check.php';
requireAdmin();

require_once '../includes/header.php';
require_once '../api/config/db.php';

// ── Truy vấn danh sách users ───────────────────────────────────────────────
$db_obj = new Database();
$db     = $db_obj->getConnection();

$search = trim($_GET['search'] ?? '');
$role   = $_GET['role']   ?? '';
$sort   = in_array($_GET['sort'] ?? '', ['created_at','full_name','email','role']) 
          ? $_GET['sort'] : 'created_at';
$order  = ($_GET['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

$params = [];
$sql    = "SELECT id, full_name, email, phone, role, created_at FROM users WHERE 1=1";
if ($search !== '') {
    $sql    .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params  = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
}
if ($role !== '') {
    $sql    .= " AND role = ?";
    $params[] = $role;
}
$sql .= " ORDER BY $sort $order";

$stmt  = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Đếm tổng theo vai trò (cho badge thống kê)
$counts = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role")->fetchAll();
$countMap = ['user' => 0, 'admin' => 0];
foreach ($counts as $c) $countMap[$c['role']] = (int)$c['cnt'];
$totalUsers = array_sum($countMap);

// Đọc thông báo
$error = htmlspecialchars(strip_tags($_GET['error'] ?? ''));
$msg   = htmlspecialchars(strip_tags($_GET['msg']   ?? ''));

// Helper: toggle sort link
function sortLink(string $col, string $label, string $curSort, string $curOrder): string {
    $nextOrder = ($curSort === $col && $curOrder === 'asc') ? 'desc' : 'asc';
    $active    = $curSort === $col;
    $icon      = $active ? ($curOrder === 'asc' ? ' ↑' : ' ↓') : '';
    $s = isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '';
    $r = isset($_GET['role'])   ? '&role='   . urlencode($_GET['role'])   : '';
    return '<a href="?sort=' . $col . '&order=' . $nextOrder . $s . $r . '"'
         . ($active ? ' style="color:var(--color-primary);font-weight:700"' : '')
         . '>' . htmlspecialchars($label) . $icon . '</a>';
}
?>

<!-- ── ADMIN LAYOUT ──────────────────────────────────────────────────────── -->
<div class="admin-layout">

  <?php require_once '../includes/admin_sidebar.php'; ?>

  <div class="admin-content">

    <!-- Tiêu đề + nút thêm -->
    <div class="admin-content__header">
      <div>
        <h1 class="admin-content__title">Quản lý người dùng</h1>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-top:4px">
          Tổng: <strong><?= $totalUsers ?></strong> tài khoản
          &nbsp;·&nbsp; Admin: <strong><?= $countMap['admin'] ?></strong>
          &nbsp;·&nbsp; User: <strong><?= $countMap['user'] ?></strong>
        </p>
      </div>
      <button class="btn btn-primary" id="btn-open-add" onclick="openModal('modal-add')">
        <i data-lucide="user-plus" width="16" height="16"></i>
        Thêm người dùng
      </button>
    </div>

    <!-- Thông báo -->
    <?php if ($error): ?>
      <div class="alert alert-error" role="alert" style="margin-bottom:var(--space-4)">
        <i data-lucide="alert-circle" width="16" height="16" style="flex-shrink:0"></i>
        <?= $error ?>
      </div>
    <?php endif; ?>
    <?php if ($msg): ?>
      <div class="alert alert-success" role="status" style="margin-bottom:var(--space-4)">
        <i data-lucide="check-circle" width="16" height="16" style="flex-shrink:0"></i>
        <?= $msg ?>
      </div>
    <?php endif; ?>

    <!-- ── BỘ LỌC / TÌM KIẾM ──────────────────────────────────────────── -->
    <form method="GET" action=""
          style="display:flex;gap:var(--space-3);flex-wrap:wrap;
                 margin-bottom:var(--space-5);align-items:flex-end">
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:200px">
        <label class="form-label" for="search-input">Tìm kiếm</label>
        <input class="form-input" type="search" id="search-input" name="search"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Tên, email hoặc số điện thoại…">
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:150px">
        <label class="form-label" for="role-filter">Vai trò</label>
        <select class="form-select" id="role-filter" name="role">
          <option value="">Tất cả</option>
          <option value="user"  <?= $role === 'user'  ? 'selected' : '' ?>>Người dùng</option>
          <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
        </select>
      </div>
      <input type="hidden" name="sort"  value="<?= htmlspecialchars($sort) ?>">
      <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
      <div style="display:flex;gap:var(--space-2)">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="search" width="15" height="15"></i> Tìm
        </button>
        <?php if ($search || $role): ?>
          <a href="?" class="btn btn-secondary">
            <i data-lucide="x" width="15" height="15"></i> Xóa lọc
          </a>
        <?php endif; ?>
      </div>
    </form>

    <!-- ── BẢNG DANH SÁCH ─────────────────────────────────────────────── -->
    <div class="table-wrapper">
      <table class="table" aria-label="Danh sách người dùng">
        <thead>
          <tr>
            <th style="width:40px">#</th>
            <th><?= sortLink('full_name', 'Họ và tên', $sort, $order) ?></th>
            <th><?= sortLink('email', 'Email', $sort, $order) ?></th>
            <th>Điện thoại</th>
            <th><?= sortLink('role', 'Vai trò', $sort, $order) ?></th>
            <th><?= sortLink('created_at', 'Ngày tạo', $sort, $order) ?></th>
            <th style="width:130px;text-align:center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr>
              <td colspan="7" style="text-align:center;padding:var(--space-12);
                color:var(--color-text-faint)">
                <i data-lucide="users" width="32" height="32"
                   style="margin:0 auto var(--space-3)"></i>
                <p>Không tìm thấy người dùng nào</p>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($users as $i => $u): ?>
              <tr>
                <td class="text-muted text-xs"><?= $i + 1 ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:var(--space-3)">
                    <span style="width:32px;height:32px;border-radius:var(--radius-full);
                      background:var(--color-primary-highlight);color:var(--color-primary);
                      display:inline-flex;align-items:center;justify-content:center;
                      font-weight:700;font-size:var(--text-xs);flex-shrink:0">
                      <?= mb_strtoupper(mb_substr($u['full_name'], 0, 1, 'UTF-8'), 'UTF-8') ?>
                    </span>
                    <span style="font-weight:500">
                      <?= htmlspecialchars($u['full_name']) ?>
                      <?php if ((int)$u['id'] === (int)$_SESSION['user']['id']): ?>
                        <span class="badge" style="background:var(--color-warning-highlight);
                          color:var(--color-warning);margin-left:4px">Tôi</span>
                      <?php endif; ?>
                    </span>
                  </div>
                </td>
                <td style="color:var(--color-text-muted)">
                  <?= htmlspecialchars($u['email']) ?>
                </td>
                <td style="color:var(--color-text-muted)">
                  <?= htmlspecialchars($u['phone'] ?? '—') ?>
                </td>
                <td>
                  <span class="badge <?= $u['role'] === 'admin' ? 'badge-admin' : 'badge-user' ?>">
                    <?= $u['role'] === 'admin' ? 'Admin' : 'User' ?>
                  </span>
                </td>
                <td class="text-sm text-muted">
                  <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                </td>
                <td>
                  <div style="display:flex;gap:var(--space-1);justify-content:center">
                    <button class="btn btn-secondary btn-sm"
                            aria-label="Sửa <?= htmlspecialchars($u['full_name']) ?>"
                            onclick="openEditModal(this)"
                            data-id="<?= $u['id'] ?>"
                            data-name="<?= htmlspecialchars($u['full_name'], ENT_QUOTES) ?>"
                            data-email="<?= htmlspecialchars($u['email'], ENT_QUOTES) ?>"
                            data-phone="<?= htmlspecialchars($u['phone'] ?? '', ENT_QUOTES) ?>"
                            data-role="<?= $u['role'] ?>">
                      <i data-lucide="pencil" width="13" height="13"></i>
                    </button>
                    <!-- Nút Xóa: form POST ẩn -->
                    <?php if ((int)$u['id'] !== (int)$_SESSION['user']['id']): ?>
                      <form method="POST"
                            action="<?= BASE_URL ?>/api/users.php?action=admin_delete"
                            style="margin:0"
                            onsubmit="return confirm('Xóa tài khoản <?= htmlspecialchars(addslashes($u['full_name'])) ?>?\nHành động này không thể hoàn tác.')">
                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"
                                aria-label="Xóa <?= htmlspecialchars($u['full_name']) ?>">
                          <i data-lucide="trash-2" width="13" height="13"></i>
                        </button>
                      </form>
                    <?php else: ?>
                      <button class="btn btn-sm" disabled
                              title="Không thể xóa tài khoản đang đăng nhập"
                              style="opacity:.3;cursor:not-allowed">
                        <i data-lucide="trash-2" width="13" height="13"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <p style="margin-top:var(--space-3);font-size:var(--text-xs);color:var(--color-text-faint)">
      Hiển thị <?= count($users) ?> / <?= $totalUsers ?> tài khoản
    </p>

  </div>
</div>


<!-- ════════════════════════════════════════════════════
     MODAL: THÊM NGƯỜI DÙNG
════════════════════════════════════════════════════ -->
<div id="modal-add" class="modal-overlay" role="dialog"
     aria-modal="true" aria-labelledby="modal-add-title">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title" id="modal-add-title">
        <i data-lucide="user-plus" width="18" height="18" style="vertical-align:middle"></i>
        Thêm người dùng mới
      </h2>
      <button class="btn-icon" onclick="closeModal('modal-add')" aria-label="Đóng">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>

    <form method="POST"
          action="<?= BASE_URL ?>/api/users.php?action=admin_add"
          id="form-add"
          novalidate>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="add-full-name">Họ và tên *</label>
            <input class="form-input" type="text" id="add-full-name"
                   name="full_name" required maxlength="100" placeholder="Nguyễn Văn A">
          </div>
          <div class="form-group">
            <label class="form-label" for="add-phone">Số điện thoại</label>
            <input class="form-input" type="tel" id="add-phone"
                   name="phone" placeholder="0912 345 678" maxlength="20">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="add-email">Email *</label>
          <input class="form-input" type="email" id="add-email"
                 name="email" required placeholder="user@email.com">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="add-password">Mật khẩu *</label>
            <input class="form-input" type="password" id="add-password"
                   name="password" required minlength="6" placeholder="Tối thiểu 6 ký tự">
          </div>
          <div class="form-group">
            <label class="form-label" for="add-role">Vai trò</label>
            <select class="form-select" id="add-role" name="role">
              <option value="user">Người dùng</option>
              <option value="admin">Quản trị viên</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add')">
          Hủy
        </button>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="plus" width="15" height="15"></i>
          Thêm tài khoản
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ════════════════════════════════════════════════════
     MODAL: SỬA NGƯỜI DÙNG
════════════════════════════════════════════════════ -->
<div id="modal-edit" class="modal-overlay" role="dialog"
     aria-modal="true" aria-labelledby="modal-edit-title">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title" id="modal-edit-title">
        <i data-lucide="pencil" width="18" height="18" style="vertical-align:middle"></i>
        Chỉnh sửa người dùng
      </h2>
      <button class="btn-icon" onclick="closeModal('modal-edit')" aria-label="Đóng">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>

    <form method="POST"
          action="<?= BASE_URL ?>/api/users.php?action=admin_update"
          id="form-edit"
          novalidate>
      <input type="hidden" id="edit-id" name="id" value="">

      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="edit-full-name">Họ và tên *</label>
            <input class="form-input" type="text" id="edit-full-name"
                   name="full_name" required maxlength="100">
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-phone">Số điện thoại</label>
            <input class="form-input" type="tel" id="edit-phone"
                   name="phone" maxlength="20">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="edit-email">Email *</label>
          <input class="form-input" type="email" id="edit-email"
                 name="email" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="edit-role">Vai trò</label>
            <select class="form-select" id="edit-role" name="role">
              <option value="user">Người dùng</option>
              <option value="admin">Quản trị viên</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-password">Mật khẩu mới</label>
            <input class="form-input" type="password" id="edit-password"
                   name="password" minlength="6" placeholder="Để trống = không đổi">
            <p class="form-help">Để trống nếu không muốn thay đổi</p>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">
          Hủy
        </button>
        <button type="submit" class="btn btn-primary">
          <i data-lucide="save" width="15" height="15"></i>
          Lưu thay đổi
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ── STYLES riêng trang admin ───────────────────────────────────────── -->
<style>
.alert  { display:flex; align-items:flex-start; gap:var(--space-2); }
.btn-icon {
  width:36px; height:36px; border-radius:var(--radius-md);
  display:inline-flex; align-items:center; justify-content:center;
  color:var(--color-text-muted); border:none; background:none; cursor:pointer;
  transition: background var(--transition-interactive), color var(--transition-interactive);
}
.btn-icon:hover { background:var(--color-surface-offset); color:var(--color-text); }

/* Hiệu ứng fade-in khi mở modal */
.modal-overlay { animation: none; }
.modal-overlay.open {
  display: flex;
  animation: fadeIn .15s ease;
}
@keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
.modal { animation: slideUp .18s ease; }
@keyframes slideUp {
  from { transform:translateY(12px); opacity:0; }
  to   { transform:translateY(0);    opacity:1; }
}

/* Cột thao tác không wrap */
.table td:last-child { white-space:nowrap; }
</style>


<!-- ── SCRIPTS ────────────────────────────────────────────────────────── -->
<script>
// ── Mở / đóng modal ──────────────────────────────────────────────────────
function openModal(id) {
  var el = document.getElementById(id);
  el.classList.add('open');
  el.querySelector('input:not([type=hidden])') &&
    setTimeout(function () { el.querySelector('input:not([type=hidden])').focus(); }, 50);
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow = '';
}

// Đóng modal khi click backdrop
document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
  overlay.addEventListener('click', function (e) {
    if (e.target === overlay) closeModal(overlay.id);
  });
});
// Đóng bằng Escape
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape')
    document.querySelectorAll('.modal-overlay.open').forEach(function (m) {
      closeModal(m.id);
    });
});

// ── Mở modal Edit và điền sẵn dữ liệu ───────────────────────────────────
function openEditModal(btn) {
  document.getElementById('edit-id').value         = btn.dataset.id;
  document.getElementById('edit-full-name').value  = btn.dataset.name;
  document.getElementById('edit-email').value      = btn.dataset.email;
  document.getElementById('edit-phone').value      = btn.dataset.phone;
  document.getElementById('edit-role').value       = btn.dataset.role;
  document.getElementById('edit-password').value   = '';
  openModal('modal-edit');
}

// ── Auto-dismiss thông báo sau 4 giây ────────────────────────────────────
(function () {
  var alerts = document.querySelectorAll('.alert');
  alerts.forEach(function (a) {
    setTimeout(function () {
      a.style.transition = 'opacity .4s';
      a.style.opacity = '0';
      setTimeout(function () { a.remove(); }, 400);
    }, 4000);
  });
})();
</script>

<?php require_once '../includes/footer.php'; ?>
