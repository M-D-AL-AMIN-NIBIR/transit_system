<?php
/**
 * Admin Sidebar Partial
 * includes/sidebar-admin.php
 *
 * PHP integration point:
 * - $admin_current_page: set in each admin page file to highlight active nav
 */

$admin_current_page = $admin_current_page ?? 'fleet';
$admin_name = $admin_name ?? ($_SESSION['user_name'] ?? 'Admin');
$admin_initials = strtoupper(substr(explode(' ', $admin_name)[0], 0, 1) . (isset(explode(' ', $admin_name)[1]) ? substr(explode(' ', $admin_name)[1], 0, 1) : ''));

$admin_nav = [
  ['id' => 'dashboard',  'label' => 'Dashboard',       'href' => 'dashboard.php', 'icon' => 'grid'],
  ['id' => 'fleet',      'label' => 'Fleet & Routes',  'href' => 'fleet.php',     'icon' => 'truck'],
  ['id' => 'users',      'label' => 'Users',           'href' => 'users.php',     'icon' => 'users'],
  ['id' => 'schedules',  'label' => 'Schedules',       'href' => 'schedules.php', 'icon' => 'calendar'],
  ['id' => 'reports',    'label' => 'Reports',         'href' => 'reports.php',   'icon' => 'bar-chart'],
  ['id' => 'settings',   'label' => 'Settings',        'href' => 'settings.php',  'icon' => 'settings'],
];

$icons = [
  'grid'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
  'truck'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>',
  'users'     => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  'calendar'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
  'bar-chart' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/></svg>',
  'settings'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
  'bus'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4 16c0 .88.39 1.67 1 2.22V20a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h8v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4s-8 .5-8 4v10zm3.5 1a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm9 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zM6 6h12v4H6V6z"/></svg>',
];
?>

<aside class="sidebar" id="sidebar" role="navigation" aria-label="Admin Navigation">

  <!-- Logo -->
  <div class="sidebar-logo">
    <div class="logo-icon" aria-hidden="true">
      <?php echo $icons['bus']; ?>
    </div>
    <span class="brand-name">MetroLink</span>
  </div>

  <!-- User Welcome -->
  <div class="sidebar-user">
    <div class="sidebar-avatar" aria-hidden="true"><?php echo htmlspecialchars($admin_initials); ?></div>
    <div class="sidebar-welcome">
      Welcome,
      <strong><?php echo htmlspecialchars($admin_name); ?></strong>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav" aria-label="Admin menu">
    <ul>
      <?php foreach ($admin_nav as $item): ?>
        <li>
          <a href="<?php echo htmlspecialchars($item['href']); ?>"
             class="<?php echo $admin_current_page === $item['id'] ? 'active' : ''; ?>"
             aria-current="<?php echo $admin_current_page === $item['id'] ? 'page' : 'false'; ?>">
            <?php echo $icons[$item['icon']]; ?>
            <?php echo htmlspecialchars($item['label']); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <!-- Logout -->
  <div class="sidebar-footer">
    <form method="post" action="../auth/logout.php">
      <button type="submit" class="btn-logout">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Log Out
      </button>
    </form>
  </div>

</aside>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>
