<?php
/**
 * includes/header.php — Header dùng chung toàn bộ dự án
 */
if (session_status() === PHP_SESSION_NONE) session_start();

require_once dirname(__DIR__) . '/api/config/config.php';
// toi la binh va toi sua header.php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$isAdmin     = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
$isLoggedIn  = isset($_SESSION['user']);
$user        = $_SESSION['user'] ?? null;
$pageTitle   = $pageTitle ?? 'LuxStay — Đặt phòng khách sạn';
?>
<!DOCTYPE html>
<html lang="vi" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400;1,600&display=swap" rel="stylesheet">
  <link href="https://api.fontshare.com/v2/css?f[]=satoshi@400,500,600,700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">

  <style>
    /* ── User chip (avatar + tên) ── */
    .user-chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 4px 10px 4px 4px;
      border-radius: 9999px;
      border: 1px solid var(--color-border);
      background: var(--color-surface);
      font-size: var(--text-sm);
      font-weight: 500;
      color: var(--color-text);
      cursor: default;
      white-space: nowrap;
    }
    .user-chip__avatar {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: var(--color-primary-highlight);
      color: var(--color-primary);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 12px;
      flex-shrink: 0;
    }

    /* ── Divider dọc giữa các action ── */
    .nav-divider {
      width: 1px;
      height: 20px;
      background: var(--color-border);
      flex-shrink: 0;
    }

    /* ── Link hành động inline (Tài khoản / Đăng xuất) ── */
    .nav-action-link {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 10px;
      border-radius: var(--radius-md);
      font-size: var(--text-sm);
      font-weight: 500;
      text-decoration: none;
      white-space: nowrap;
      transition: background var(--transition-interactive), color var(--transition-interactive);
    }
    .nav-action-link:hover { text-decoration: none; }
    .nav-action-link--default {
      color: var(--color-text-muted);
    }
    .nav-action-link--default:hover {
      background: var(--color-surface-offset);
      color: var(--color-text);
    }
    .nav-action-link--danger {
      color: var(--color-error);
    }
    .nav-action-link--danger:hover {
      background: var(--color-error-highlight);
      color: var(--color-error);
    }

    /* ── btn-icon (dark mode toggle) ── */
    .btn-icon {
      width: 36px; height: 36px;
      border-radius: var(--radius-md);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--color-text-muted);
      border: none; background: none; cursor: pointer;
      transition: background var(--transition-interactive), color var(--transition-interactive);
    }
    .btn-icon:hover {
      background: var(--color-surface-offset);
      color: var(--color-text);
    }
  </style>

  <script>
    (function () {
      var saved = localStorage.getItem('luxstay_theme');
      var pref  = saved || (matchMedia('(prefers-color-scheme:dark)').matches ? 'dark' : 'light');
      document.documentElement.setAttribute('data-theme', pref);
    })();
  </script>
</head>
<body>

<header>
  <nav class="navbar" role="navigation" aria-label="Điều hướng chính">
    <div class="container navbar__inner">

      <!-- Logo -->
      <a href="<?= BASE_URL ?>/pages/index.php" class="navbar__logo" aria-label="LuxStay — Trang chủ">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <rect x="2" y="14" width="10" height="12" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
          <rect x="16" y="8" width="10" height="18" rx="1.5" stroke="currentColor" stroke-width="1.5"/>
          <path d="M5 14V10a4 4 0 0 1 8 0v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          <circle cx="7"  cy="19" r="1" fill="currentColor"/>
          <circle cx="11" cy="19" r="1" fill="currentColor"/>
          <circle cx="20" cy="14" r="1" fill="currentColor"/>
          <circle cx="22" cy="14" r="1" fill="currentColor"/>
        </svg>
        LuxStay
      </a>

      <!-- Menu chính -->
      <ul class="navbar__nav" id="main-nav" role="list">
        <li>
          <a href="<?= BASE_URL ?>/pages/index.php"
             class="<?= $currentPage === 'index' ? 'active' : '' ?>">Trang chủ</a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/pages/rooms.php"
             class="<?= $currentPage === 'rooms' ? 'active' : '' ?>">Phòng</a>
        </li>
        <li>
          <a href="<?= BASE_URL ?>/pages/amenities.php"
             class="<?= $currentPage === 'amenities' ? 'active' : '' ?>">Tiện ích</a>
        </li>
        <?php if ($isLoggedIn): ?>
          <li>
            <a href="<?= BASE_URL ?>/pages/my_bookings.php"
               class="<?= $currentPage === 'my_bookings' ? 'active' : '' ?>">Đặt phòng của tôi</a>
          </li>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
          <li>
            <a href="<?= BASE_URL ?>/admin/dashboard.php"
               class="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : '' ?>">
              Quản trị
            </a>
          </li>
        <?php endif; ?>
      </ul>

      <!-- Actions bên phải -->
      <div class="navbar__actions">

        <!-- Dark mode toggle -->
        <button class="btn-icon" data-theme-toggle aria-label="Chuyển giao diện tối/sáng">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
          </svg>
        </button>

        <?php if ($isLoggedIn): ?>

          <!-- Divider -->
          <div class="nav-divider" aria-hidden="true"></div>

          <!-- Avatar chip (chỉ hiển thị, không click) -->
          <span class="user-chip" title="<?= htmlspecialchars($user['email']) ?>">
            <span class="user-chip__avatar">
              <?= mb_strtoupper(mb_substr($user['full_name'], 0, 1, 'UTF-8'), 'UTF-8') ?>
            </span>
            <?= htmlspecialchars(explode(' ', trim($user['full_name']))[count(explode(' ', trim($user['full_name']))) - 1]) ?>
          </span>

          <!-- Tài khoản -->
          <a href="<?= BASE_URL ?>/pages/profile.php"
             class="nav-action-link nav-action-link--default"
             title="Hồ sơ cá nhân">
            <i data-lucide="user" width="15" height="15"></i>
            Tài khoản
          </a>

          <!-- Đăng xuất -->
          <a href="<?= BASE_URL ?>/api/auth.php?action=logout"
             class="nav-action-link nav-action-link--danger"
             title="Đăng xuất"
             onclick="return confirm('Xác nhận đăng xuất?')">
            <i data-lucide="log-out" width="15" height="15"></i>
            Đăng xuất
          </a>

        <?php else: ?>
          <a href="<?= BASE_URL ?>/pages/login.php"    class="btn btn-ghost btn-sm">Đăng nhập</a>
          <a href="<?= BASE_URL ?>/pages/register.php" class="btn btn-primary btn-sm">Đăng ký</a>
        <?php endif; ?>

        <!-- Hamburger (mobile) -->
        <button class="navbar__toggle" id="nav-toggle"
                aria-expanded="false" aria-controls="main-nav"
                aria-label="Mở/đóng menu">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
               stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="3"  y1="6"  x2="21" y2="6"/>
            <line x1="3"  y1="12" x2="21" y2="12"/>
            <line x1="3"  y1="18" x2="21" y2="18"/>
          </svg>
        </button>

      </div>
    </div>
  </nav>
</header>

<main id="main-content">
