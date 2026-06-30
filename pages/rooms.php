<?php
/**
 * pages/rooms.php — Danh sách phòng cho khách
 */
$pageTitle = 'Danh sách phòng — LuxStay';

require_once '../api/config/db.php';
require_once '../includes/header.php';

$db = (new Database())->getConnection();

$roomTypesStmt = $db->query('SELECT id, name FROM room_types ORDER BY name ASC');
$roomTypes     = $roomTypesStmt->fetchAll();

$roomsStmt = $db->query(
    "SELECT r.id, r.room_number, r.room_type_id, r.floor, r.status,
            rt.name AS room_type_name, rt.description AS room_type_description,
            rt.price_per_night, rt.capacity, rt.image
     FROM rooms r
     INNER JOIN room_types rt ON rt.id = r.room_type_id
     WHERE r.status <> 'maintenance'
     ORDER BY rt.price_per_night ASC, r.room_number ASC"
);
$rooms = $roomsStmt->fetchAll();

$summary = [
  'total'     => count($rooms),
  'available' => 0,
  'booked'    => 0,
];

foreach ($rooms as $roomItem) {
  if (($roomItem['status'] ?? '') === 'booked') {
    $summary['booked']++;
  } else {
    $summary['available']++;
  }
}

function roomStatusText(string $status): string {
    return $status === 'booked' ? 'Đang bận' : 'Sẵn sàng';
}

function roomStatusClass(string $status): string {
    return $status === 'booked' ? 'badge-warning' : 'badge-success';
}

function shortText(?string $text, int $limit = 120): string {
    $text = trim((string)$text);
    if ($text === '') {
        return 'Không có mô tả chi tiết cho loại phòng này.';
    }
    if (mb_strlen($text, 'UTF-8') <= $limit) {
        return $text;
    }

    return mb_substr($text, 0, $limit - 1, 'UTF-8') . '…';
}

function resolveRoomImageSrc(?string $image): string {
  $image = str_replace('\\', '/', trim((string)$image));
  if ($image === '') {
    return '';
  }

  if (preg_match('#^https?://#i', $image)) {
    return $image;
  }

  if (strpos($image, '//') === 0) {
    $scheme = (string)(parse_url(BASE_URL, PHP_URL_SCHEME) ?: 'http');
    return $scheme . ':' . $image;
  }

  $basePath = (string)(parse_url(BASE_URL, PHP_URL_PATH) ?? '');
  $basePath = $basePath === '/' ? '' : rtrim($basePath, '/');
  $origin = $basePath !== '' ? substr(BASE_URL, 0, -strlen($basePath)) : BASE_URL;

  while (strpos($image, './') === 0) {
    $image = substr($image, 2);
  }

  while (strpos($image, '../') === 0) {
    $image = substr($image, 3);
  }

  if (substr($image, 0, 1) === '/') {
    if ($basePath !== '' && strpos($image, $basePath . '/') === 0) {
      return $origin . $image;
    }

    return BASE_URL . $image;
  }

  $clean = ltrim($image, '/');
  $basePathTrim = trim($basePath, '/');

  if ($basePathTrim !== '' && strpos($clean, $basePathTrim . '/') === 0) {
    return $origin . '/' . $clean;
  }

  if (strpos($clean, 'assets/') === 0) {
    return BASE_URL . '/' . $clean;
  }

  if (strpos($clean, '/') !== false) {
    return BASE_URL . '/' . $clean;
  }

  return BASE_URL . '/assets/rooms/' . $clean;
}

function roomThumbStyle(?string $image): string {
  $base = "linear-gradient(135deg, oklch(from var(--color-primary) l c h / 0.16), transparent)";
  $resolved = resolveRoomImageSrc($image);

  if ($resolved === '') {
    return 'background-image:' . $base;
  }

  return 'background-image:' . $base . ", url('" . htmlspecialchars($resolved, ENT_QUOTES) . "')";
}

$roomsJson = json_encode($rooms, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>

<section class="rooms-hero">
  <div class="container">
    <p class="rooms-hero__eyebrow">Khám phá phòng nghỉ</p>
    <h1 class="rooms-hero__title">Tìm căn phòng phù hợp cho kỳ nghỉ của bạn</h1>
    <p class="rooms-hero__desc">
      Chọn ngày nhận và trả phòng để xem phòng còn trống theo thời gian thực.
    </p>
  </div>
</section>

<section class="container" style="margin-top:calc(-1 * var(--space-8));padding-bottom:var(--space-12)">
  <div class="rooms-summary-grid">
    <div class="rooms-summary-item">
      <p>Tổng phòng hiển thị</p>
      <strong><?= (int)$summary['total'] ?></strong>
    </div>
    <div class="rooms-summary-item">
      <p>Sẵn sàng đặt</p>
      <strong><?= (int)$summary['available'] ?></strong>
    </div>
    <div class="rooms-summary-item">
      <p>Đang bận</p>
      <strong><?= (int)$summary['booked'] ?></strong>
    </div>
  </div>

  <div class="card" style="margin-bottom:var(--space-8)">
    <div class="card-body" style="padding-bottom:var(--space-5)">
      <form id="search-form" class="rooms-search-grid">
        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" for="check_in">Ngày nhận phòng</label>
          <input class="form-input" type="date" id="check_in" name="check_in" min="<?= date('Y-m-d') ?>">
        </div>

        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" for="check_out">Ngày trả phòng</label>
          <input class="form-input" type="date" id="check_out" name="check_out" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
        </div>

        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" for="guests">Số khách</label>
          <select class="form-select" id="guests" name="guests">
            <?php for ($i = 1; $i <= 6; $i++): ?>
              <option value="<?= $i ?>" <?= $i === 2 ? 'selected' : '' ?>><?= $i ?> người</option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
          <label class="form-label" for="room_type_id">Loại phòng</label>
          <select class="form-select" id="room_type_id" name="room_type_id">
            <option value="0">Tất cả loại phòng</option>
            <?php foreach ($roomTypes as $type): ?>
              <option value="<?= (int)$type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="rooms-search-actions">
          <button type="submit" class="btn btn-primary" id="search-submit-btn">
            <i data-lucide="search" width="15" height="15"></i>
            Tìm phòng trống
          </button>
          <button type="button" class="btn btn-secondary" id="reset-search-btn">Xem tất cả</button>
        </div>
      </form>

      <p id="search-message" class="text-sm text-muted" style="margin-top:var(--space-4)">
        Hiển thị <?= count($rooms) ?> phòng hiện có.
      </p>
    </div>
  </div>

  <div id="rooms-grid" class="rooms-grid">
    <?php if (empty($rooms)): ?>
      <div class="card" style="grid-column:1/-1">
        <div class="card-body" style="text-align:center;padding:var(--space-10)">
          Hiện chưa có phòng nào để hiển thị.
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($rooms as $room): ?>
        <article class="card room-card">
          <div class="room-card__thumb" style="<?= roomThumbStyle($room['image'] ?? null) ?>">
            <span class="badge <?= roomStatusClass($room['status']) ?>"><?= roomStatusText($room['status']) ?></span>
          </div>

          <div class="card-body room-card__body">
            <div class="room-card__head">
              <h2><?= htmlspecialchars($room['room_type_name']) ?></h2>
              <p>Phòng <?= htmlspecialchars($room['room_number']) ?> · Tầng <?= (int)$room['floor'] ?></p>
            </div>

            <p class="room-card__desc"><?= htmlspecialchars(shortText($room['room_type_description'])) ?></p>

            <div class="room-card__meta">
              <span><i data-lucide="users" width="14" height="14"></i> <?= (int)$room['capacity'] ?> khách</span>
              <span><i data-lucide="badge-dollar-sign" width="14" height="14"></i> <?= number_format((float)$room['price_per_night'], 0, ',', '.') ?> đ / đêm</span>
            </div>

            <div class="room-card__actions">
              <a class="btn btn-secondary btn-sm" href="<?= BASE_URL ?>/pages/room_detail.php?room_type_id=<?= (int)$room['room_type_id'] ?>&id=<?= (int)$room['id'] ?>">Xem chi tiết</a>
              <?php if ($room['status'] === 'available'): ?>
                <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/pages/booking.php?room_id=<?= (int)$room['id'] ?>&room_type_id=<?= (int)$room['room_type_id'] ?>">Đặt phòng</a>
              <?php else: ?>
                <button class="btn btn-secondary btn-sm" disabled>Đang bận</button>
              <?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<style>
.rooms-hero {
  padding: var(--space-12) 0 calc(var(--space-12) + var(--space-8));
  background:
    radial-gradient(circle at 10% 20%, oklch(from var(--color-primary) l c h / 0.18), transparent 45%),
    linear-gradient(170deg, var(--color-surface-offset), var(--color-bg));
  border-bottom: 1px solid var(--color-divider);
}
.rooms-hero__eyebrow {
  font-size: var(--text-xs);
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--color-text-muted);
  margin-bottom: var(--space-2);
}
.rooms-hero__title {
  font-family: var(--font-display);
  font-size: clamp(2rem, 1.7rem + 1vw, 3rem);
  font-style: italic;
  margin-bottom: var(--space-3);
}
.rooms-hero__desc {
  color: var(--color-text-muted);
  max-width: 62ch;
}
.rooms-search-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--space-4);
}
.rooms-summary-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--space-4);
  margin-bottom: var(--space-4);
}
.rooms-summary-item {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  padding: var(--space-4);
  background: var(--color-surface-offset);
}
.rooms-summary-item p {
  margin: 0;
  font-size: var(--text-xs);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--color-text-muted);
}
.rooms-summary-item strong {
  display: inline-block;
  margin-top: var(--space-2);
  font-size: var(--text-lg);
}
.rooms-search-actions {
  grid-column: 1 / -1;
  display: flex;
  gap: var(--space-2);
  justify-content: flex-end;
}
.rooms-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--space-5);
}
.room-card {
  overflow: hidden;
}
.room-card__thumb {
  height: 170px;
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: flex-start;
  justify-content: flex-end;
  padding: var(--space-3);
  border-bottom: 1px solid var(--color-divider);
}
.room-card__body {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}
.room-card__head h2 {
  font-size: var(--text-lg);
  margin-bottom: 2px;
}
.room-card__head p {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
}
.room-card__desc {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  min-height: 46px;
}
.room-card__meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  color: var(--color-text-muted);
  font-size: var(--text-sm);
}
.room-card__meta span {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
}
.room-card__actions {
  display: flex;
  gap: var(--space-2);
}

@media (max-width: 1024px) {
  .rooms-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
  .rooms-summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
  .rooms-search-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .rooms-search-actions { justify-content: stretch; }
  .rooms-search-actions .btn { flex: 1; }
}

@media (max-width: 560px) {
  .rooms-summary-grid { grid-template-columns: 1fr; }
  .rooms-grid { grid-template-columns: 1fr; }
  .rooms-search-grid { grid-template-columns: 1fr; }
  .room-card__actions { flex-wrap: wrap; }
  .room-card__actions .btn { flex: 1; }
}
</style>

<script>
(function () {
  var baseUrl = <?= json_encode(BASE_URL, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  var basePath = <?= json_encode((string)(parse_url(BASE_URL, PHP_URL_PATH) ?? ''), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
  var baseOrigin = basePath ? baseUrl.slice(0, -basePath.length) : baseUrl;
  var initialRooms = <?= $roomsJson ?: '[]' ?>;
  var form = document.getElementById('search-form');
  var grid = document.getElementById('rooms-grid');
  var message = document.getElementById('search-message');
  var resetButton = document.getElementById('reset-search-btn');
  var submitButton = document.getElementById('search-submit-btn');
  var checkInInput = document.getElementById('check_in');
  var checkOutInput = document.getElementById('check_out');

  function escapeHtml(input) {
    return String(input)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function statusInfo(status) {
    if (status === 'booked') {
      return { className: 'badge-warning', text: 'Đang bận' };
    }
    return { className: 'badge-success', text: 'Sẵn sàng' };
  }

  function shortText(text) {
    if (!text) return 'Không có mô tả chi tiết cho loại phòng này.';
    if (text.length <= 120) return text;
    return text.slice(0, 119) + '…';
  }

  function resolveRoomImageSrc(value) {
    var image = (value || '').trim();
    if (!image) return '';

    var normalized = image.replace(/\\/g, '/');

    if (/^https?:\/\//i.test(normalized)) {
      return normalized;
    }

     if (/^\/\//.test(normalized)) {
      var scheme = baseUrl.indexOf('https://') === 0 ? 'https:' : 'http:';
      return scheme + normalized;
    }

    var clean = normalized.replace(/^\.\/+/, '');
    while (clean.indexOf('../') === 0) {
      clean = clean.slice(3);
    }

    if (normalized.charAt(0) === '/') {
      if (basePath && normalized.indexOf(basePath + '/') === 0) {
        return baseOrigin + normalized;
      }

      return baseUrl + normalized;
    }

    var basePathTrim = (basePath || '').replace(/^\/+|\/+$/g, '');
    if (basePathTrim && clean.indexOf(basePathTrim + '/') === 0) {
      return baseOrigin + '/' + clean;
    }

    if (clean.indexOf('assets/') === 0) {
      return baseUrl + '/' + clean;
    }

    if (clean.indexOf('/') !== -1) {
      return baseUrl + '/' + clean;
    }

    return baseUrl + '/assets/rooms/' + clean.replace(/^\/+/, '');
  }

  function parseDate(value) {
    var parts = value.split('-');
    if (parts.length !== 3) return null;
    var d = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    return isNaN(d.getTime()) ? null : d;
  }

  function formatDate(date) {
    var y = date.getFullYear();
    var m = String(date.getMonth() + 1).padStart(2, '0');
    var d = String(date.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
  }

  function updateCheckOutMin() {
    var inDate = parseDate(checkInInput.value);
    if (!inDate) return;
    inDate.setDate(inDate.getDate() + 1);
    var minOut = formatDate(inDate);
    checkOutInput.min = minOut;

    if (checkOutInput.value && checkOutInput.value < minOut) {
      checkOutInput.value = minOut;
    }
  }

  checkInInput.addEventListener('change', updateCheckOutMin);

  function renderRooms(rooms) {
    if (!rooms.length) {
      grid.innerHTML = '<div class="card" style="grid-column:1/-1"><div class="card-body" style="text-align:center;padding:var(--space-10)">Không tìm thấy phòng phù hợp.</div></div>';
      return;
    }

    var html = rooms.map(function (room) {
      var st = statusInfo(room.status);
      var image = resolveRoomImageSrc(room.image || '');
      var escapedImage = image ? escapeHtml(image) : '';
      var price = Number(room.price_per_night || 0).toLocaleString('vi-VN');
      var detailsUrl = baseUrl + '/pages/room_detail.php?room_type_id=' + encodeURIComponent(room.room_type_id) + '&id=' + encodeURIComponent(room.id);
      var bookingUrl = baseUrl + '/pages/booking.php?room_id=' + encodeURIComponent(room.id) + '&room_type_id=' + encodeURIComponent(room.room_type_id);
      var bgStyle = escapedImage
        ? 'background-image:linear-gradient(135deg, oklch(from var(--color-primary) l c h / 0.16), transparent), url(\'' + escapedImage + '\')'
        : 'background-image:linear-gradient(135deg, oklch(from var(--color-primary) l c h / 0.16), transparent)';

      var actionHtml = room.status === 'available'
        ? '<a class="btn btn-primary btn-sm" href="' + bookingUrl + '">Đặt phòng</a>'
        : '<button class="btn btn-secondary btn-sm" disabled>Đang bận</button>';

      return [
        '<article class="card room-card">',
          '<div class="room-card__thumb" style="' + bgStyle + '">',
            '<span class="badge ' + st.className + '">' + st.text + '</span>',
          '</div>',
          '<div class="card-body room-card__body">',
            '<div class="room-card__head">',
              '<h2>' + escapeHtml(room.room_type_name) + '</h2>',
              '<p>Phòng ' + escapeHtml(room.room_number) + ' · Tầng ' + escapeHtml(room.floor) + '</p>',
            '</div>',
            '<p class="room-card__desc">' + escapeHtml(shortText(room.room_type_description)) + '</p>',
            '<div class="room-card__meta">',
              '<span><i data-lucide="users" width="14" height="14"></i> ' + escapeHtml(room.capacity) + ' khách</span>',
              '<span><i data-lucide="badge-dollar-sign" width="14" height="14"></i> ' + price + ' đ / đêm</span>',
            '</div>',
            '<div class="room-card__actions">',
              '<a class="btn btn-secondary btn-sm" href="' + detailsUrl + '">Xem chi tiết</a>',
              actionHtml,
            '</div>',
          '</div>',
        '</article>'
      ].join('');
    }).join('');

    grid.innerHTML = html;
    if (typeof lucide !== 'undefined') {
      lucide.createIcons();
    }
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    var checkIn = document.getElementById('check_in').value;
    var checkOut = document.getElementById('check_out').value;
    var guests = document.getElementById('guests').value;
    var roomTypeId = document.getElementById('room_type_id').value;

    if (!checkIn || !checkOut) {
      message.textContent = 'Vui lòng chọn đầy đủ ngày nhận và ngày trả phòng.';
      message.style.color = 'var(--color-error)';
      return;
    }

    if (checkIn >= checkOut) {
      message.textContent = 'Ngày trả phòng phải sau ngày nhận phòng.';
      message.style.color = 'var(--color-error)';
      return;
    }

    var params = new URLSearchParams({
      action: 'search_available',
      check_in: checkIn,
      check_out: checkOut,
      guests: guests
    });

    if (roomTypeId && roomTypeId !== '0') {
      params.append('room_type_id', roomTypeId);
    }

    message.textContent = 'Đang tìm phòng trống...';
    message.style.color = 'var(--color-text-muted)';
    submitButton.disabled = true;

    fetch(baseUrl + '/api/rooms.php?' + params.toString(), {
      headers: { 'Accept': 'application/json' }
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { ok: response.ok, data: data };
        });
      })
      .then(function (payload) {
        if (!payload.ok) {
          var err = payload.data && payload.data.error ? payload.data.error : 'Không thể tìm phòng trống.';
          throw new Error(err);
        }

        renderRooms(payload.data);
        message.textContent = 'Tìm thấy ' + payload.data.length + ' phòng trống trong khoảng thời gian đã chọn.';
        message.style.color = 'var(--color-success)';
      })
      .catch(function (error) {
        message.textContent = error.message || 'Đã có lỗi xảy ra khi tìm phòng.';
        message.style.color = 'var(--color-error)';
      })
      .finally(function () {
        submitButton.disabled = false;
      });
  });

  resetButton.addEventListener('click', function () {
    form.reset();
    renderRooms(initialRooms);
    message.textContent = 'Hiển thị ' + initialRooms.length + ' phòng hiện có.';
    message.style.color = 'var(--color-text-muted)';
    checkOutInput.min = '<?= date('Y-m-d', strtotime('+1 day')) ?>';
  });

  updateCheckOutMin();
})();
</script>

<?php require_once '../includes/footer.php'; ?>
