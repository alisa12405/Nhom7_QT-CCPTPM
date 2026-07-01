<?php
session_start();
require_once 'config/db.php';
require_once '../includes/auth_check.php';

$action = $_GET['action'] ?? '';
$db_obj = new Database();
$db     = $db_obj->getConnection();
// sua user
switch ($action) {

    // THÊM USER (admin)
    case 'admin_add':
        requireAdmin();
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $password  = trim($_POST['password'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $role      = in_array($_POST['role'] ?? '', ['user','admin']) ? $_POST['role'] : 'user';

        if (empty($full_name) || empty($email) || empty($password)) {
            header("Location: " . BASE_URL . "/admin/users.php?error=Thiếu+thông+tin+bắt+buộc"); exit;
        }
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            header("Location: " . BASE_URL . "/admin/users.php?error=Email+đã+tồn+tại"); exit;
        }
        $stmt = $db->prepare(
            "INSERT INTO users (full_name,email,password,phone,role) VALUES (?,?,?,?,?)"
        );
        if ($stmt->execute([$full_name, $email, password_hash($password, PASSWORD_DEFAULT), $phone, $role])) {
            header("Location: " . BASE_URL . "/admin/users.php?msg=Thêm+người+dùng+thành+công");
        } else {
            header("Location: " . BASE_URL . "/admin/users.php?error=Thêm+thất+bại");
        }
        exit;

    // SỬA USER (admin)
    case 'admin_update':
        requireAdmin();
        $id        = (int)($_POST['id'] ?? 0);
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $role      = in_array($_POST['role'] ?? '', ['user','admin']) ? $_POST['role'] : 'user';
        $password  = trim($_POST['password'] ?? '');

        if (!$id || empty($full_name) || empty($email)) {
            header("Location: " . BASE_URL . "/admin/users.php?error=Dữ+liệu+không+hợp+lệ"); exit;
        }
        $check = $db->prepare("SELECT id FROM users WHERE email=? AND id!=?");
        $check->execute([$email, $id]);
        if ($check->fetch()) {
            header("Location: " . BASE_URL . "/admin/users.php?error=Email+đã+dùng+bởi+tài+khoản+khác"); exit;
        }
        if (!empty($password)) {
            $stmt = $db->prepare(
                "UPDATE users SET full_name=?,email=?,phone=?,role=?,password=? WHERE id=?"
            );
            $stmt->execute([$full_name,$email,$phone,$role,password_hash($password,PASSWORD_DEFAULT),$id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name=?,email=?,phone=?,role=? WHERE id=?");
            $stmt->execute([$full_name, $email, $phone, $role, $id]);
        }
        header("Location: " . BASE_URL . "/admin/users.php?msg=Cập+nhật+thành+công");
        exit;

    // XÓA USER (admin)
    case 'admin_delete':
        requireAdmin();
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { header("Location: " . BASE_URL . "/admin/users.php?error=ID+không+hợp+lệ"); exit; }
        if ($id === (int)$_SESSION['user']['id']) {
            header("Location: " . BASE_URL . "/admin/users.php?error=Không+thể+xóa+tài+khoản+đang+dùng"); exit;
        }
        $stmt = $db->prepare("DELETE FROM users WHERE id=?");
        if ($stmt->execute([$id])) {
            header("Location: " . BASE_URL . "/admin/users.php?msg=Xóa+thành+công");
        } else {
            header("Location: " . BASE_URL . "/admin/users.php?error=Xóa+thất+bại");
        }
        exit;

    // CẬP NHẬT PROFILE (user tự sửa)
    case 'update_profile':
        requireLogin(BASE_URL . "/pages/login.php");
        $id        = (int)$_SESSION['user']['id'];
        $full_name = trim($_POST['full_name'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $password  = trim($_POST['password'] ?? '');
        $confirm   = trim($_POST['confirm_password'] ?? '');

        if (empty($full_name)) {
            header("Location: " . BASE_URL . "/pages/profile.php?error=Họ+tên+không+được+để+trống"); exit;
        }
        if (!empty($password)) {
            if (strlen($password) < 6) {
                header("Location: " . BASE_URL . "/pages/profile.php?error=Mật+khẩu+phải+≥+6+ký+tự"); exit;
            }
            if ($password !== $confirm) {
                header("Location: " . BASE_URL . "/pages/profile.php?error=Mật+khẩu+xác+nhận+không+khớp"); exit;
            }
            $stmt = $db->prepare("UPDATE users SET full_name=?,phone=?,password=? WHERE id=?");
            $stmt->execute([$full_name,$phone,password_hash($password,PASSWORD_DEFAULT),$id]);
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name=?,phone=? WHERE id=?");
            $stmt->execute([$full_name, $phone, $id]);
        }
        $_SESSION['user']['full_name'] = $full_name;
        $_SESSION['user']['phone']     = $phone;
        header("Location: " . BASE_URL . "/pages/profile.php?msg=Cập+nhật+thành+công");
        exit;

    default:
        header("Location: " . BASE_URL . "/pages/login.php"); exit;
}
?>