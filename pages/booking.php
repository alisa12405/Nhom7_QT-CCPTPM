<?php
/**
 * pages/booking.php — Form đặt phòng
 */
$pageTitle = 'Đặt phòng — LuxStay';
require_once '../includes/auth_check.php';
requireLogin();                         // Chưa đăng nhập → về login

require_once '../api/config/db.php';
$db      = (new Database())->getConnection();
$room_id = (int)($_GET['room_id'] ?? 0);

// ── Lấy thông tin phòng ────────────────────────────────────────────────────
$s = $db->prepare(
    "SELECT r.id, r.room_number, r.floor, r.status,
            rt.name AS type_name, rt.description,
            rt.price_per_night, rt.capacity, rt.image
     FROM rooms r JOIN room_types rt ON r.room_type_id = rt.id
     WHERE r.id = ?"
);
$s->execute([$room_id]);
$room = $s->fetch();

if (!$room) {
    header('Location: ' . BASE_URL . '/pages/rooms.php?error=' . urlencode('Không tìm thấy phòng'));
    exit;
}
if ($room['status'] === 'maintenance') {
    header('Location: ' . BASE_URL . '/pages/rooms.php?error=' . urlencode('Phòng đang bảo trì'));
    exit;
}

// ── Các khoảng ngày đã bị đặt (để JS cảnh báo) ────────────────────────────
$bs = $db->prepare(
    "SELECT check_in, check_out FROM bookings
     WHERE room_id = ? AND status NOT IN ('cancelled') AND check_out >= CURDATE()"
);
$bs->execute([$room_id]);
$bookedRanges = array_map(fn($b) => ['from' => $b['check_in'], 'to' => $b['check_out']],
                          $bs->fetchAll());

// Giữ lại giá trị khi có lỗi
$old = [
    'check_in'  => $_GET['check_in']  ?? date('Y-m-d', strtotime('+1 day')),
    'check_out' => $_GET['check_out'] ?? date('Y-m-d', strtotime('+2 day')),
    'guests'    => max(1, (int)($_GET['guests'] ?? 1)),
    'note'      => htmlspecialchars($_GET['note'] ?? ''),
];
$error = htmlspecialchars(strip_tags($_GET['error'] ?? ''));

require_once '../includes/header.php';
?>

<!-- BREADCRUMB -->
<div style="background:var(--color-surface);border-bottom:1px solid var(--color-border);padding:var(--space-3) 0">
  <div class="container">
    <nav aria-label="Breadcrumb" style="display:flex;align-items:center;gap:var(--space-2);font-size:var(--text-xs);color:var(--color-text-muted)">
      <a href="<?= BASE_URL ?>/pages/index.php">Trang chủ</a><span>›</span>
      <a href="<?= BASE_URL ?>/pages/rooms.php">Phòng</a><span>›</span>
      <span aria-current="page">Đặt phòng</span>
    </nav>
  </div>
</div>

<section style="padding:var(--space-10) 0 var(--space-16)">
  <div class="container" style="max-width:900px">

    <h1 style="font-family:var(--font-display);font-size:var(--text-xl);font-style:italic;margin-bottom:var(--space-8)">
      Xác nhận đặt phòng
    </h1>

    <?php if ($error): ?>
      <div class="alert alert-error" role="alert" style="margin-bottom:var(--space-5)">
        <i data-lucide="alert-circle" width="16" height="16" style="flex-shrink:0"></i>
        <?= $error ?>
      </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:3fr 2fr;gap:var(--space-6);align-items:start">

      <!-- CỘT TRÁI: Form -->
      <div>
        <form method="POST"
              action="<?= BASE_URL ?>/api/booking.php?action=create"
              id="booking-form" novalidate>
          <input type="hidden" name="room_id" value="<?= $room_id ?>">

          <!-- Chọn ngày -->
          <div class="card" style="margin-bottom:var(--space-5)">
            <div class="card-body">
              <h2 style="font-size:var(--text-lg);font-weight:600;margin-bottom:var(--space-4)">
                <i data-lucide="calendar" width="17" height="17" style="vertical-align:middle;margin-right:6px"></i>
                Ngày lưu trú
              </h2>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label" for="check_in">Ngày nhận phòng *</label>
                  <input class="form-input" type="date" id="check_in" name="check_in"
                         value="<?= $old['check_in'] ?>"
                         min="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label" for="check_out">Ngày trả phòng *</label>
                  <input class="form-input" type="date" id="check_out" name="check_out"
                         value="<?= $old['check_out'] ?>"
                         min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                </div>
              </div>

              <!-- Tóm tắt giá (hiện khi ngày hợp lệ) -->
              <div id="price-box" style="display:none;background:var(--color-primary-highlight);
                border:1px solid var(--color-primary);border-radius:var(--radius-md);
                padding:var(--space-4);margin-top:var(--space-2)">
                <div style="display:flex;justify-content:space-between;
                  font-size:var(--text-sm);margin-bottom:var(--space-2)">
                  <span style="color:var(--color-text-muted)">
                    <?= number_format($room['price_per_night'],0,',','.') ?>đ × <span id="js-nights">0</span> đêm
                  </span>
                  <span id="js-subtotal" style="font-weight:600"></span>
                </div>
                <div style="display:flex;justify-content:space-between;font-weight:700;
                  padding-top:var(--space-2);border-top:1px solid var(--color-primary)">
                  <span>Tổng cộng</span>
                  <span id="js-total" style="color:var(--color-primary);font-size:var(--text-lg)"></span>
                </div>
              </div>
              <p id="js-date-err" style="display:none;color:var(--color-error);
                font-size:var(--text-sm);margin-top:var(--space-2)"></p>
            </div>
          </div>

          <!-- Thông tin khách -->
          <div class="card" style="margin-bottom:var(--space-5)">
            <div class="card-body">
              <h2 style="font-size:var(--text-lg);font-weight:600;margin-bottom:var(--space-4)">
                <i data-lucide="users" width="17" height="17" style="vertical-align:middle;margin-right:6px"></i>
                Thông tin khách
              </h2>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Tên khách</label>
                  <input class="form-input" type="text"
                         value="<?= htmlspecialchars($_SESSION['user']['full_name']) ?>"
                         disabled style="opacity:.6;cursor:not-allowed">
                  <p class="form-help">Lấy từ tài khoản đăng nhập</p>
                </div>
                <div class="form-group">
                  <label class="form-label" for="guests">Số khách *</label>
                  <input class="form-input" type="number" id="guests" name="guests"
                         value="<?= $old['guests'] ?>" min="1"
                         max="<?= $room['capacity'] ?>" required>
                  <p class="form-help">Tối đa <?= $room['capacity'] ?> khách</p>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label" for="note">Ghi chú đặc biệt</label>
                <textarea class="form-input" id="note" name="note" rows="3"
                          placeholder="Yêu cầu đặc biệt, giờ nhận phòng dự kiến…"
                          style="resize:vertical"><?= $old['note'] ?></textarea>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-full" id="js-submit"
                  style="padding:var(--space-4);font-size:var(--text-base)">
            <i data-lucide="calendar-check" width="18" height="18"></i>
            Xác nhận đặt phòng
          </button>
          <p style="text-align:center;font-size:var(--text-xs);color:var(--color-text-faint);margin-top:var(--space-2)">
            Chưa thanh toán — nhân viên sẽ xác nhận và liên hệ bạn
          </p>
        </form>
      </div>

      <!-- CỘT PHẢI: Thông tin phòng -->
      <div>
        <div class="card" style="position:sticky;top:80px">
          <div class="card-body">
            <?php if ($room['image']): ?>
              <img src="<?= BASE_URL ?>/assets/uploads/<?= htmlspecialchars($room['image']) ?>"
                   alt="Phòng <?= htmlspecialchars($room['room_number']) ?>"
                   style="width:100%;height:150px;object-fit:cover;
                          border-radius:var(--radius-md);margin-bottom:var(--space-4)">
            <?php else: ?>
              <div style="width:100%;height:120px;background:var(--color-surface-offset);
                border-radius:var(--radius-md);display:flex;align-items:center;
                justify-content:center;margin-bottom:var(--space-4);
                color:var(--color-text-faint)">
                <i data-lucide="bed-double" width="36" height="36"></i>
              </div>
            <?php endif; ?>

            <p style="font-size:var(--text-xs);text-transform:uppercase;letter-spacing:.08em;color:var(--color-text-faint)">
              <?= htmlspecialchars($room['type_name']) ?>
            </p>
            <h3 style="font-size:var(--text-lg);font-weight:600;margin:var(--space-1) 0">
              Phòng <?= htmlspecialchars($room['room_number']) ?>
            </h3>
            <p style="font-size:var(--text-sm);color:var(--color-text-muted);margin-bottom:var(--space-4)">
              Tầng <?= $room['floor'] ?> &nbsp;·&nbsp;
              <i data-lucide="users" width="13" height="13" style="vertical-align:middle"></i>
              <?= $room['capacity'] ?> khách tối đa
            </p>

            <div style="border-top:1px solid var(--color-divider);padding-top:var(--space-4)">
              <div style="display:flex;justify-content:space-between;align-items:baseline">
                <span style="font-size:var(--text-sm);color:var(--color-text-muted)">Mỗi đêm</span>
                <span style="font-weight:700;font-size:var(--text-xl);color:var(--color-primary)">
                  <?= number_format($room['price_per_night'],0,',','.') ?>đ
                </span>
              </div>
            </div>

            <?php if (!empty($bookedRanges)): ?>
              <div style="margin-top:var(--space-4);padding:var(--space-3);
                background:var(--color-warning-highlight);border-radius:var(--radius-md);
                font-size:var(--text-xs);color:var(--color-warning)">
                <p style="font-weight:600;margin-bottom:4px">⚠ Lịch đã đặt:</p>
                <?php foreach ($bookedRanges as $br): ?>
                  <p><?= date('d/m', strtotime($br['from'])) ?> – <?= date('d/m/Y', strtotime($br['to'])) ?></p>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<style>
.alert { display:flex;align-items:flex-start;gap:var(--space-2); }
@media(max-width:640px){
  section .container>div[style*="grid-template-columns:3fr"]{grid-template-columns:1fr!important}
}
</style>

<script>
(function(){
  var ci  = document.getElementById('check_in');
  var co  = document.getElementById('check_out');
  var box = document.getElementById('price-box');
  var err = document.getElementById('js-date-err');
  var btn = document.getElementById('js-submit');
  var ppn = <?= (float)$room['price_per_night'] ?>;
  var booked = <?= json_encode($bookedRanges) ?>;

  function fmt(n){ return n.toLocaleString('vi-VN')+'đ'; }

  function overlap(a,b){
    var at=new Date(a).getTime(), bt=new Date(b).getTime();
    for(var i=0;i<booked.length;i++){
      var ft=new Date(booked[i].from).getTime(), tt=new Date(booked[i].to).getTime();
      if(at<tt && bt>ft) return true;
    }
    return false;
  }

  function update(){
    var a=ci.value, b=co.value;
    err.style.display='none'; box.style.display='none'; btn.disabled=false;
    if(!a||!b) return;
    var ad=new Date(a), bd=new Date(b), today=new Date(); today.setHours(0,0,0,0);
    if(ad<today){err.textContent='⚠ Ngày nhận phòng không được là quá khứ';err.style.display='block';btn.disabled=true;return;}
    if(bd<=ad){err.textContent='⚠ Ngày trả phòng phải sau ngày nhận phòng';err.style.display='block';btn.disabled=true;return;}
    if(overlap(a,b)){err.textContent='⚠ Phòng đã có người đặt trong khoảng này';err.style.display='block';btn.disabled=true;return;}
    var nights=Math.round((bd-ad)/86400000), total=nights*ppn;
    document.getElementById('js-nights').textContent=nights;
    document.getElementById('js-subtotal').textContent=fmt(total);
    document.getElementById('js-total').textContent=fmt(total);
    box.style.display='block';
  }

  ci.addEventListener('change',function(){
    if(co.value && co.value<=ci.value){
      var d=new Date(ci.value); d.setDate(d.getDate()+1);
      co.value=d.toISOString().split('T')[0];
    }
    update();
  });
  co.addEventListener('change',update);
  update();
})();
</script>

<?php require_once '../includes/footer.php'; ?>