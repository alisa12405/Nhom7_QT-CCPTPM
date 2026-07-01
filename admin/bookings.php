<?php
require_once '../api/config/db.php';
require_once '../includes/auth_check.php';

// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
// Kiểm tra quyền Admin dựa trên cấu trúc Session thực tế (phải có ['user'])
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../pages/login.php?error=unauthorized");
    exit;
}

$database = new Database();
$db = $database->getConnection();

$msg   = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

$filter_status = $_GET['status'] ?? 'all';
$where  = '';
$params = [];
if ($filter_status !== 'all') {
    $where    = "WHERE b.status = ?";
    $params[] = $filter_status;
}

// SQL đã sửa: Lấy đúng cột u.full_name từ bảng users
$stmt = $db->prepare("SELECT b.*, u.full_name, u.email, r.room_number, rt.name AS room_type
                      FROM bookings b
                      JOIN users u ON b.user_id = u.id
                      JOIN rooms r ON b.room_id = r.id
                      JOIN room_types rt ON r.room_type_id = rt.id
                      $where
                      ORDER BY b.created_at DESC");
$stmt->execute($params);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sq = $db->query("SELECT status, COUNT(*) as cnt FROM bookings GROUP BY status");
$stats_raw = $sq->fetchAll(PDO::FETCH_ASSOC);
$stats = ['pending' => 0, 'confirmed' => 0, 'cancelled' => 0, 'completed' => 0];
foreach ($stats_raw as $s) $stats[$s['status']] = $s['cnt'];

// Cập nhật màu sắc Status cho Dark Mode
$status_map = [
    'pending'   => ['text' => 'Chờ xác nhận', 'bg' => 'rgba(245, 158, 11, 0.15)', 'color' => '#fcd34d'],
    'confirmed' => ['text' => 'Đã xác nhận',  'bg' => 'rgba(16, 185, 129, 0.15)', 'color' => '#6ee7b7'],
    'cancelled' => ['text' => 'Đã hủy',       'bg' => 'rgba(239, 68, 68, 0.15)', 'color' => '#fca5a5'],
    'completed' => ['text' => 'Hoàn thành',   'bg' => 'rgba(99, 102, 241, 0.15)', 'color' => '#a5b4fc'],
];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin - Quản lý đặt phòng</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Base Dark Theme */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #121212; /* Màu nền tối tương tự ảnh */
            color: #d1d5db; 
            margin: 0;
        }
        
        .wrap { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 40px 20px; 
        }

        /* Typography */
        h2.page-title {
            font-family: 'Georgia', serif; /* Font có chân cho tiêu đề giống ảnh */
            font-style: italic;
            font-size: 28px;
            color: #f3f4f6;
            margin-bottom: 8px;
            font-weight: normal;
        }
        .page-subtitle {
            color: #9ca3af;
            font-size: 14px;
            margin-bottom: 24px;
        }

        /* Stats Cards */
        .stats-row { 
            display: grid; 
            grid-template-columns: repeat(4, 1fr); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        .stat-box { 
            background: #1c1c1c; 
            border-radius: 8px; 
            padding: 24px 20px; 
            text-align: left; 
            border: 1px solid #333; 
        }
        .stat-box .lbl { 
            color: #9ca3af; 
            font-size: 13px; 
            margin-bottom: 8px; 
            font-weight: 500; 
        }
        .stat-box .num { 
            font-size: 28px; 
            font-weight: 700; 
            line-height: 1; 
            color: #f3f4f6;
        }

        /* Filter Bar */
        .filter-bar { 
            display: flex; 
            gap: 12px; 
            margin-bottom: 24px; 
            align-items: center; 
            background: #1c1c1c; 
            padding: 12px 16px; 
            border-radius: 8px; 
            border: 1px solid #333; 
        }
        .filter-bar span {
            color: #9ca3af;
        }
        .filter-bar a { 
            padding: 6px 14px; 
            border-radius: 6px; 
            text-decoration: none; 
            font-size: 13px; 
            font-weight: 600; 
            background: transparent; 
            color: #9ca3af; 
            transition: all 0.2s; 
        }
        .filter-bar a:hover {
            color: #f3f4f6;
        }
        .filter-bar a.active { 
            background: #63c5b5; /* Màu teal nổi bật giống nút Thêm loại phòng */
            color: #121212; 
        }

        /* Alerts */
        .alert { padding: 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; border-left: 4px solid; }
        .alert-success { background: rgba(34, 197, 94, 0.1); color: #4ade80; border-color: #22c55e; }
        .alert-danger  { background: rgba(239, 68, 68, 0.1); color: #f87171; border-color: #ef4444; }

        /* Table */
        table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0; 
            background: #1c1c1c; 
            border-radius: 8px; 
            overflow: hidden; 
            border: 1px solid #333; 
        }
        th { 
            background: transparent; 
            color: #9ca3af; 
            padding: 16px; 
            text-align: left; 
            font-size: 12px; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 0.05em; 
            border-bottom: 1px solid #333; 
        }
        td { 
            padding: 16px; 
            border-bottom: 1px solid #2a2a2a; 
            font-size: 14px; 
            vertical-align: middle; 
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); }

        /* Text colors inside table */
        .text-strong { color: #f3f4f6; font-weight: 600; }
        .text-muted { color: #9ca3af; font-size: 12px; margin-top: 4px; }
        .text-accent { color: #63c5b5; font-weight: 600; } /* Trùng màu giá trong ảnh */

        .status-pill { 
            padding: 4px 10px; 
            border-radius: 4px; 
            font-size: 12px; 
            font-weight: 600; 
        }

        .guests-badge {
            background: #2a2a2a; 
            color: #d1d5db;
            padding: 4px 8px; 
            border-radius: 4px; 
            font-size: 13px;
        }

        /* Buttons */
        .btn-group { display: flex; gap: 8px; align-items: center; }
        .btn-sm { 
            padding: 6px 12px; 
            border-radius: 6px; 
            font-size: 12px; 
            font-weight: 600; 
            border: none; 
            cursor: pointer; 
            transition: all 0.2s; 
        }
        .btn-view { 
            background: #2a2a2a; 
            color: #d1d5db; 
            text-decoration: none; 
            padding: 6px 12px; 
            border-radius: 6px; 
            font-size: 12px; 
            font-weight: 600;
        }
        .btn-view:hover { background: #333; color: #fff; }
        .btn-confirm  { background: #10b981; color: #111827; }
        .btn-complete { background: #6366f1; color: #fff; }
        .btn-delete   { background: transparent; color: #ef4444; border: 1px solid #ef4444; }
        .btn-delete:hover { background: #ef4444; color: #fff; }
    </style>
</head>
<body>
<div class="wrap">
    <h2 class="page-title">Quản lý đặt phòng</h2>
    <div class="page-subtitle">Có tất cả <?php echo count($bookings); ?> đơn đặt phòng hiện tại</div>

    <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="stats-row">
        <div class="stat-box"><div class="lbl">Chờ xác nhận</div><div class="num"><?= $stats['pending'] ?></div></div>
        <div class="stat-box"><div class="lbl">Đã xác nhận</div><div class="num"><?= $stats['confirmed'] ?></div></div>
        <div class="stat-box"><div class="lbl">Đã hủy</div><div class="num"><?= $stats['cancelled'] ?></div></div>
        <div class="stat-box"><div class="lbl">Hoàn thành</div><div class="num"><?= $stats['completed'] ?></div></div>
    </div>

    <div class="filter-bar">
        <span style="font-size: 13px;">Lọc trạng thái:</span>
        <a href="?status=all"       class="<?= $filter_status==='all'       ?'active':'' ?>">Tất cả</a>
        <a href="?status=pending"   class="<?= $filter_status==='pending'   ?'active':'' ?>">Chờ xác nhận</a>
        <a href="?status=confirmed" class="<?= $filter_status==='confirmed' ?'active':'' ?>">Đã xác nhận</a>
        <a href="?status=cancelled" class="<?= $filter_status==='cancelled' ?'active':'' ?>">Đã hủy</a>
        <a href="?status=completed" class="<?= $filter_status==='completed' ?'active':'' ?>">Hoàn thành</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Khách hàng</th>
                <th>Phòng</th>
                <th>Sức chứa / Ngày</th>
                <th>Giá / Đêm</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($bookings)): ?>
            <tr><td colspan="7" style="text-align:center;padding:48px;color:#94a3b8">Không tìm thấy đơn đặt phòng nào phù hợp.</td></tr>
        <?php endif; ?>
        <?php foreach ($bookings as $b):
            $st = $status_map[$b['status']] ?? ['text' => $b['status'], 'bg' => '#333', 'color' => '#d1d5db'];
        ?>
            <tr>
                <td><span class="text-muted">N/A</span> </td>
                <td>
                    <div class="text-strong"><?= htmlspecialchars($b['full_name']) ?></div>
                    <div class="text-muted"><?= htmlspecialchars($b['email']) ?></div>
                    <div class="text-muted">Booking ID: <?= $b['id'] ?></div>
                </td>
                <td>
                    <div class="text-strong"><?= htmlspecialchars($b['room_type']) ?></div>
                    <div class="text-muted">Phòng: <?= htmlspecialchars($b['room_number']) ?></div>
                </td>
                <td>
                    <div style="margin-bottom: 8px;"><span class="guests-badge"><?= $b['guests'] ?> người</span></div>
                    <div class="text-muted">📥 <?= date('d/m/Y', strtotime($b['check_in'])) ?></div>
                    <div class="text-muted">📤 <?= date('d/m/Y', strtotime($b['check_out'])) ?></div>
                </td>
                <td><div class="text-accent"><?= number_format($b['total_price'], 0, ',', '.') ?> ₫</div></td>
                <td>
                    <span class="status-pill" style="background:<?= $st['bg'] ?>;color:<?= $st['color'] ?>">
                        <?= $st['text'] ?>
                    </span>
                </td>
                <td>
                    <div class="btn-group">
                        <a href="../pages/booking_detail.php?id=<?= $b['id'] ?>" class="btn-view">Chi tiết</a>

                        <?php if ($b['status'] === 'pending'): ?>
                        <form method="POST" action="../api/booking.php?action=update_status" style="display:inline">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="status" value="confirmed">
                            <button class="btn-sm btn-confirm">Duyệt</button>
                        </form>
                        <?php endif; ?>

                        <?php if ($b['status'] === 'confirmed'): ?>
                        <form method="POST" action="../api/booking.php?action=update_status" style="display:inline">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <input type="hidden" name="status" value="completed">
                            <button class="btn-sm btn-complete">Xong</button>
                        </form>
                        <?php endif; ?>

                        <form method="POST" action="../api/booking.php?action=delete" style="display:inline"
                              onsubmit="return confirm('Xóa vĩnh viễn booking #<?= $b['id'] ?>?')">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button class="btn-sm btn-delete">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
