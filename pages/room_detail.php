<?php
/**
 * pages/room_detail.php — Chi tiết loại phòng
 */
require_once '../api/config/db.php';

$db = (new Database())->getConnection();

$roomId     = (int)($_GET['id'] ?? 0);
$roomTypeId = (int)($_GET['room_type_id'] ?? 0);

if ($roomTypeId <= 0 && $roomId > 0) {
    $resolveStmt = $db->prepare('SELECT room_type_id FROM rooms WHERE id = ? LIMIT 1');
    $resolveStmt->execute([$roomId]);
    $resolved = $resolveStmt->fetch();
    $roomTypeId = $resolved ? (int)$resolved['room_type_id'] : 0;
}

$roomType = null;
$roomsOfType = [];
$statusSummary = [
  'available' => 0,
  'booked' => 0,
  'maintenance' => 0,
];

if ($roomTypeId > 0) {
    $typeStmt = $db->prepare(
        "SELECT rt.id, rt.name, rt.description, rt.price_per_night, rt.capacity, rt.image,
                COUNT(r.id) AS total_rooms,
                SUM(CASE WHEN r.status = 'available' THEN 1 ELSE 0 END) AS available_rooms
         FROM room_types rt
         LEFT JOIN rooms r ON r.room_type_id = rt.id
         WHERE rt.id = ?
         GROUP BY rt.id
         LIMIT 1"
    );
    $typeStmt->execute([$roomTypeId]);
    $roomType = $typeStmt->fetch();

    if ($roomType) {
        $roomsStmt = $db->prepare(
            "SELECT id, room_number, floor, status
             FROM rooms
             WHERE room_type_id = ?
             ORDER BY room_number ASC"
        );
        $roomsStmt->execute([$roomTypeId]);
        $roomsOfType = $roomsStmt->fetchAll();

        foreach ($roomsOfType as $roomRow) {
          $status = $roomRow['status'] ?? '';
          if (isset($statusSummary[$status])) {
            $statusSummary[$status]++;
          }
        }
    }
}

$pageTitle = $roomType
    ? ('Chi tiết ' . $roomType['name'] . ' — LuxStay')
    : 'Chi tiết phòng — LuxStay';

require_once '../includes/header.php';

function roomStatusData(string $status): array {
    switch ($status) {
        case 'booked':
            return ['badge-warning', 'Đang bận'];
        case 'maintenance':
            return ['badge-error', 'Bảo trì'];
        default:
            return ['badge-success', 'Sẵn sàng'];
    }
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

function roomHeroStyle(?string $image): string {
    $base = "linear-gradient(160deg, oklch(from var(--color-primary) l c h / 0.26), transparent)";
  $resolved = resolveRoomImageSrc($image);

  if ($resolved === '') {
    return 'background-image:' . $base;
    }

  return 'background-image:' . $base . ", url('" . htmlspecialchars($resolved, ENT_QUOTES) . "')";
}
?>

<?php if (!$roomType): ?>
  <section style="padding:var(--space-12) 0">
    <div class="container container--narrow">
      <div class="card">
        <div class="card-body" style="text-align:center;padding:var(--space-12)">
          <h1 style="font-family:var(--font-display);font-style:italic;font-size:var(--text-xl);margin-bottom:var(--space-3)">
            Không tìm thấy thông tin phòng
          </h1>
          <p class="text-muted" style="margin:0 auto var(--space-6)">
            Loại phòng bạn tìm có thể đã bị xóa hoặc đường dẫn không hợp lệ.
          </p>
          <a href="<?= BASE_URL ?>/pages/rooms.php" class="btn btn-primary">Quay lại danh sách phòng</a>
        </div>
      </div>
    </div>
  </section>
<?php else: ?>
  <div style="background:var(--color-surface);border-bottom:1px solid var(--color-border);padding:var(--space-3) 0">
    <div class="container">
      <nav aria-label="Breadcrumb" style="font-size:var(--text-xs);color:var(--color-text-muted);display:flex;align-items:center;gap:var(--space-2)">
        <a href="<?= BASE_URL ?>/pages/index.php">Trang chủ</a>
        <span aria-hidden="true">›</span>
        <a href="<?= BASE_URL ?>/pages/rooms.php">Danh sách phòng</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?= htmlspecialchars($roomType['name']) ?></span>
      </nav>
    </div>
  </div>

  <section class="room-detail-hero">
    <div class="container room-detail-hero__inner">
      <div>
        <p class="room-detail-hero__eyebrow">Chi tiết loại phòng</p>
        <h1 class="room-detail-hero__title"><?= htmlspecialchars($roomType['name']) ?></h1>
        <p class="room-detail-hero__desc">
          <?= htmlspecialchars(trim((string)$roomType['description']) !== '' ? $roomType['description'] : 'Không gian lưu trú được thiết kế tối ưu cho sự thoải mái, yên tĩnh và riêng tư.') ?>
        </p>
      </div>
      <div class="room-detail-hero__image" style="<?= roomHeroStyle($roomType['image'] ?? null) ?>">
      </div>
    </div>
  </section>

  <section style="padding:var(--space-8) 0 var(--space-12)">
    <div class="container room-detail-grid">
      <div class="card">
        <div class="card-body">
          <h2 style="font-size:var(--text-lg);margin-bottom:var(--space-5)">Thông tin chính</h2>

          <div class="room-highlight-grid">
            <div class="room-highlight-item">
              <span class="text-xs text-muted">Giá mỗi đêm</span>
              <strong><?= number_format((float)$roomType['price_per_night'], 0, ',', '.') ?> đ</strong>
            </div>
            <div class="room-highlight-item">
              <span class="text-xs text-muted">Sức chứa</span>
              <strong><?= (int)$roomType['capacity'] ?> khách</strong>
            </div>
            <div class="room-highlight-item">
              <span class="text-xs text-muted">Tổng số phòng</span>
              <strong><?= (int)$roomType['total_rooms'] ?> phòng</strong>
            </div>
            <div class="room-highlight-item">
              <span class="text-xs text-muted">Còn trống</span>
              <strong><?= (int)$roomType['available_rooms'] ?> phòng</strong>
            </div>
          </div>

          <div class="room-status-strip">
            <span class="badge badge-success">Sẵn sàng: <?= (int)$statusSummary['available'] ?></span>
            <span class="badge badge-warning">Đang bận: <?= (int)$statusSummary['booked'] ?></span>
            <span class="badge badge-error">Bảo trì: <?= (int)$statusSummary['maintenance'] ?></span>
          </div>

          <div style="display:flex;gap:var(--space-3);margin-top:var(--space-6);flex-wrap:wrap">
            <a class="btn btn-primary" href="<?= BASE_URL ?>/pages/booking.php?room_type_id=<?= (int)$roomType['id'] ?>">
              Đặt phòng ngay
            </a>
            <a class="btn btn-secondary" href="<?= BASE_URL ?>/pages/rooms.php">
              Xem danh sách phòng
            </a>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-body">
          <h2 style="font-size:var(--text-lg);margin-bottom:var(--space-5)">Danh sách phòng thuộc loại này</h2>

          <?php if (empty($roomsOfType)): ?>
            <p class="text-muted">Chưa có phòng thực tế nào cho loại phòng này.</p>
          <?php else: ?>
            <div class="table-wrapper">
              <table class="table" aria-label="Danh sách phòng thuộc loại">
                <thead>
                  <tr>
                    <th>Số phòng</th>
                    <th>Tầng</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($roomsOfType as $room): ?>
                    <?php [$badgeClass, $badgeText] = roomStatusData($room['status']); ?>
                    <tr>
                      <td style="font-weight:600"><?= htmlspecialchars($room['room_number']) ?></td>
                      <td><?= (int)$room['floor'] ?></td>
                      <td><span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span></td>
                      <td>
                        <?php if ($room['status'] === 'available'): ?>
                          <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/pages/booking.php?room_id=<?= (int)$room['id'] ?>&room_type_id=<?= (int)$roomType['id'] ?>">
                            Chọn phòng
                          </a>
                        <?php else: ?>
                          <button class="btn btn-secondary btn-sm" disabled>Không khả dụng</button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <style>
  .room-detail-hero {
    padding: var(--space-10) 0;
    background:
      radial-gradient(circle at 80% 10%, oklch(from var(--color-primary) l c h / 0.16), transparent 44%),
      linear-gradient(175deg, var(--color-surface-offset), var(--color-bg));
    border-bottom: 1px solid var(--color-divider);
  }
  .room-detail-hero__inner {
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: var(--space-8);
    align-items: center;
  }
  .room-detail-hero__eyebrow {
    font-size: var(--text-xs);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--color-text-muted);
    margin-bottom: var(--space-2);
  }
  .room-detail-hero__title {
    font-family: var(--font-display);
    font-size: clamp(2rem, 1.6rem + 1.1vw, 3rem);
    font-style: italic;
    margin-bottom: var(--space-3);
  }
  .room-detail-hero__desc {
    color: var(--color-text-muted);
    max-width: 64ch;
  }
  .room-detail-hero__image {
    border-radius: var(--radius-xl);
    min-height: 280px;
    border: 1px solid var(--color-border);
    background-size: cover;
    background-position: center;
    box-shadow: var(--shadow-md);
  }
  .room-detail-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--space-5);
  }
  .room-highlight-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--space-3);
  }
  .room-highlight-item {
    border: 1px solid var(--color-border);
    background: var(--color-surface-offset);
    border-radius: var(--radius-md);
    padding: var(--space-4);
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
  }
  .room-highlight-item strong {
    font-size: var(--text-base);
    color: var(--color-text);
  }
  .room-status-strip {
    margin-top: var(--space-4);
    display: flex;
    gap: var(--space-2);
    flex-wrap: wrap;
  }

  @media (max-width: 860px) {
    .room-detail-hero__inner { grid-template-columns: 1fr; }
    .room-detail-hero__image { min-height: 220px; }
  }

  @media (max-width: 520px) {
    .room-highlight-grid { grid-template-columns: 1fr; }
  }
  </style>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
