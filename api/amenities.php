<?php
// api/amenities.php
session_start();
require_once 'config/db.php'; 

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$database = new Database();
$db = $database->getConnection();

switch ($action) {
    case 'add':
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $icon = $_POST['icon'] ?? 'fas fa-star';

        try {
            $stmt = $db->prepare("INSERT INTO amenities (name, description, icon) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $description, $icon])) {
                $_SESSION['success'] = 'Thêm tiện ích thành công!';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
        }
        header('Location: ../admin/amenities.php');
        exit;

    case 'edit':
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $icon = $_POST['icon'] ?? 'fas fa-star';

        try {
            $stmt = $db->prepare("UPDATE amenities SET name=?, description=?, icon=? WHERE id=?");
            if ($stmt->execute([$name, $description, $icon, $id])) {
                $_SESSION['success'] = 'Cập nhật thành công!';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
        }
        header('Location: ../admin/amenities.php');
        exit;

    case 'delete':
        // Lấy ID từ cả GET (nếu bấm link) và POST (nếu dùng form)
        $id = $_GET['id'] ?? $_POST['id'] ?? 0;

        try {
            $stmt = $db->prepare("DELETE FROM amenities WHERE id=?");
            if ($stmt->execute([$id])) {
                $_SESSION['success'] = 'Xóa tiện ích thành công!';
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
        }
        header('Location: ../admin/amenities.php');
        exit;

    default:
        header('Location: ../admin/amenities.php');
        exit;
}