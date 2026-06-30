<?php 
// 1. Khởi động session
session_start();
require_once '../api/config/db.php'; 

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../pages/login.php?error=Vui lòng đăng nhập quyền Admin'); 
    exit;
}

// 3. Khởi tạo đối tượng Database và lấy kết nối
$database = new Database();
$db = $database->getConnection();

// 4. Truy vấn lấy số liệu thống kê (Sử dụng Try-Catch để tránh lỗi trắng trang)
try {
    $stats = [];

    // Lấy số lượng phòng đang trống (Dùng bảng 'rooms')
    $stmt1 = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'available' OR status = 'trống'");
    $stats['phong_trong'] = $stmt1->fetchColumn() ?: 0;

    // Lấy tổng số đơn đặt phòng (Bảng 'bookings')
    $stmt2 = $db->query("SELECT COUNT(*) FROM bookings");
    $stats['tong_booking'] = $stmt2->fetchColumn() ?: 0;

    // Tính tổng doanh thu từ các đơn đã xác nhận (Confirmed)
    $stmt3 = $db->query("SELECT SUM(total_price) FROM bookings WHERE status = 'completed' OR status = 'đã xác nhận'");
    $stats['doanh_thu'] = $stmt3->fetchColumn() ?: 0;

    // Lấy tổng số khách hàng (User có role là user)
    $stmt4 = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
    $stats['tong_khach'] = $stmt4->fetchColumn() ?: 0;

} catch (PDOException $e) {
    $stats['phong_trong'] = $stats['tong_booking'] = $stats['tong_khach'] = 0;
    $stats['doanh_thu'] = 0;
    $error_db = $e->getMessage();
}

$page_title = 'Bảng Điều Khiển Admin';
include '../includes/header.php'; 
?>

<div class="container-fluid mt-4" style="min-height: 80vh;">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard Thống Kê</h1>
        <span class="badge bg-primary p-2">
            Chào mừng Admin: <?php echo htmlspecialchars($_SESSION['user']['full_name'] ?? 'Quản trị viên'); ?>
        </span>
    </div>

    <?php if(isset($error_db)): ?>
        <div class="alert alert-warning">Lưu ý: Hệ thống đang kết nối... (<?php echo $error_db; ?>)</div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-primary border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Phòng Còn Trống</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['phong_trong']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bed fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-success border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Tổng Đơn Đặt</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['tong_booking']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-info border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Doanh Thu</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($stats['doanh_thu'], 0, ',', '.'); ?> ₫</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-warning border-4 shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Khách Hàng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['tong_khach']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow mb-4 border-0">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-dark">Lối tắt quản lý</h6>
                </div>
                <div class="card-body d-flex gap-3">
    <a href="amenities.php" class="btn btn-outline-primary flex-grow-1 p-3">
    <i class="fas fa-concierge-bell d-block mb-2"></i> Tiện Ích
</a>

    <a href="rooms.php" class="btn btn-outline-success flex-grow-1 p-3 rounded-3 shadow-sm">
        <i class="fas fa-door-open d-block mb-2 fa-lg"></i> Phòng
    </a>

    <a href="bookings.php" class="btn btn-outline-info flex-grow-1 p-3 rounded-3 shadow-sm">
        <i class="fas fa-clipboard-list d-block mb-2 fa-lg"></i> Đơn Đặt
    </a>
</div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>