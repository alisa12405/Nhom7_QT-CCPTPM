<?php
/**
 * api/bookings.php
 */
require_once __DIR__ . '/config/db.php';           // load BASE_URL + kết nối DB
require_once __DIR__ . '/../includes/auth_check.php'; // requireLogin / requireAdmin / isAdmin
// sua booking
$action = $_GET['action'] ?? '';
$db     = (new Database())->getConnection();

// ─── Helpers ────────────────────────────────────────────────────────────────
function jsonOk(array $data = []): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}

function jsonFail(string $msg, int $code = 400): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

function redirectMsg(string $url, string $type, string $text): void {
    $sep = str_contains($url, '?') ? '&' : '?';
    header("Location: {$url}{$sep}{$type}=" . urlencode($text));
    exit;
}

/** Kiểm tra phòng có trống trong khoảng ngày không */
function roomAvailable(PDO $db, int $room_id, string $ci, string $co,
                        int $exclude = 0): bool {
    $sql = "SELECT COUNT(*) FROM bookings
            WHERE room_id  = :rid
              AND id       != :ex
              AND status   NOT IN ('cancelled')
              AND check_in  < :co
              AND check_out > :ci";
    $s = $db->prepare($sql);
    $s->execute([':rid' => $room_id, ':ex' => $exclude, ':co' => $co, ':ci' => $ci]);
    return (int)$s->fetchColumn() === 0;
}

/** Tính số đêm và tổng tiền */
function calcTotal(PDO $db, int $room_id, string $ci, string $co): array {
    $s = $db->prepare(
        "SELECT rt.price_per_night FROM rooms r
         JOIN room_types rt ON r.room_type_id = rt.id
         WHERE r.id = ?"
    );
    $s->execute([$room_id]);
    $row    = $s->fetch();
    $ppn    = $row ? (float)$row['price_per_night'] : 0;
    $nights = (int)((strtotime($co) - strtotime($ci)) / 86400);
    return ['nights' => $nights, 'price_per_night' => $ppn, 'total' => $nights * $ppn];
}

// ─── Router ─────────────────────────────────────────────────────────────────
switch ($action) {

    // ════════════════════════════════════════
    //  THÊM — Tạo đặt phòng mới
    // ════════════════════════════════════════
    case 'create':
        requireLogin();

        $room_id  = (int)($_POST['room_id']  ?? 0);
        $check_in = trim($_POST['check_in']  ?? '');
        $check_out= trim($_POST['check_out'] ?? '');
        $guests   = max(1, (int)($_POST['guests'] ?? 1));
        $note     = trim($_POST['note'] ?? '');
        $backUrl  = BASE_URL . '/pages/booking.php?room_id=' . $room_id;

        // Validate
        $errs = [];
        if (!$room_id)                                            $errs[] = 'Phòng không hợp lệ';
        if (!$check_in || !strtotime($check_in))                  $errs[] = 'Ngày nhận phòng không hợp lệ';
        if (!$check_out || !strtotime($check_out))                $errs[] = 'Ngày trả phòng không hợp lệ';
        if ($check_in  && strtotime($check_in) < strtotime('today'))
                                                                   $errs[] = 'Ngày nhận phòng không được là quá khứ';
        if ($check_in && $check_out && strtotime($check_out) <= strtotime($check_in))
                                                                   $errs[] = 'Ngày trả phòng phải sau ngày nhận phòng';
        if (!empty($errs)) redirectMsg($backUrl, 'error', implode('. ', $errs));

        // Kiểm tra phòng tồn tại
        $rs = $db->prepare(
            "SELECT r.id, r.status, rt.capacity FROM rooms r
             JOIN room_types rt ON r.room_type_id = rt.id WHERE r.id = ?"
        );
        $rs->execute([$room_id]);
        $room = $rs->fetch();

        if (!$room)                              redirectMsg($backUrl, 'error', 'Phòng không tồn tại');
        if ($room['status'] === 'maintenance')   redirectMsg($backUrl, 'error', 'Phòng đang bảo trì');
        if ($guests > (int)$room['capacity'])    redirectMsg($backUrl, 'error',
                                                    'Số khách vượt sức chứa tối đa (' . $room['capacity'] . ' người)');
        if (!roomAvailable($db, $room_id, $check_in, $check_out))
                                                 redirectMsg($backUrl, 'error', 'Phòng đã có người đặt trong khoảng thời gian này');

        $calc    = calcTotal($db, $room_id, $check_in, $check_out);
        $user_id = (int)$_SESSION['user']['id'];

        $ins = $db->prepare(
            "INSERT INTO bookings (user_id, room_id, check_in, check_out,
                                   total_price, guests, note, status)
             VALUES (?,?,?,?,?,?,?,'pending')"
        );
        if ($ins->execute([$user_id, $room_id, $check_in, $check_out,
                            $calc['total'], $guests, $note])) {
            header('Location: ' . BASE_URL . '/pages/booking_confirm.php?id=' . $db->lastInsertId());
            exit;
        }
        redirectMsg($backUrl, 'error', 'Đặt phòng thất bại, vui lòng thử lại');


    // ════════════════════════════════════════
    //  SỬA — Cập nhật ngày / số khách / ghi chú
    // ════════════════════════════════════════
    case 'update_info':
        requireLogin();

        $id        = (int)($_POST['id'] ?? 0);
        $check_in  = trim($_POST['check_in']  ?? '');
        $check_out = trim($_POST['check_out'] ?? '');
        $guests    = max(1, (int)($_POST['guests'] ?? 1));
        $note      = trim($_POST['note'] ?? '');
        $backUrl   = BASE_URL . '/pages/booking_detail.php?id=' . $id;

        if (!$id) redirectMsg(BASE_URL . '/pages/my_bookings.php', 'error', 'Booking không hợp lệ');

        $bs = $db->prepare("SELECT * FROM bookings WHERE id = ?");
        $bs->execute([$id]);
        $booking = $bs->fetch();

        if (!$booking)
            redirectMsg(BASE_URL . '/pages/my_bookings.php', 'error', 'Không tìm thấy đặt phòng');
        if (!isAdmin() && (int)$booking['user_id'] !== (int)$_SESSION['user']['id'])
            redirectMsg(BASE_URL . '/pages/my_bookings.php', 'error', 'Không có quyền chỉnh sửa');
        if ($booking['status'] !== 'pending')
            redirectMsg($backUrl, 'error', 'Chỉ có thể sửa đặt phòng đang chờ xác nhận');
        if (strtotime($check_out) <= strtotime($check_in))
            redirectMsg($backUrl, 'error', 'Ngày trả phòng phải sau ngày nhận phòng');
        if (!roomAvailable($db, (int)$booking['room_id'], $check_in, $check_out, $id))
            redirectMsg($backUrl, 'error', 'Phòng đã có người đặt trong khoảng thời gian này');

        $calc = calcTotal($db, (int)$booking['room_id'], $check_in, $check_out);
        $upd  = $db->prepare(
            "UPDATE bookings SET check_in=?,check_out=?,guests=?,note=?,total_price=? WHERE id=?"
        );
        $upd->execute([$check_in, $check_out, $guests, $note, $calc['total'], $id]);
        redirectMsg($backUrl, 'msg', 'Cập nhật đặt phòng thành công');


    // ════════════════════════════════════════
    //  SỬA — Đổi trạng thái (admin)
    // ════════════════════════════════════════
    case 'update_status':
        requireAdmin();

        $id     = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $back   = BASE_URL . '/admin/bookings.php';

        if (!$id || !in_array($status, ['pending','confirmed','cancelled','completed']))
            redirectMsg($back, 'error', 'Dữ liệu không hợp lệ');

        $db->prepare("UPDATE bookings SET status=? WHERE id=?")->execute([$status, $id]);
        $label = ['pending'=>'Chờ duyệt','confirmed'=>'Đã xác nhận',
                  'cancelled'=>'Đã hủy','completed'=>'Hoàn thành'];
        redirectMsg($back, 'msg', 'Đã cập nhật: ' . $label[$status]);


    // ════════════════════════════════════════
    //  HỦY — User tự hủy booking của mình
    // ════════════════════════════════════════
    case 'cancel':
        requireLogin();

        $id   = (int)($_POST['id'] ?? 0);
        $back = BASE_URL . '/pages/my_bookings.php';

        $bs = $db->prepare("SELECT * FROM bookings WHERE id = ?");
        $bs->execute([$id]);
        $booking = $bs->fetch();

        if (!$booking)
            redirectMsg($back, 'error', 'Không tìm thấy đặt phòng');
        if (!isAdmin() && (int)$booking['user_id'] !== (int)$_SESSION['user']['id'])
            redirectMsg($back, 'error', 'Không có quyền hủy đặt phòng này');
        // Admin hủy được pending/confirmed; user chỉ hủy được khi còn pending
        if (isAdmin()) {
            if (!in_array($booking['status'], ['pending','confirmed']))
                redirectMsg($back, 'error', 'Không thể hủy đặt phòng ở trạng thái này');
        } else {
            if ($booking['status'] !== 'pending')
                redirectMsg($back, 'error', 'Đặt phòng đã được xác nhận, bạn không thể tự hủy. Vui lòng liên hệ lễ tân để được hỗ trợ.');
        }

        $db->prepare("UPDATE bookings SET status='cancelled' WHERE id=?")->execute([$id]);
        redirectMsg($back, 'msg', 'Đã hủy đặt phòng thành công');


    // ════════════════════════════════════════
    //  XÓA — Xóa hẳn booking (admin)
    // ════════════════════════════════════════
    case 'delete':
        requireAdmin();

        $id   = (int)($_POST['id'] ?? 0);
        $back = BASE_URL . '/admin/bookings.php';

        if (!$id) redirectMsg($back, 'error', 'ID không hợp lệ');
        $db->prepare("DELETE FROM bookings WHERE id=?")->execute([$id]);
        redirectMsg($back, 'msg', 'Đã xóa đặt phòng #' . $id);


    // ════════════════════════════════════════
    //  XEM — Danh sách booking của user (JSON)
    // ════════════════════════════════════════
    case 'list_user':
        requireLogin();

        $uid    = (int)$_SESSION['user']['id'];
        $status = $_GET['status'] ?? '';
        $sql    = "SELECT b.*, r.room_number, r.floor,
                          rt.name AS room_type_name, rt.price_per_night, rt.image
                   FROM bookings b
                   JOIN rooms r      ON b.room_id = r.id
                   JOIN room_types rt ON r.room_type_id = rt.id
                   WHERE b.user_id = ?";
        $params = [$uid];
        if ($status && in_array($status, ['pending','confirmed','cancelled','completed'])) {
            $sql .= " AND b.status = ?"; $params[] = $status;
        }
        $sql .= " ORDER BY b.created_at DESC";
        $s = $db->prepare($sql);
        $s->execute($params);
        jsonOk(['bookings' => $s->fetchAll()]);


    // ════════════════════════════════════════
    //  XEM — Tất cả booking (admin, JSON)
    // ════════════════════════════════════════
    case 'list_all':
        requireAdmin();

        $status = $_GET['status'] ?? '';
        $q      = '%' . trim($_GET['search'] ?? '') . '%';
        $sql    = "SELECT b.*, r.room_number, rt.name AS room_type_name,
                          u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone
                   FROM bookings b
                   JOIN rooms r      ON b.room_id = r.id
                   JOIN room_types rt ON r.room_type_id = rt.id
                   JOIN users u       ON b.user_id = u.id
                   WHERE (u.full_name LIKE ? OR u.email LIKE ? OR r.room_number LIKE ?)";
        $params = [$q, $q, $q];
        if ($status && in_array($status, ['pending','confirmed','cancelled','completed'])) {
            $sql .= " AND b.status = ?"; $params[] = $status;
        }
        $sql .= " ORDER BY b.created_at DESC";
        $s = $db->prepare($sql);
        $s->execute($params);
        jsonOk(['bookings' => $s->fetchAll()]);


    // ════════════════════════════════════════
    //  XEM — Chi tiết 1 booking (JSON)
    // ════════════════════════════════════════
    case 'get_detail':
        requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) jsonFail('ID không hợp lệ');

        $s = $db->prepare(
            "SELECT b.*, r.room_number, r.floor,
                    rt.name AS room_type_name, rt.price_per_night, rt.capacity, rt.image,
                    u.full_name AS user_name, u.email AS user_email, u.phone AS user_phone
             FROM bookings b
             JOIN rooms r      ON b.room_id = r.id
             JOIN room_types rt ON r.room_type_id = rt.id
             JOIN users u       ON b.user_id = u.id
             WHERE b.id = ?"
        );
        $s->execute([$id]);
        $booking = $s->fetch();

        if (!$booking)                                                     jsonFail('Không tìm thấy', 404);
        if (!isAdmin() && (int)$booking['user_id'] !== (int)$_SESSION['user']['id'])
                                                                           jsonFail('Không có quyền', 403);
        jsonOk(['booking' => $booking]);


    default:
        header('Location: ' . BASE_URL . '/pages/index.php');
        exit;
}
?>