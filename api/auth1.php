<?php
session_start();
require_once 'config/db.php';

$action = $_GET['action'] ?? '';
$db_obj = new Database();
$db     = $db_obj->getConnection();

switch ($action) {

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/pages/login.php"); exit;
        }
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            header("Location: " . BASE_URL . "/pages/login.php?error=Vui+lòng+nhập+đầy+đủ+thông+tin"); exit;
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            $redirect = $user['role'] === 'admin' ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/pages/index.php';
            header("Location: $redirect?msg=Dang+nhap+thanh+cong");
        } else {
            header("Location: " . BASE_URL . "/pages/login.php?error=Email+hoac+mat+khau+khong+dung");
        }
        exit;

    case 'register':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/pages/register.php"); exit;
        }
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = trim($_POST['password'] ?? '');
        $confirm   = trim($_POST['confirm_password'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');

        $errors = [];
        if (empty($full_name))                           $errors[] = "Họ tên không được để trống";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = "Email không hợp lệ";
        if (strlen($password) < 6)                       $errors[] = "Mật khẩu phải ≥ 6 ký tự";
        if ($password !== $confirm)                      $errors[] = "Mật khẩu xác nhận không khớp";

        if (!empty($errors)) {
            header("Location: " . BASE_URL . "/pages/register.php?error=" . urlencode(implode('; ', $errors)));
            exit;
        }

        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            header("Location: " . BASE_URL . "/pages/register.php?error=Email+da+duoc+su+dung"); exit;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt   = $db->prepare(
            "INSERT INTO users (full_name, email, password, phone, role) VALUES (?,?,?,?,'user')"
        );
        if ($stmt->execute([$full_name, $email, $hashed, $phone])) {
            header("Location: " . BASE_URL . "/pages/login.php?msg=Dang+ky+thanh+cong!+Vui+long+dang+nhap");
        } else {
            header("Location: " . BASE_URL . "/pages/register.php?error=Dang+ky+that+bai");
        }
        exit;

    case 'logout':
        session_destroy();
        header("Location: " . BASE_URL . "/pages/login.php?msg=Đã+đăng+xuất");
        exit;

    default:
        header("Location: " . BASE_URL . "/pages/login.php"); exit;
}
?>