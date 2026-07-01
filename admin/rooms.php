<?php
/**
 * admin/rooms.php — Quản lý phòng
 */
$pageTitle = 'Quản lý phòng — LuxStay Admin';
//ndchjdbjcjbhdjbcbksjn slkvndsnvkjsdvoidsnvnsdvndskbjvkjdvsdjkvbksdjvbkjsbvkjskd
require_once '../includes/auth_check.php';
requireAdmin();

require_once '../api/config/db.php';
require_once '../includes/header.php';

$db = (new Database())->getConnection();

$search     = trim($_GET['search'] ?? '');
$status     = trim($_GET['status'] ?? '');
$roomTypeId = (int)($_GET['room_type_id'] ?? 0);
$error      = htmlspecialchars(strip_tags($_GET['error'] ?? ''));
$msg        = htmlspecialchars(strip_tags($_GET['msg'] ?? ''));

$roomTypesStmt = $db->query('SELECT id, name FROM room_types ORDER BY name ASC');
$roomTypes     = $roomTypesStmt->fetchAll();

$sql = "SELECT r.id, r.room_number, r.room_type_id, r.floor, r.status,
         rt.name AS room_type_name, rt.price_per_night, rt.capacity, rt.image
        FROM rooms r
        INNER JOIN room_types rt ON rt.id = r.room_type_id
        WHERE (r.room_number LIKE ? OR rt.name LIKE ?)";
$params = ['%' . $search . '%', '%' . $search . '%'];

if ($status !== '' && in_array($status, ['available', 'booked', 'maintenance'], true)) {
    $sql .= ' AND r.status = ?';
    $params[] = $status;
}

if ($roomTypeId > 0) {
    $sql .= ' AND r.room_type_id = ?';
    $params[] = $roomTypeId;
}

$sql .= ' ORDER BY r.room_number ASC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$statusCount = [
  'available' => 0,
  'booked' => 0,
  'maintenance' => 0,
];
foreach ($rooms as $item) {
  $key = $item['status'] ?? '';
  if (isset($statusCount[$key])) {
    $statusCount[$key]++;
  }
}

function roomStatusBadge(string $status): array {
    switch ($status) {
        case 'booked':
            return ['badge-warning', 'Đã đặt'];
        case 'maintenance':
            return ['badge-error', 'Bảo trì'];
        default:
            return ['badge-success', 'Sẵn sàng'];
    }
}

function formatVnd(float $amount): string {
    return number_format($amount, 0, ',', '.') . ' đ';
}

function roomTypeImageSrc(?string $image): string {
  $image = trim((string)$image);
  if ($image === '') {
    return '';
  }

  if (preg_match('#^https?://#i', $image)) {
    return $image;
  }

  if (substr($image, 0, 1) === '/') {
    $basePath = (string)(parse_url(BASE_URL, PHP_URL_PATH) ?? '');
    if ($basePath !== '' && strpos($image, $basePath . '/') === 0) {
      $origin = substr(BASE_URL, 0, -strlen($basePath));
      return $origin . $image;
    }

    return BASE_URL . $image;
  }

  return BASE_URL . '/assets/rooms/' . ltrim($image, '/');
}
?>

<div class="admin-layout">
  <?php require_once '../includes/admin_sidebar.php'; ?>

  <div class="admin-content">
    <div class="admin-content__header">
      <div>
        <h1 class="admin-content__title">Quản lý phòng</h1>
        <p class="text-sm text-muted" style="margin-top:4px">
          Có <?= count($rooms) ?> phòng phù hợp với bộ lọc hiện tại
        </p>
      </div>
      <button type="button" class="btn btn-primary" onclick="openModal('modal-add-room')">
        <i data-lucide="plus" width="16" height="16"></i>
        Thêm phòng
      </button>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error" role="alert" style="display:flex;gap:var(--space-2);align-items:flex-start">
        <i data-lucide="alert-circle" width="16" height="16" style="flex-shrink:0"></i>
        <?= $error ?>
      </div>
    <?php endif; ?>

    <?php if ($msg): ?>
      <div class="alert alert-success" role="status" style="display:flex;gap:var(--space-2);align-items:flex-start">
        <i data-lucide="check-circle" width="16" height="16" style="flex-shrink:0"></i>
        <?= $msg ?>
      </div>
    <?php endif; ?>

    <div class="rooms-admin-summary" style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:var(--space-3);margin-bottom:var(--space-5)">
      <div class="card">
        <div class="card-body" style="padding:var(--space-4)">
          <p class="text-xs text-muted">Tổng số phòng</p>
          <p style="font-size:var(--text-lg);font-weight:700;margin-top:4px"><?= count($rooms) ?></p>
        </div>
      </div>
      <div class="card">
        <div class="card-body" style="padding:var(--space-4)">
          <p class="text-xs text-muted">Sẵn sàng</p>
          <p style="font-size:var(--text-lg);font-weight:700;margin-top:4px"><?= (int)$statusCount['available'] ?></p>
        </div>
      </div>
      <div class="card">
        <div class="card-body" style="padding:var(--space-4)">
          <p class="text-xs text-muted">Đã đặt</p>
          <p style="font-size:var(--text-lg);font-weight:700;margin-top:4px"><?= (int)$statusCount['booked'] ?></p>
        </div>
      </div>
      <div class="card">
        <div class="card-body" style="padding:var(--space-4)">
          <p class="text-xs text-muted">Bảo trì</p>
          <p style="font-size:var(--text-lg);font-weight:700;margin-top:4px"><?= (int)$statusCount['maintenance'] ?></p>
        </div>
      </div>
    </div>

    <form method="GET" action="" style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:flex-end;margin-bottom:var(--space-5)">
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:220px">
        <label class="form-label" for="search">Tìm theo số phòng / loại phòng</label>
        <input class="form-input" type="search" id="search" name="search"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Ví dụ: 101, Suite...">
      </div>

      <div class="form-group" style="margin-bottom:0;min-width:160px">
        <label class="form-label" for="room_type_id">Loại phòng</label>
        <select class="form-select" id="room_type_id" name="room_type_id">
          <option value="0">Tất cả</option>
          <?php foreach ($roomTypes as $type): ?>
            <option value="<?= (int)$type['id'] ?>" <?= $roomTypeId === (int)$type['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($type['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" style="margin-bottom:0;min-width:160px">
        <label class="form-label" for="status">Trạng thái</label>
        <select class="form-select" id="status" name="status">
          <option value="">Tất cả</option>
          <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Sẵn sàng</option>
          <option value="booked" <?= $status === 'booked' ? 'selected' : '' ?>>Đã đặt</option>
          <option value="maintenance" <?= $status === 'maintenance' ? 'selected' : '' ?>>Bảo trì</option>
        </select>
      </div>

      <div style="display:flex;gap:var(--space-2)">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="search" width="15" height="15"></i>
          Lọc
        </button>
        <?php if ($search !== '' || $status !== '' || $roomTypeId > 0): ?>
          <a class="btn btn-secondary" href="?">
            <i data-lucide="x" width="15" height="15"></i>
            Xóa lọc
          </a>
        <?php endif; ?>
      </div>
    </form>

    <div class="table-wrapper">
      <table class="table" aria-label="Danh sách phòng">
        <thead>
          <tr>
            <th style="width:44px">#</th>
            <th>Số phòng</th>
            <th>Ảnh</th>
            <th>Loại phòng</th>
            <th>Sức chứa</th>
            <th>Tầng</th>
            <th>Giá/đêm</th>
            <th>Trạng thái</th>
            <th style="width:132px;text-align:center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rooms)): ?>
            <tr>
              <td colspan="9" style="text-align:center;padding:var(--space-10);color:var(--color-text-faint)">
                Không có phòng nào
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($rooms as $i => $room): ?>
              <?php [$badgeClass, $badgeText] = roomStatusBadge($room['status']); ?>
              <tr>
                <td class="text-muted text-xs"><?= $i + 1 ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($room['room_number']) ?></td>
                <td>
                  <?php
                    $imgSrc = roomTypeImageSrc($room['image'] ?? '');
                    $fallbackDisplay = $imgSrc !== '' ? 'none' : 'inline-flex';
                  ?>
                  <?php if ($imgSrc !== ''): ?>
                    <img src="<?= htmlspecialchars($imgSrc) ?>"
                         alt="<?= htmlspecialchars($room['room_type_name']) ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                         style="width:56px;height:38px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--color-border)">
                  <?php endif; ?>
                  <span style="width:56px;height:38px;display:<?= $fallbackDisplay ?>;align-items:center;justify-content:center;
                               border-radius:var(--radius-sm);border:1px solid var(--color-border);
                               background:var(--color-surface-offset);color:var(--color-text-faint);font-size:var(--text-xs)">
                    N/A
                  </span>
                </td>
                <td>
                  <a href="<?= BASE_URL ?>/pages/room_detail.php?room_type_id=<?= (int)$room['room_type_id'] ?>">
                    <?= htmlspecialchars($room['room_type_name']) ?>
                  </a>
                </td>
                <td><?= (int)$room['capacity'] ?> người</td>
                <td>Tầng <?= (int)$room['floor'] ?></td>
                <td style="font-weight:600;color:var(--color-primary)"><?= formatVnd((float)$room['price_per_night']) ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span></td>
                <td>
                  <div style="display:flex;gap:var(--space-1);justify-content:center">
                    <button type="button" class="btn btn-secondary btn-sm"
                            onclick="openEditRoom(this)"
                            data-id="<?= (int)$room['id'] ?>"
                            data-room-number="<?= htmlspecialchars($room['room_number'], ENT_QUOTES) ?>"
                            data-room-type-id="<?= (int)$room['room_type_id'] ?>"
                            data-floor="<?= (int)$room['floor'] ?>"
                            data-status="<?= htmlspecialchars($room['status'], ENT_QUOTES) ?>"
                            aria-label="Sửa phòng <?= htmlspecialchars($room['room_number']) ?>">
                      <i data-lucide="pencil" width="13" height="13"></i>
                    </button>

                    <form method="POST" action="<?= BASE_URL ?>/api/rooms.php?action=delete"
                          onsubmit="return confirm('Xóa phòng <?= htmlspecialchars(addslashes($room['room_number'])) ?>?')"
                          style="margin:0">
                      <input type="hidden" name="id" value="<?= (int)$room['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm"
                              aria-label="Xóa phòng <?= htmlspecialchars($room['room_number']) ?>">
                        <i data-lucide="trash-2" width="13" height="13"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div id="modal-add-room" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-add-room-title">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title" id="modal-add-room-title">Thêm phòng</h2>
      <button type="button" class="btn-icon" onclick="closeModal('modal-add-room')" aria-label="Đóng">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/api/rooms.php?action=add">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="add-room-number">Số phòng *</label>
            <input class="form-input" type="text" id="add-room-number" name="room_number"
                   maxlength="10" pattern="[A-Za-z0-9-]{1,10}" required>
            <p class="form-help">Chỉ cho phép chữ, số và dấu gạch ngang</p>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-floor">Tầng *</label>
            <input class="form-input" type="number" id="add-floor" name="floor" min="0" max="100" value="1" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="add-room-type-id">Loại phòng *</label>
            <select class="form-select" id="add-room-type-id" name="room_type_id" required>
              <option value="">Chọn loại phòng</option>
              <?php foreach ($roomTypes as $type): ?>
                <option value="<?= (int)$type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-status">Trạng thái *</label>
            <select class="form-select" id="add-status" name="status" required>
              <option value="available">Sẵn sàng</option>
              <option value="booked">Đã đặt</option>
              <option value="maintenance">Bảo trì</option>
            </select>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-room')">Hủy</button>
        <button type="submit" class="btn btn-primary">Thêm phòng</button>
      </div>
    </form>
  </div>
</div>

<div id="modal-edit-room" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-edit-room-title">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title" id="modal-edit-room-title">Chỉnh sửa phòng</h2>
      <button type="button" class="btn-icon" onclick="closeModal('modal-edit-room')" aria-label="Đóng">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/api/rooms.php?action=update">
      <input type="hidden" id="edit-id" name="id" value="">

      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="edit-room-number">Số phòng *</label>
            <input class="form-input" type="text" id="edit-room-number" name="room_number"
                   maxlength="10" pattern="[A-Za-z0-9-]{1,10}" required>
            <p class="form-help">Chỉ cho phép chữ, số và dấu gạch ngang</p>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-floor">Tầng *</label>
            <input class="form-input" type="number" id="edit-floor" name="floor" min="0" max="100" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="edit-room-type-id">Loại phòng *</label>
            <select class="form-select" id="edit-room-type-id" name="room_type_id" required>
              <?php foreach ($roomTypes as $type): ?>
                <option value="<?= (int)$type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-status">Trạng thái *</label>
            <select class="form-select" id="edit-status" name="status" required>
              <option value="available">Sẵn sàng</option>
              <option value="booked">Đã đặt</option>
              <option value="maintenance">Bảo trì</option>
            </select>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-room')">Hủy</button>
        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
  var modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.add('open');
  document.body.style.overflow = 'hidden';

  var firstInput = modal.querySelector('input, select, textarea, button');
  if (firstInput) firstInput.focus();
}

function closeModal(id) {
  var modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.remove('open');
  document.body.style.overflow = '';
}

function openEditRoom(button) {
  document.getElementById('edit-id').value = button.dataset.id;
  document.getElementById('edit-room-number').value = button.dataset.roomNumber;
  document.getElementById('edit-room-type-id').value = button.dataset.roomTypeId;
  document.getElementById('edit-floor').value = button.dataset.floor;
  document.getElementById('edit-status').value = button.dataset.status;
  openModal('modal-edit-room');
}

function normalizeRoomNumber(input) {
  if (!input) return;
  input.value = input.value.toUpperCase().replace(/[^A-Z0-9-]/g, '').slice(0, 10);
}

document.getElementById('add-room-number').addEventListener('input', function (e) {
  normalizeRoomNumber(e.target);
});

document.getElementById('edit-room-number').addEventListener('input', function (e) {
  normalizeRoomNumber(e.target);
});

document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
  overlay.addEventListener('click', function (event) {
    if (event.target === overlay) {
      closeModal(overlay.id);
    }
  });
});

document.addEventListener('keydown', function (event) {
  if (event.key !== 'Escape') return;
  document.querySelectorAll('.modal-overlay.open').forEach(function (modal) {
    closeModal(modal.id);
  });
});
</script>

<style>
@media (max-width: 980px) {
  .rooms-admin-summary { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; }
}
@media (max-width: 560px) {
  .rooms-admin-summary { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once '../includes/footer.php'; ?>
