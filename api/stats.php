<?php
// api/stats.php
require_once 'config/db.php';

function getStats($db) {
    try {
        $s = [];
        $s['phong_trong'] = $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn() ?: 0;
        $s['tong_booking'] = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn() ?: 0;
        
        // Đổi thành 'Hoàn thành' cho khớp với giao diện thực tế của Minh
        $s['doanh_thu'] = $db->query("SELECT SUM(total_price) FROM bookings WHERE status = 'completed' OR status = 'confirmed'")->fetchColumn() ?: 0;
        
        $s['tong_khach'] = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn() ?: 0;
        
        return $s;
    } catch (PDOException $e) {
        return ['error' => $e->getMessage()];
    }
}

$db_obj = new Database();
$db_conn = $db_obj->getConnection();
$current_stats = getStats($db_conn);

// LỆNH ĐỂ HIỆN DỮ LIỆU RA MÀN HÌNH
header('Content-Type: application/json');
echo json_encode($current_stats);