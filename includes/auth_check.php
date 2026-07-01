<?php
/**
 * Dùng chung toàn nhóm — require_once file này TRƯỚC header.php
 * khi trang cần kiểm tra quyền truy cập.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
// binh sưa auth_check
// Cần BASE_URL để redirect đúng — load config nếu chưa có
if (!defined('BASE_URL')) {
    require_once dirname(__DIR__) . '/api/config/config.php';
}

/**
 * Yêu cầu đã đăng nhập. Nếu chưa → redirect về login.
 */
function requireLogin(string $redirect = '') {
    if (empty($redirect)) $redirect = BASE_URL . '/pages/login.php';
    if (!isset($_SESSION['user'])) {
        header('Location: ' . $redirect . '?error=' . urlencode('Vui lòng đăng nhập để tiếp tục'));
        exit;
    }
}

/**
 * Yêu cầu quyền admin. Nếu không đủ quyền → redirect.
 */
function requireAdmin(string $redirect = '') {
    if (empty($redirect)) $redirect = BASE_URL . '/pages/login.php';
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header('Location: ' . $redirect . '?error=' . urlencode('Bạn không có quyền truy cập trang này'));
        exit;
    }
}

/** Kiểm tra có phải admin không */
function isAdmin(): bool {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}
?>
