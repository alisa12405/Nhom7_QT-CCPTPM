<?php
require_once '../api/config/db.php';
require_once '../includes/auth_check.php';

// 1. SỬA LỖI SESSION: Truy cập vào mảng ['user']
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];
$role    = $_SESSION['user']['role'];

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: my_bookings.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();


$query = "SELECT b.*, 
                 u.full_name, u.email, u.phone,
                 r.room_number, 
                 rt.name AS room_type_name, 
                 rt.price_per_night, 
                 rt.image AS room_image
          FROM bookings b
          JOIN users u ON b.user_id = u.id
          JOIN rooms r ON b.room_id = r.id
          JOIN room_types rt ON r.room_type_id = rt.id
          WHERE b.id = ?";

$stmt = $db->prepare($query);
$stmt->execute([$id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

// Kiểm tra quyền xem: Admin hoặc chính chủ đơn hàng
if (!$booking || ($role !== 'admin' && (int)$booking['user_id'] !== (int)$user_id)) {
    die("Bạn không có quyền xem thông tin này hoặc đơn hàng không tồn tại.");
}

// Tính số đêm (hỗ trợ hiển thị)
$nights = (strtotime($booking['check_out']) - strtotime($booking['check_in'])) / 86400;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đặt phòng #<?= $id ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container { max-width: 800px; margin: 50px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .info-box { border: 1px solid #eee; padding: 15px; border-radius: 8px; }
        .info-box h3 { margin-top: 0; color: #1e40af; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; }
        .status-badge { padding: 5px 15px; border-radius: 20px; font-weight: bold; font-size: 14px; }
        .price-total { font-size: 24px; color: #ef4444; font-weight: 800; }
        .room-img { width: 100%; border-radius: 8px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display:flex; justify-content: space-between; align-items: center;">
            <h2>Chi tiết đơn đặt phòng #<?= $booking['id'] ?></h2>
            <a href="javascript:history.back()" style="text-decoration:none; color:#64748b;">← Quay lại</a>
        </div>

        <div class="detail-grid">
            <div class="info-box">
                <h3>🏨 Thông tin phòng</h3>
                <?php if ($booking['room_image']): ?>
                    <img src="../assets/images/rooms/<?= $booking['room_image'] ?>" class="room-img">
                <?php endif; ?>
                <p><strong>Phòng:</strong> <?= htmlspecialchars($booking['room_number']) ?> (<?= htmlspecialchars($booking['room_type_name']) ?>)</p>
                <p><strong>Giá mỗi đêm:</strong> <?= number_format($booking['price_per_night'], 0, ',', '.') ?> ₫</p>
                <p><strong>Số khách:</strong> <?= $booking['guests'] ?> người</p>
            </div>

            <div class="info-box">
                <h3>📅 Thời gian & Thanh toán</h3>
                <p><strong>Ngày nhận:</strong> <?= date('d/m/Y', strtotime($booking['check_in'])) ?></p>
                <p><strong>Ngày trả:</strong> <?= date('d/m/Y', strtotime($booking['check_out'])) ?></p>
                <p><strong>Số đêm:</strong> <?= $nights ?> đêm</p>
                <hr>
                <p><strong>Tổng tiền:</strong> <br><span class="price-total"><?= number_format($booking['total_price'], 0, ',', '.') ?> ₫</span></p>
                <p><strong>Trạng thái:</strong> 
                    <span class="status-badge"><?= strtoupper($booking['status']) ?></span>
                </p>
            </div>

            <div class="info-box" style="grid-column: span 2;">
                <h3>👤 Thông tin khách hàng</h3>
                <p><strong>Họ tên:</strong> <?= htmlspecialchars($booking['full_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($booking['email']) ?></p>
                <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($booking['phone'] ?? 'Chưa cập nhật') ?></p>
                <?php if ($booking['note']): ?>
                    <p><strong>Ghi chú:</strong> <?= htmlspecialchars($booking['note']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>