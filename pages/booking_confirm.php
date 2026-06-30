<?php
require_once '../api/config/db.php';
require_once '../includes/auth_check.php';
$database = new Database();
$db = $database->getConnection();

$id  = intval($_GET['id'] ?? 0);
$msg = $_GET['msg'] ?? '';

$stmt = $db->prepare("SELECT b.*, r.room_number, rt.name AS room_type, u.full_name, u.email
                      FROM bookings b
                      JOIN rooms r ON b.room_id = r.id
                      JOIN room_types rt ON r.room_type_id = rt.id
                      JOIN users u ON b.user_id = u.id
                      WHERE b.id = ? AND b.user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) { header("Location: my_bookings.php"); exit; }

$status_map = [
    'pending'   => ['label' => '⏳ Chờ xác nhận', 'color' => '#f59e0b'],
    'confirmed' => ['label' => '✅ Đã xác nhận',  'color' => '#10b981'],
    'cancelled' => ['label' => '❌ Đã hủy',        'color' => '#ef4444'],
    'completed' => ['label' => '🏁 Hoàn thành',   'color' => '#6366f1'],
];
$st = $status_map[$booking['status']] ?? ['label' => $booking['status'], 'color' => '#888'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác nhận đặt phòng #<?= $id ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .confirm-box { max-width: 600px; margin: 50px auto; background: #fff; border-radius: 12px; padding: 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .success-banner { text-align: center; margin-bottom: 30px; }
        .success-banner h2 { color: #10b981; font-size: 28px; }
        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table tr { border-bottom: 1px solid #eee; }
        .detail-table td { padding: 12px 10px; }
        .detail-table td:first-child { color: #666; width: 45%; }
        .detail-table td:last-child { font-weight: 600; }
        .status-badge { display: inline-block; padding: 6px 16px; border-radius: 20px; color: #fff; font-weight: 700; }
        .actions { margin-top: 30px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }
        .btn { padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; text-decoration: none; border: none; cursor: pointer; }
        .btn-primary   { background: #2563eb; color: #fff; }
        .btn-danger    { background: #ef4444; color: #fff; }
        .btn-secondary { background: #e5e7eb; color: #333; }
        .total-price   { font-size: 22px; color: #2563eb; }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>

<div class="confirm-box">
    <?php if ($msg): ?>
    <div class="success-banner">
        <div style="font-size:60px">🎉</div>
        <h2><?= htmlspecialchars($msg) ?>!</h2>
        <p>Mã đặt phòng: <strong>#<?= $id ?></strong></p>
    </div>
    <?php endif; ?>

    <table class="detail-table">
        <tr><td>Mã đặt phòng</td><td>#<?= $id ?></td></tr>
        <tr><td>Phòng</td><td><?= htmlspecialchars($booking['room_number']) ?> — <?= htmlspecialchars($booking['room_type']) ?></td></tr>
        <tr><td>Khách hàng</td><td><?= htmlspecialchars($booking['username']) ?></td></tr>
        <tr><td>Email</td><td><?= htmlspecialchars($booking['email']) ?></td></tr>
        <tr><td>Ngày nhận phòng</td><td><?= date('d/m/Y', strtotime($booking['check_in'])) ?></td></tr>
        <tr><td>Ngày trả phòng</td><td><?= date('d/m/Y', strtotime($booking['check_out'])) ?></td></tr>
        <tr><td>Số khách</td><td><?= $booking['guests'] ?> người</td></tr>
        <tr><td>Trạng thái</td>
            <td><span class="status-badge" style="background:<?= $st['color'] ?>"><?= $st['label'] ?></span></td>
        </tr>
        <?php if ($booking['notes']): ?>
        <tr><td>Ghi chú</td><td><?= htmlspecialchars($booking['notes']) ?></td></tr>
        <?php endif; ?>
        <tr><td>Tổng tiền</td><td class="total-price"><?= number_format($booking['total_price'], 0, ',', '.') ?> VNĐ</td></tr>
    </table>

    <div class="actions">
        <a href="my_bookings.php" class="btn btn-primary">📋 Lịch sử đặt phòng</a>
        <?php if ($booking['status'] === 'pending'): ?>
        <form method="POST" action="../api/booking.php?action=update_status"
              onsubmit="return confirm('Bạn có chắc muốn hủy?')">
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="status" value="cancelled">
            <button type="submit" class="btn btn-danger">❌ Hủy đặt phòng</button>
        </form>
        <?php endif; ?>
        <a href="rooms.php" class="btn btn-secondary">🏨 Xem phòng khác</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>