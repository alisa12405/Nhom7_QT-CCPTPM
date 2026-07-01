<?php
session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/dao/RoomDAO.php';
//binh sua rooms.php
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$db     = (new Database())->getConnection();
$dao    = new RoomDAO($db);

function redirectRoomsAdmin(string $type, string $message): void {
    $query = $type . '=' . urlencode($message);
    header('Location: ' . BASE_URL . '/admin/rooms.php?' . $query);
    exit;
}

function isValidDate(string $value): bool {
    $date = DateTime::createFromFormat('Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
}

function roomTypeExists(PDO $db, int $id): bool {
    $stmt = $db->prepare('SELECT id FROM room_types WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    return (bool)$stmt->fetch();
}

switch ($action) {
    case 'list':
        header('Content-Type: application/json; charset=utf-8');

        $filters = [
            'search'       => trim($_GET['search'] ?? ''),
            'status'       => trim($_GET['status'] ?? ''),
            'room_type_id' => (int)($_GET['room_type_id'] ?? 0),
        ];

        echo json_encode($dao->getAll($filters), JSON_UNESCAPED_UNICODE);
        exit;

    case 'add':
        requireAdmin();

        if ($method !== 'POST') {
            redirectRoomsAdmin('error', 'Phương thức không hợp lệ');
        }

        $roomNumber = strtoupper(trim($_POST['room_number'] ?? ''));
        $roomTypeId = (int)($_POST['room_type_id'] ?? 0);
        $floor      = (int)($_POST['floor'] ?? 1);
        $status     = trim($_POST['status'] ?? 'available');

        if (!preg_match('/^[A-Z0-9-]{1,10}$/', $roomNumber)) {
            redirectRoomsAdmin('error', 'Số phòng chỉ gồm chữ, số hoặc dấu gạch ngang (tối đa 10 ký tự)');
        }
        if ($roomTypeId <= 0 || !roomTypeExists($db, $roomTypeId)) {
            redirectRoomsAdmin('error', 'Loại phòng không hợp lệ');
        }
        if ($floor < 0 || $floor > 100) {
            redirectRoomsAdmin('error', 'Tầng phải từ 0 đến 100');
        }
        if (!in_array($status, ['available', 'booked', 'maintenance'], true)) {
            redirectRoomsAdmin('error', 'Trạng thái phòng không hợp lệ');
        }
        if ($dao->existsRoomNumber($roomNumber)) {
            redirectRoomsAdmin('error', 'Số phòng đã tồn tại');
        }

        $ok = $dao->create([
            'room_number'  => $roomNumber,
            'room_type_id' => $roomTypeId,
            'floor'        => $floor,
            'status'       => $status,
        ]);

        if (!$ok) {
            redirectRoomsAdmin('error', 'Không thể thêm phòng');
        }

        redirectRoomsAdmin('msg', 'Thêm phòng thành công');
        break;

    case 'update':
        requireAdmin();

        if ($method !== 'POST') {
            redirectRoomsAdmin('error', 'Phương thức không hợp lệ');
        }

        $id         = (int)($_POST['id'] ?? 0);
        $roomNumber = strtoupper(trim($_POST['room_number'] ?? ''));
        $roomTypeId = (int)($_POST['room_type_id'] ?? 0);
        $floor      = (int)($_POST['floor'] ?? 1);
        $status     = trim($_POST['status'] ?? 'available');

        if ($id <= 0 || !$dao->getById($id)) {
            redirectRoomsAdmin('error', 'Phòng không tồn tại');
        }
        if (!preg_match('/^[A-Z0-9-]{1,10}$/', $roomNumber)) {
            redirectRoomsAdmin('error', 'Số phòng chỉ gồm chữ, số hoặc dấu gạch ngang (tối đa 10 ký tự)');
        }
        if ($roomTypeId <= 0 || !roomTypeExists($db, $roomTypeId)) {
            redirectRoomsAdmin('error', 'Loại phòng không hợp lệ');
        }
        if ($floor < 0 || $floor > 100) {
            redirectRoomsAdmin('error', 'Tầng phải từ 0 đến 100');
        }
        if (!in_array($status, ['available', 'booked', 'maintenance'], true)) {
            redirectRoomsAdmin('error', 'Trạng thái phòng không hợp lệ');
        }
        if ($dao->existsRoomNumber($roomNumber, $id)) {
            redirectRoomsAdmin('error', 'Số phòng đã tồn tại');
        }

        $ok = $dao->update($id, [
            'room_number'  => $roomNumber,
            'room_type_id' => $roomTypeId,
            'floor'        => $floor,
            'status'       => $status,
        ]);

        if (!$ok) {
            redirectRoomsAdmin('error', 'Không thể cập nhật phòng');
        }

        redirectRoomsAdmin('msg', 'Cập nhật phòng thành công');
        break;

    case 'delete':
        requireAdmin();

        if ($method !== 'POST') {
            redirectRoomsAdmin('error', 'Phương thức không hợp lệ');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || !$dao->getById($id)) {
            redirectRoomsAdmin('error', 'Phòng không tồn tại');
        }

        $ok = $dao->delete($id);
        if (!$ok) {
            redirectRoomsAdmin('error', 'Không thể xóa phòng');
        }

        redirectRoomsAdmin('msg', 'Xóa phòng thành công');
        break;

    case 'search_available':
        header('Content-Type: application/json; charset=utf-8');

        $checkIn    = trim($_GET['check_in'] ?? '');
        $checkOut   = trim($_GET['check_out'] ?? '');
        $guests     = (int)($_GET['guests'] ?? 0);
        $roomTypeId = (int)($_GET['room_type_id'] ?? 0);

        if (!isValidDate($checkIn) || !isValidDate($checkOut)) {
            http_response_code(400);
            echo json_encode(['error' => 'Định dạng ngày không hợp lệ'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($checkIn < date('Y-m-d')) {
            http_response_code(400);
            echo json_encode(['error' => 'Ngày nhận phòng không được nhỏ hơn hôm nay'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($checkIn >= $checkOut) {
            http_response_code(400);
            echo json_encode(['error' => 'Ngày trả phòng phải sau ngày nhận phòng'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($guests < 0 || $guests > 20) {
            http_response_code(400);
            echo json_encode(['error' => 'Số khách không hợp lệ'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $rows = $dao->getAvailableRooms($checkIn, $checkOut, max(0, $guests), max(0, $roomTypeId));
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        exit;

    default:
        header('Location: ' . BASE_URL . '/pages/index.php');
        exit;
}
