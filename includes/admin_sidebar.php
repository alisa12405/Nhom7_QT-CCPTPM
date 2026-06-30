<?php
/**
 * includes/admin_sidebar.php — Sidebar dùng chung cho tất cả trang admin
 * Đã được load BASE_URL từ header.php trước đó
 */
$sidebarPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="admin-sidebar" aria-label="Menu quản trị">

  <p class="admin-sidebar__label">Tổng quan</p>
  <a href="<?= BASE_URL ?>/admin/dashboard.php"
     class="<?= $sidebarPage === 'dashboard' ? 'active' : '' ?>">
    <i data-lucide="layout-dashboard" width="16" height="16"></i>
    Dashboard
  </a>

  <p class="admin-sidebar__label">Quản lý</p>
  <a href="<?= BASE_URL ?>/admin/users.php"
     class="<?= $sidebarPage === 'users' ? 'active' : '' ?>">
    <i data-lucide="users" width="16" height="16"></i>
    Người dùng
  </a>
  <a href="<?= BASE_URL ?>/admin/rooms.php"
     class="<?= $sidebarPage === 'rooms' ? 'active' : '' ?>">
    <i data-lucide="bed-double" width="16" height="16"></i>
    Phòng
  </a>
  <a href="<?= BASE_URL ?>/admin/room_types.php"
     class="<?= $sidebarPage === 'room_types' ? 'active' : '' ?>">
    <i data-lucide="tag" width="16" height="16"></i>
    Loại phòng
  </a>
  <a href="<?= BASE_URL ?>/admin/bookings.php"
     class="<?= $sidebarPage === 'bookings' ? 'active' : '' ?>">
    <i data-lucide="calendar-check" width="16" height="16"></i>
    Đặt phòng
  </a>
  <a href="<?= BASE_URL ?>/admin/amenities.php"
     class="<?= $sidebarPage === 'amenities' ? 'active' : '' ?>">
    <i data-lucide="sparkles" width="16" height="16"></i>
    Tiện ích
  </a>

  <p class="admin-sidebar__label">Tài khoản</p>
  <a href="<?= BASE_URL ?>/pages/profile.php">
    <i data-lucide="user-cog" width="16" height="16"></i>
    Hồ sơ cá nhân
  </a>
  <a href="<?= BASE_URL ?>/api/auth.php?action=logout"
     style="color:var(--color-error) !important"
     onclick="return confirm('Xác nhận đăng xuất?')">
    <i data-lucide="log-out" width="16" height="16"></i>
    Đăng xuất
  </a>

</aside>
