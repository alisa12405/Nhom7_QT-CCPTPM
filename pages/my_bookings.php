<?php
/**
 * pages/my_bookings.php — Lịch sử đặt phòng của user
 */
$pageTitle = 'Đặt phòng của tôi — LuxStay';

require_once '../includes/auth_check.php';
requireLogin();

require_once '../api/config/db.php';
$db      = (new Database())->getConnection();
$user_id = (int)$_SESSION['user']['id'];

// ── Bộ lọc trạng thái ─────────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? '';
$validStatus  = ['pending','confirmed','cancelled','completed'];

$sql    = "SELECT b.id, b.check_in, b.check_out, b.total_price,
                  b.guests, b.status, b.created_at, b.note,
                  r.room_number, r.floor,
                  rt.name AS room_type_name, rt.price_per_night, rt.image
           FROM bookings b
           JOIN rooms r      ON b.room_id = r.id
           JOIN room_types rt ON r.room_type_id = rt.id
           WHERE b.user_id = ?";
$params = [$user_id];
if ($filterStatus && in_array($filterStatus, $validStatus)) {
    $sql .= " AND b.status = ?";
    $params[] = $filterStatus;
}
$sql .= " ORDER BY b.created_at DESC";
$stmt     = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Đếm theo từng trạng thái để hiện badge trên tab
$cs = $db->prepare("SELECT status, COUNT(*) cnt FROM bookings WHERE user_id=? GROUP BY status");
$cs->execute([$user_id]);
$counts = ['pending'=>0,'confirmed'=>0,'cancelled'=>0,'completed'=>0];
foreach ($cs->fetchAll() as $c) $counts[$c['status']] = (int)$c['cnt'];
$total = array_sum($counts);

$error = htmlspecialchars(strip_tags($_GET['error'] ?? ''));
$msg   = htmlspecialchars(strip_tags($_GET['msg']   ?? ''));

// Badge helper
function statusBadge(string $st): string {
    $map = [
        'pending'   => ['badge-pending', '⏳ Chờ xác nhận'],
        'confirmed' => ['badge-success', '✓ Đã xác nhận'],
        'cancelled' => ['badge-error',   '✕ Đã hủy'],
        'completed' => ['badge',         '★ Hoàn thành'],
    ];
    [$cls, $lbl] = $map[$st] ?? $map['pending'];
    $extra = $st === 'completed'
        ? ' style="background:var(--color-surface-dynamic);color:var(--color-text-muted)"'
        : '';
    return "<span class=\"badge {$cls}\"{$extra}>{$lbl}</span>";
}

require_once '../includes/header.php';
?>

<!-- BREADCRUMB -->
<div style="background:var(--color-surface);border-bottom:1px solid var(--color-border);padding:var(--space-3) 0">
  <div class="container">
    <nav style="display:flex;align-items:center;gap:var(--space-2);font-size:var(--text-xs);color:var(--color-text-muted)">
      <a href="<?= BASE_URL ?>/pages/index.php">Trang chủ</a>
      <span>›</span>
      <span aria-current="page">Đặt phòng của tôi</span>
    </nav>
  </div>
</div>

<section style="padding:var(--space-10) 0 var(--space-16)">
  <div class="container" style="max-width:860px">

    <!-- Tiêu đề -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;
      flex-wrap:wrap;gap:var(--space-4);margin-bottom:var(--space-6)">
      <div>
        <h1 style="font-family:var(--font-display);font-size:var(--text-xl);font-style:italic;margin-bottom:4px">
          Đặt phòng của tôi
        </h1>
        <p style="font-size:var(--text-sm);color:var(--color-text-muted)">
          Tổng <strong><?= $total ?></strong> đơn đặt phòng
        </p>
      </div>
      <a href="<?= BASE_URL ?>/pages/rooms.php" class="btn btn-primary">
        <i data-lucide="plus" width="16" height="16"></i> Đặt phòng mới
      </a>
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

    <!-- TABS -->
    <div style="display:flex;gap:0;flex-wrap:wrap;
      border-bottom:2px solid var(--color-border);margin-bottom:var(--space-6)">
      <?php
      $tabs = [
        '' => ['Tất cả', $total],
        'pending'   => ['Chờ xác nhận', $counts['pending']],
        'confirmed' => ['Đã xác nhận',  $counts['confirmed']],
        'completed' => ['Hoàn thành',   $counts['completed']],
        'cancelled' => ['Đã hủy',       $counts['cancelled']],
      ];
      foreach ($tabs as $val => [$lbl, $cnt]):
        $active = $filterStatus === $val;
      ?>
        <a href="?<?= $val ? 'status='.$val : '' ?>"
           style="display:inline-flex;align-items:center;gap:var(--space-2);
             padding:var(--space-3) var(--space-4);white-space:nowrap;text-decoration:none;
             font-size:var(--text-sm);font-weight:<?= $active?'700':'500' ?>;
             color:<?= $active?'var(--color-primary)':'var(--color-text-muted)' ?>;
             border-bottom:2px solid <?= $active?'var(--color-primary)':'transparent' ?>;
             margin-bottom:-2px">
          <?= $lbl ?>
          <?php if ($cnt): ?>
            <span style="font-size:var(--text-xs);padding:1px 6px;border-radius:var(--radius-full);
              background:<?= $active?'var(--color-primary-highlight)':'var(--color-surface-dynamic)' ?>;
              color:<?= $active?'var(--color-primary)':'var(--color-text-muted)' ?>;font-weight:600">
              <?= $cnt ?>
            </span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>

    <!-- DANH SÁCH -->
    <?php if (empty($bookings)): ?>
      <div style="text-align:center;padding:var(--space-16) 0;color:var(--color-text-faint)">
        <i data-lucide="calendar-x" width="52" height="52" style="margin:0 auto var(--space-4);opacity:.35"></i>
        <p style="font-size:var(--text-lg);font-weight:600;margin-bottom:var(--space-2)">Chưa có đặt phòng nào</p>
        <p style="font-size:var(--text-sm);margin-bottom:var(--space-6)">
          <?= $filterStatus ? 'Không có đơn ở trạng thái này' : 'Bạn chưa đặt phòng lần nào' ?>
        </p>
        <a href="<?= BASE_URL ?>/pages/rooms.php" class="btn btn-primary">Khám phá phòng</a>
      </div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:var(--space-4)">
        <?php foreach ($bookings as $b):
          $nights    = (int)((strtotime($b['check_out']) - strtotime($b['check_in'])) / 86400);
          $canCancel = $b['status'] === 'pending' && strtotime($b['check_out']) > time();
        ?>
          <div class="card">
            <div style="display:grid;grid-template-columns:88px 1fr auto;
              gap:var(--space-4);align-items:start;padding:var(--space-5)">

              <!-- Thumbnail -->
              <div style="width:88px;height:88px;border-radius:var(--radius-md);overflow:hidden;flex-shrink:0">
                <?php if ($b['image']): ?>
                  <img src="<?= BASE_URL ?>/assets/uploads/<?= htmlspecialchars($b['image']) ?>"
                       alt="" style="width:100%;height:100%;object-fit:cover">
                <?php else: ?>
                  <div style="width:100%;height:100%;background:var(--color-surface-offset);
                    display:flex;align-items:center;justify-content:center;color:var(--color-text-faint)">
                    <i data-lucide="bed-double" width="28" height="28"></i>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Thông tin -->
              <div style="min-width:0">
                <div style="display:flex;align-items:center;gap:var(--space-2);flex-wrap:wrap;margin-bottom:4px">
                  <span style="font-size:var(--text-xs);color:var(--color-text-faint)">
                    #<?= str_pad($b['id'],5,'0',STR_PAD_LEFT) ?>
                  </span>
                  <?= statusBadge($b['status']) ?>
                </div>
                <h3 style="font-weight:600;margin-bottom:var(--space-2)">
                  <?= htmlspecialchars($b['room_type_name']) ?> — Phòng <?= htmlspecialchars($b['room_number']) ?>
                </h3>
                <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-3)">
                  <i data-lucide="calendar" width="13" height="13" style="vertical-align:middle"></i>
                  <?= date('d/m/Y', strtotime($b['check_in'])) ?> → <?= date('d/m/Y', strtotime($b['check_out'])) ?>
                  &nbsp;·&nbsp; <?= $nights ?> đêm
                  &nbsp;·&nbsp;
                  <i data-lucide="users" width="13" height="13" style="vertical-align:middle"></i>
                  <?= $b['guests'] ?> khách
                </p>
                <div style="display:flex;gap:var(--space-2);flex-wrap:wrap">
                  <a href="<?= BASE_URL ?>/pages/booking_detail.php?id=<?= $b['id'] ?>"
                     class="btn btn-secondary btn-sm">
                    <i data-lucide="eye" width="13" height="13"></i> Chi tiết
                  </a>
                  <?php if ($canCancel): ?>
                    <form method="POST"
                          action="<?= BASE_URL ?>/api/booking.php?action=cancel"
                          style="margin:0"
                          onsubmit="return confirm('Xác nhận hủy đặt phòng này?\nHành động không thể hoàn tác.')">
                      <input type="hidden" name="id" value="<?= $b['id'] ?>">
                      <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--color-error)">
                        <i data-lucide="x-circle" width="13" height="13"></i> Hủy đặt
                      </button>
                    </form>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Giá -->
              <div style="text-align:right;flex-shrink:0">
                <p style="font-weight:700;font-size:var(--text-lg);color:var(--color-primary)">
                  <?= number_format($b['total_price'],0,',','.') ?>đ
                </p>
                <p style="font-size:var(--text-xs);color:var(--color-text-faint);margin-top:2px">
                  <?= number_format($b['price_per_night'],0,',','.') ?>đ/đêm
                </p>
              </div>
            </div>

            <?php if ($b['note']): ?>
              <div style="padding:var(--space-3) var(--space-5);border-top:1px solid var(--color-divider);
                background:var(--color-surface-offset);font-size:var(--text-xs);color:var(--color-text-muted)">
                <i data-lucide="message-square" width="12" height="12" style="vertical-align:middle"></i>
                <?= htmlspecialchars($b['note']) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<style>
.alert{display:flex;align-items:flex-start;gap:var(--space-2)}
@media(max-width:560px){
  .card>div[style*="grid-template-columns:88px"]{grid-template-columns:1fr!important}
  .card>div>div:first-child{display:none}
}
</style>

<script>
(function(){
  document.querySelectorAll('.alert').forEach(function(a){
    setTimeout(function(){
      a.style.transition='opacity .4s'; a.style.opacity='0';
      setTimeout(function(){a.remove()},400);
    },5000);
  });
})();
</script>

<?php require_once '../includes/footer.php'; ?>