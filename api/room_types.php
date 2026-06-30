<?php
session_start();

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/dao/RoomTypeDAO.php';

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$db     = (new Database())->getConnection();
$dao    = new RoomTypeDAO($db);

function redirectRoomTypesAdmin(string $type, string $message): void {
    $query = $type . '=' . urlencode($message);
    header('Location: ' . BASE_URL . '/admin/room_types.php?' . $query);
    exit;
}

function roomTypeImageDirectory(): string {
    return ROOT_PATH . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'rooms';
}

function detectImageExtension(string $tmpPath): ?string {
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);
            $mime = is_string($detected) ? $detected : '';
        }
    }

    if ($mime === '' && function_exists('mime_content_type')) {
        $detected = mime_content_type($tmpPath);
        $mime = is_string($detected) ? $detected : '';
    }

    return $allowedMimes[$mime] ?? null;
}

function handleRoomTypeImageUpload(?array $file): array {
    if (!is_array($file) || !isset($file['error'])) {
        return [null, null];
    }

    $errorCode = (int)$file['error'];
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return [null, 'Không thể tải ảnh lên'];
    }

    $tmpPath = $file['tmp_name'] ?? '';
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return [null, 'Tệp ảnh không hợp lệ'];
    }

    $size = (int)($file['size'] ?? 0);
    if ($size <= 0) {
        return [null, 'Ảnh tải lên đang bị trống'];
    }
    if ($size > 5 * 1024 * 1024) {
        return [null, 'Ảnh vượt quá 5MB'];
    }

    $extension = detectImageExtension($tmpPath);
    if ($extension === null) {
        return [null, 'Định dạng ảnh không hợp lệ (chỉ nhận JPG, PNG, WEBP, GIF)'];
    }

    $directory = roomTypeImageDirectory();
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return [null, 'Không thể tạo thư mục lưu ảnh'];
    }

    try {
        $random = bin2hex(random_bytes(8));
    } catch (Throwable $e) {
        $random = str_replace('.', '', uniqid('', true));
    }

    $fileName = 'room_type_' . date('Ymd_His') . '_' . $random . '.' . $extension;
    $targetPath = $directory . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        return [null, 'Không thể lưu ảnh tải lên'];
    }

    return [$fileName, null];
}

function deleteLocalRoomTypeImage(string $image): void {
    $image = trim($image);
    if ($image === '') {
        return;
    }

    if (strpos($image, '://') !== false || substr($image, 0, 1) === '/') {
        return;
    }

    $fileName = basename($image);
    if ($fileName === '' || $fileName === '.' || $fileName === '..') {
        return;
    }

    $path = roomTypeImageDirectory() . DIRECTORY_SEPARATOR . $fileName;
    if (is_file($path)) {
        @unlink($path);
    }
}

function isRoomTypeNameTaken(PDO $db, string $name, int $excludeId = 0): bool {
    $sql = 'SELECT id FROM room_types WHERE LOWER(name) = LOWER(?)';
    $params = [$name];

    if ($excludeId > 0) {
        $sql .= ' AND id <> ?';
        $params[] = $excludeId;
    }

    $sql .= ' LIMIT 1';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return (bool)$stmt->fetch();
}

function validateRoomTypeInput(array $input): array {
    $name        = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $priceRaw    = trim((string)($input['price_per_night'] ?? ''));
    $capacity    = (int)($input['capacity'] ?? 0);

    if ($name === '') {
        return [null, 'Tên loại phòng là bắt buộc'];
    }
    if (mb_strlen($name, 'UTF-8') > 100) {
        return [null, 'Tên loại phòng tối đa 100 ký tự'];
    }
    if ($priceRaw === '' || !is_numeric($priceRaw)) {
        return [null, 'Giá phòng không hợp lệ'];
    }

    $price = (float)$priceRaw;
    if ($price <= 0 || $price > 9999999999) {
        return [null, 'Giá phòng phải lớn hơn 0'];
    }

    if ($capacity < 1 || $capacity > 20) {
        return [null, 'Sức chứa phải từ 1 đến 20'];
    }
    if (mb_strlen($description, 'UTF-8') > 5000) {
        return [null, 'Mô tả quá dài'];
    }

    return [[
        'name' => $name,
        'description' => $description,
        'price_per_night' => $price,
        'capacity' => $capacity,
    ], null];
}

switch ($action) {
    case 'list':
        header('Content-Type: application/json; charset=utf-8');
        $search = trim($_GET['search'] ?? '');
        echo json_encode($dao->getAll($search), JSON_UNESCAPED_UNICODE);
        exit;

    case 'add':
        requireAdmin();

        if ($method !== 'POST') {
            redirectRoomTypesAdmin('error', 'Phương thức không hợp lệ');
        }

        [$data, $error] = validateRoomTypeInput($_POST);
        if ($error) {
            redirectRoomTypesAdmin('error', $error);
        }

        if (isRoomTypeNameTaken($db, $data['name'])) {
            redirectRoomTypesAdmin('error', 'Tên loại phòng đã tồn tại');
        }

        [$uploadedImage, $uploadError] = handleRoomTypeImageUpload($_FILES['image_file'] ?? null);
        if ($uploadError) {
            redirectRoomTypesAdmin('error', $uploadError);
        }

        $data['image'] = $uploadedImage ?? '';

        try {
            $ok = $dao->create($data);
        } catch (Throwable $e) {
            $ok = false;
        }

        if (!$ok) {
            if ($uploadedImage !== null) {
                deleteLocalRoomTypeImage($uploadedImage);
            }
            redirectRoomTypesAdmin('error', 'Không thể thêm loại phòng');
        }

        redirectRoomTypesAdmin('msg', 'Thêm loại phòng thành công');
        break;

    case 'update':
        requireAdmin();

        if ($method !== 'POST') {
            redirectRoomTypesAdmin('error', 'Phương thức không hợp lệ');
        }

        $id = (int)($_POST['id'] ?? 0);
        $currentRoomType = $id > 0 ? $dao->getById($id) : null;
        if ($id <= 0 || !$currentRoomType) {
            redirectRoomTypesAdmin('error', 'Loại phòng không tồn tại');
        }

        [$data, $error] = validateRoomTypeInput($_POST);
        if ($error) {
            redirectRoomTypesAdmin('error', $error);
        }

        if (isRoomTypeNameTaken($db, $data['name'], $id)) {
            redirectRoomTypesAdmin('error', 'Tên loại phòng đã tồn tại');
        }

        [$uploadedImage, $uploadError] = handleRoomTypeImageUpload($_FILES['image_file'] ?? null);
        if ($uploadError) {
            redirectRoomTypesAdmin('error', $uploadError);
        }

        $oldImage = (string)($currentRoomType['image'] ?? '');
        $data['image'] = $uploadedImage ?? $oldImage;

        try {
            $ok = $dao->update($id, $data);
        } catch (Throwable $e) {
            $ok = false;
        }

        if (!$ok) {
            if ($uploadedImage !== null) {
                deleteLocalRoomTypeImage($uploadedImage);
            }
            redirectRoomTypesAdmin('error', 'Không thể cập nhật loại phòng');
        }

        if ($uploadedImage !== null && $oldImage !== '' && $oldImage !== $uploadedImage) {
            deleteLocalRoomTypeImage($oldImage);
        }

        redirectRoomTypesAdmin('msg', 'Cập nhật loại phòng thành công');
        break;

    case 'delete':
        requireAdmin();

        if ($method !== 'POST') {
            redirectRoomTypesAdmin('error', 'Phương thức không hợp lệ');
        }

        $id = (int)($_POST['id'] ?? 0);
        $currentRoomType = $id > 0 ? $dao->getById($id) : null;
        if ($id <= 0 || !$currentRoomType) {
            redirectRoomTypesAdmin('error', 'Loại phòng không tồn tại');
        }

        try {
            $ok = $dao->delete($id);
        } catch (Throwable $e) {
            $ok = false;
        }

        if (!$ok) {
            redirectRoomTypesAdmin('error', 'Không thể xóa loại phòng');
        }

        deleteLocalRoomTypeImage((string)($currentRoomType['image'] ?? ''));

        redirectRoomTypesAdmin('msg', 'Xóa loại phòng thành công');
        break;

    default:
        header('Location: ' . BASE_URL . '/admin/room_types.php');
        exit;
}
