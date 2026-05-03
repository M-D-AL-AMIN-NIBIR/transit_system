<?php
/**
 * Admin Topbar Partial
 * includes/topbar-admin.php
 *
 * PHP integration point:
 * - $topbar_title: page title shown in the topbar (set in each admin page)
 * - $topbar_title_prefix: highlighted muted prefix (e.g. "Admin")
 * - $notification_count: unread notification count from DB
 * - $admin_name: current admin user name from session
 */

$topbar_title        = $topbar_title        ?? 'Dashboard';
$topbar_title_prefix = $topbar_title_prefix ?? 'Admin';
$notification_count  = $notification_count  ?? 0;
$admin_name          = $admin_name          ?? 'Admin';
$admin_initials      = strtoupper(substr($admin_name, 0, 2));
?>

<header class="admin-topbar" role="banner">
  <div class="topbar-left">
    <!-- Hamburger (mobile only) -->
    <button class="hamburger" id="hamburger" aria-label="Open navigation menu" aria-controls="sidebar" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>

    <h1 class="topbar-page-title">
      <span class="title-admin"><?php echo htmlspecialchars($topbar_title_prefix); ?></span>
      <?php echo ' ' . htmlspecialchars($topbar_title); ?>
    </h1>
  </div>

  <div class="topbar-right">
    <!-- Notification bell -->
    <!-- PHP integration point: fetch unread notifications count -->
    <div class="topbar-bell" role="button" tabindex="0" aria-label="<?php echo $notification_count; ?> unread notifications">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <?php if ($notification_count > 0): ?>
        <span class="bell-badge" aria-hidden="true"><?php echo min($notification_count, 99); ?></span>
      <?php endif; ?>
    </div>

    <!-- Avatar dropdown -->
    <div class="topbar-avatar-wrap" role="button" tabindex="0" aria-label="Admin account menu" aria-haspopup="true">
      <div class="topbar-avatar" aria-hidden="true"><?php echo htmlspecialchars($admin_initials); ?></div>
      <svg class="chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <polyline points="6 9 12 15 18 9"/>
      </svg>
    </div>
  </div>
</header>
