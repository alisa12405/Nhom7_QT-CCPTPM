<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

function isApiRequest(): bool {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    $ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if (stripos($accept, 'application/json') !== false) return true;
    if (stripos($xhr, 'XMLHttpRequest') !== false) return true;
    if (stripos($ua, 'Apache-HttpClient') !== false) return true;

    return false;
}

function jsonResponse(bool $status, string $message, int $httpCode = 200, array $extra = []): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function respond(bool $success, string $message, string $redirectUrl, int $httpCode = 302, array $extra = []): void {
    if (isApiRequest()) {
        jsonResponse($success, $message, $httpCode, $extra);
    }

    header("Location: $redirectUrl");
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$database = new Database();
$db = $database->getConnection();

switch ($action) {

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(false, 'Phương thức không hợp lệ', BASE_URL . '/pages/login.php', 405);
        }

        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            respond(false, 'Vui lòng nhập đầy đủ thông tin',
                BASE_URL . '/pages/login.php?error=Vui+lòng+nhập+đầy+đủ+thông+tin', 400);
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;

            $redirect = $user['role'] === 'admin'
                ? BASE_URL . '/admin/dashboard.php'
                : BASE_URL . '/pages/index.php';

            respond(true, 'Đăng nhập thành công',
                $redirect . '?msg=Dang+nhap+thanh+cong',
                200,
                ['role' => $user['role']]
            );
        } else {
            respond(false, 'Email hoặc mật khẩu không đúng',
                BASE_URL . '/pages/login.php?error=Email+hoac+mat+khau+khong+dung', 401);
        }
        break;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(false, 'Phương thức không hợp lệ', BASE_URL . '/pages/register.php', 405);
        }

        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirm_password'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        $errors = [];
        if (empty($full_name)) $errors[] = "Họ tên không được để trống";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ";
        if (strlen($password) < 6) $errors[] = "Mật khẩu phải >= 6 ký tự";
        if ($password !== $confirm) $errors[] = "Mật khẩu xác nhận không khớp";

        if (!empty($errors)) {
            $msg = implode('; ', $errors);
            respond(false, $msg,
                BASE_URL . '/pages/register.php?error=' . urlencode($msg), 422);
        }

        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->fetch()) {
            respond(false, 'Email đã được sử dụng',
                BASE_URL . '/pages/register.php?error=Email+da+duoc+su+dung', 409);
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            "INSERT INTO users (full_name, email, password, phone, role) VALUES (?,?,?,?,'user')"
        );

        if ($stmt->execute([$full_name, $email, $hashed, $phone])) {
            respond(true, 'Đăng ký thành công! Vui lòng đăng nhập',
                BASE_URL . '/pages/login.php?msg=Dang+ky+thanh+cong!+Vui+long+dang+nhap', 201);
        } else {
            respond(false, 'Đăng ký thất bại',
                BASE_URL . '/pages/register.php?error=Dang+ky+that+bai', 500);
        }
        break;

    case 'logout':
        session_destroy();
        respond(true, 'Đã đăng xuất',
            BASE_URL . '/pages/login.php?msg=Da+dang+xuat', 200);
        break;

    default:
        respond(false, 'Action không hợp lệ',
            BASE_URL . '/pages/login.php', 400);
        break;
}
?>