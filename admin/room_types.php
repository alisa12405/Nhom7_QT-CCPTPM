<?php
/**
 * admin/room_types.php — Quản lý loại phòng
 */
/**
 * admin/room_types.php — Quản lý loại phòng
 */
/**
 * admin/room_types.php — Quản lý loại phòng
 */
$pageTitle = 'Quản lý loại phòng — LuxStay Admin';

require_once '../includes/auth_check.php';
requireAdmin();

require_once '../api/config/db.php';
require_once '../includes/header.php';

$db = (new Database())->getConnection();

$search = trim($_GET['search'] ?? '');
$error  = htmlspecialchars(strip_tags($_GET['error'] ?? ''));
$msg    = htmlspecialchars(strip_tags($_GET['msg'] ?? ''));

$sql = "SELECT rt.id, rt.name, rt.description, rt.price_per_night, rt.capacity, rt.image, rt.created_at,
               COUNT(r.id) AS room_count
        FROM room_types rt
        LEFT JOIN rooms r ON r.room_type_id = rt.id
        WHERE (rt.name LIKE ? OR COALESCE(rt.description, '') LIKE ?)
        GROUP BY rt.id
        ORDER BY rt.created_at DESC, rt.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute(['%' . $search . '%', '%' . $search . '%']);
$roomTypes = $stmt->fetchAll();

$totalRoomTypes = count($roomTypes);
$totalRooms = 0;
$sumPrice = 0.0;
foreach ($roomTypes as $item) {
  $totalRooms += (int)$item['room_count'];
  $sumPrice += (float)$item['price_per_night'];
}
$avgPrice = $totalRoomTypes > 0 ? $sumPrice / $totalRoomTypes : 0;

function vnd(float $amount): string {
    return number_format($amount, 0, ',', '.') . ' đ';
}

function shortDescription(?string $text, int $limit = 90): string {
    $text = trim((string)$text);
    if ($text === '') {
        return '—';
    }

    if (mb_strlen($text, 'UTF-8') <= $limit) {
        return htmlspecialchars($text);
    }

    return htmlspecialchars(mb_substr($text, 0, $limit - 1, 'UTF-8') . '…');
}

  function roomImageSrc(?string $image): string {
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
        <h1 class="admin-content__title">Quản lý loại phòng</h1>
        <p class="text-sm text-muted" style="margin-top:4px">
          Có <?= count($roomTypes) ?> loại phòng phù hợp với bộ lọc hiện tại
        </p>
      </div>
      <button type="button" class="btn btn-primary" onclick="openModal('modal-add-room-type')">
        <i data-lucide="plus" width="16" height="16"></i>
        Thêm loại phòng
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

    <div class="room-types-summary" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:var(--space-3);margin-bottom:var(--space-5)">
      <div class="card">
        <div class="card-body" style="padding:var(--space-4)">
          <p class="text-xs text-muted">Loại phòng</p>
          <p style="font-size:var(--text-lg);font-weight:700;margin-top:4px"><?= (int)$totalRoomTypes ?></p>
        </div>
      </div>
      <div class="card">
        <div class="card-body" style="padding:var(--space-4)">
          <p class="text-xs text-muted">Tổng phòng thực tế</p>
          <p style="font-size:var(--text-lg);font-weight:700;margin-top:4px"><?= (int)$totalRooms ?></p>
        </div>
      </div>
      <div class="card">
        <div class="card-body" style="padding:var(--space-4)">
          <p class="text-xs text-muted">Giá trung bình / đêm</p>
          <p style="font-size:var(--text-lg);font-weight:700;margin-top:4px"><?= vnd((float)$avgPrice) ?></p>
        </div>
      </div>
    </div>

    <form method="GET" action="" style="display:flex;gap:var(--space-3);flex-wrap:wrap;align-items:flex-end;margin-bottom:var(--space-5)">
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:220px">
        <label class="form-label" for="search">Tìm theo tên hoặc mô tả</label>
        <input class="form-input" type="search" id="search" name="search"
               value="<?= htmlspecialchars($search) ?>"
               placeholder="Ví dụ: Deluxe, view biển...">
      </div>
      <div style="display:flex;gap:var(--space-2)">
        <button type="submit" class="btn btn-primary">
          <i data-lucide="search" width="15" height="15"></i>
          Tìm
        </button>
        <?php if ($search !== ''): ?>
          <a class="btn btn-secondary" href="?">
            <i data-lucide="x" width="15" height="15"></i>
            Xóa lọc
          </a>
        <?php endif; ?>
      </div>
    </form>

    <div class="table-wrapper">
      <table class="table" aria-label="Danh sách loại phòng">
        <thead>
          <tr>
            <th style="width:44px">#</th>
            <th>Loại phòng</th>
            <th>Sức chứa</th>
            <th>Giá/đêm</th>
            <th>Số phòng</th>
            <th>Mô tả</th>
            <th style="width:132px;text-align:center">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($roomTypes)): ?>
            <tr>
              <td colspan="7" style="text-align:center;padding:var(--space-10);color:var(--color-text-faint)">
                Không có loại phòng nào
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($roomTypes as $i => $rt): ?>
              <tr>
                <td class="text-muted text-xs"><?= $i + 1 ?></td>
                <td>
                  <?php
                    $imgSrc = roomImageSrc($rt['image'] ?? '');
                    $fallbackDisplay = $imgSrc !== '' ? 'none' : 'inline-flex';
                  ?>
                  <div style="display:flex;align-items:center;gap:var(--space-3)">
                    <?php if ($imgSrc !== ''): ?>
                      <img src="<?= htmlspecialchars($imgSrc) ?>"
                           alt="<?= htmlspecialchars($rt['name']) ?>"
                           onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';"
                           style="width:50px;height:34px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--color-border)">
                    <?php endif; ?>
                    <span style="width:50px;height:34px;display:<?= $fallbackDisplay ?>;align-items:center;justify-content:center;
                                 border-radius:var(--radius-sm);border:1px solid var(--color-border);
                                 background:var(--color-surface-offset);color:var(--color-text-faint);font-size:var(--text-xs)">
                      N/A
                    </span>
                    <div>
                      <p style="font-weight:600"><?= htmlspecialchars($rt['name']) ?></p>
                      <p class="text-xs text-muted">ID: <?= (int)$rt['id'] ?></p>
                    </div>
                  </div>
                </td>
                <td><?= (int)$rt['capacity'] ?> người</td>
                <td style="font-weight:600;color:var(--color-primary)"><?= vnd((float)$rt['price_per_night']) ?></td>
                <td><?= (int)$rt['room_count'] ?></td>
                <td style="white-space:normal"><?= shortDescription($rt['description']) ?></td>
                <td>
                  <div style="display:flex;gap:var(--space-1);justify-content:center">
                    <button type="button" class="btn btn-secondary btn-sm"
                            onclick="openEditRoomType(this)"
                            data-id="<?= (int)$rt['id'] ?>"
                            data-name="<?= htmlspecialchars($rt['name'], ENT_QUOTES) ?>"
                            data-description="<?= htmlspecialchars((string)$rt['description'], ENT_QUOTES) ?>"
                            data-price="<?= htmlspecialchars((string)$rt['price_per_night'], ENT_QUOTES) ?>"
                            data-capacity="<?= (int)$rt['capacity'] ?>"
                            data-image="<?= htmlspecialchars((string)$rt['image'], ENT_QUOTES) ?>"
                            aria-label="Sửa loại phòng <?= htmlspecialchars($rt['name']) ?>">
                      <i data-lucide="pencil" width="13" height="13"></i>
                    </button>

                    <form method="POST" action="<?= BASE_URL ?>/api/room_types.php?action=delete"
                          onsubmit="return confirm('Xóa loại phòng <?= htmlspecialchars(addslashes($rt['name'])) ?>?\nDữ liệu phòng liên quan cũng có thể bị ảnh hưởng.')"
                          style="margin:0">
                      <input type="hidden" name="id" value="<?= (int)$rt['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm"
                              aria-label="Xóa loại phòng <?= htmlspecialchars($rt['name']) ?>">
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

<div id="modal-add-room-type" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-add-room-type-title">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title" id="modal-add-room-type-title">Thêm loại phòng</h2>
      <button type="button" class="btn-icon" onclick="closeModal('modal-add-room-type')" aria-label="Đóng">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/api/room_types.php?action=add" enctype="multipart/form-data">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="add-name">Tên loại phòng *</label>
          <input class="form-input" type="text" id="add-name" name="name" maxlength="100" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="add-capacity">Sức chứa *</label>
            <input class="form-input" type="number" id="add-capacity" name="capacity" min="1" max="20" value="2" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-price">Giá / đêm (VND) *</label>
            <input class="form-input" type="number" id="add-price" name="price_per_night" min="1" step="1000" required>
          </div>
        </div>
        <div class="form-group">
           <label class="form-label" for="add-image-file">Ảnh đại diện</label>
           <input class="form-input" type="file" id="add-image-file" name="image_file"
             accept="image/jpeg,image/png,image/webp,image/gif">
           <p class="form-help">Chỉ nhận JPG, PNG, WEBP, GIF (tối đa 5MB)</p>
          <img id="add-image-preview" alt="Preview ảnh loại phòng"
               style="display:none;width:100%;max-height:160px;object-fit:cover;margin-top:var(--space-2);border:1px solid var(--color-border);border-radius:var(--radius-md)">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" for="add-description">Mô tả</label>
          <textarea class="form-textarea" id="add-description" name="description" rows="4" placeholder="Mô tả chi tiết loại phòng..."></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-room-type')">Hủy</button>
        <button type="submit" class="btn btn-primary">Thêm loại phòng</button>
      </div>
    </form>
  </div>
</div>

<div id="modal-edit-room-type" class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modal-edit-room-type-title">
  <div class="modal">
    <div class="modal-header">
      <h2 class="modal-title" id="modal-edit-room-type-title">Chỉnh sửa loại phòng</h2>
      <button type="button" class="btn-icon" onclick="closeModal('modal-edit-room-type')" aria-label="Đóng">
        <i data-lucide="x" width="18" height="18"></i>
      </button>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/api/room_types.php?action=update" enctype="multipart/form-data">
      <input type="hidden" id="edit-id" name="id" value="">
      <input type="hidden" id="edit-current-image" name="current_image" value="">

      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="edit-name">Tên loại phòng *</label>
          <input class="form-input" type="text" id="edit-name" name="name" maxlength="100" required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="edit-capacity">Sức chứa *</label>
            <input class="form-input" type="number" id="edit-capacity" name="capacity" min="1" max="20" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-price">Giá / đêm (VND) *</label>
            <input class="form-input" type="number" id="edit-price" name="price_per_night" min="1" step="1000" required>
          </div>
        </div>
        <div class="form-group">
           <label class="form-label" for="edit-image-file">Ảnh đại diện</label>
           <input class="form-input" type="file" id="edit-image-file" name="image_file"
             accept="image/jpeg,image/png,image/webp,image/gif">
           <p class="form-help">Để trống nếu muốn giữ ảnh hiện tại</p>
          <img id="edit-image-preview" alt="Preview ảnh loại phòng"
               style="display:none;width:100%;max-height:160px;object-fit:cover;margin-top:var(--space-2);border:1px solid var(--color-border);border-radius:var(--radius-md)">
        </div>
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" for="edit-description">Mô tả</label>
          <textarea class="form-textarea" id="edit-description" name="description" rows="4"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-room-type')">Hủy</button>
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
}

function closeModal(id) {
  var modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.remove('open');
  document.body.style.overflow = '';
}

function openEditRoomType(button) {
  document.getElementById('edit-id').value = button.dataset.id;
  document.getElementById('edit-name').value = button.dataset.name;
  document.getElementById('edit-description').value = button.dataset.description;
  document.getElementById('edit-price').value = button.dataset.price;
  document.getElementById('edit-capacity').value = button.dataset.capacity;
  document.getElementById('edit-current-image').value = button.dataset.image || '';
  document.getElementById('edit-image-file').value = '';
  updateImagePreviewFromFile('edit-image-file', 'edit-image-preview', button.dataset.image || '');
  openModal('modal-edit-room-type');
}

function resolveRoomImageSrc(value) {
  var baseUrl = '<?= BASE_URL ?>';
  var basePath = '<?= (string)(parse_url(BASE_URL, PHP_URL_PATH) ?? '') ?>';
  var baseOrigin = basePath ? baseUrl.slice(0, -basePath.length) : baseUrl;
  var image = (value || '').trim();
  if (!image) return '';

  if (/^https?:\/\//i.test(image)) {
    return image;
  }

  if (image.charAt(0) === '/') {
    if (basePath && image.indexOf(basePath + '/') === 0) {
      return baseOrigin + image;
    }
    return baseUrl + image;
  }

  return baseUrl + '/assets/rooms/' + image;
}

function updateImagePreviewFromFile(inputId, previewId, fallbackImage) {
  var input = document.getElementById(inputId);
  var preview = document.getElementById(previewId);
  if (!preview) return;

  if (input && input.files && input.files.length > 0) {
    var file = input.files[0];
    preview.src = URL.createObjectURL(file);
    preview.style.display = 'block';
    preview.onload = function () {
      URL.revokeObjectURL(preview.src);
    };
    return;
  }

  var fallbackSrc = resolveRoomImageSrc(fallbackImage || '');
  if (fallbackSrc) {
    preview.src = fallbackSrc;
    preview.style.display = 'block';
    return;
  }

  preview.style.display = 'none';
  preview.removeAttribute('src');
}

document.getElementById('add-image-file').addEventListener('change', function () {
  updateImagePreviewFromFile('add-image-file', 'add-image-preview', '');
});

document.getElementById('edit-image-file').addEventListener('change', function () {
  var current = document.getElementById('edit-current-image').value || '';
  updateImagePreviewFromFile('edit-image-file', 'edit-image-preview', current);
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

updateImagePreviewFromFile('add-image-file', 'add-image-preview', '');
</script>

<style>
@media (max-width: 860px) {
  .room-types-summary { grid-template-columns: 1fr !important; }
}
</style>

<?php require_once '../includes/footer.php'; ?>
