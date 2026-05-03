<?php
/**
 * Passenger Sidebar Partial
 * includes/sidebar-passenger.php
 *
 * PHP integration point:
 * - $current_page: set in each page file to highlight active nav item
 * - $passenger_name: pulled from session (e.g. $_SESSION['user_name'])
 */

$current_page   = $current_page   ?? 'dashboard';
$passenger_name = $passenger_name ?? 'Passenger';
$passenger_initials = strtoupper(substr(explode(' ', $passenger_name)[0], 0, 1) . (isset(explode(' ', $passenger_name)[1]) ? substr(explode(' ', $passenger_name)[1], 0, 1) : ''));

$nav_items = [
  ['id' => 'dashboard',         'label' => 'Dashboard',          'href' => 'dashboard.php',  'icon' => 'grid'],
  ['id' => 'passes',            'label' => 'My Passes',           'href' => 'passes.php',     'icon' => 'credit-card'],
  ['id' => 'trip-history',      'label' => 'Trip History',        'href' => 'trips.php',      'icon' => 'clock'],
  ['id' => 'routes-schedules',  'label' => 'Routes & Schedules',  'href' => 'routes.php',     'icon' => 'map'],
  ['id' => 'profile',           'label' => 'Profile',             'href' => 'profile.php',    'icon' => 'user'],
  ['id' => 'settings',          'label' => 'Settings',            'href' => 'settings.php',   'icon' => 'settings'],
];

$icons = [
  'grid'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
  'credit-card' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>',
  'clock'       => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
  'map'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/></svg>',
  'user'        => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
  'settings'    => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
  'logout'      => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
  'bus'         => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M4 16c0 .88.39 1.67 1 2.22V20a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1h8v1a1 1 0 0 0 1 1h1a1 1 0 0 0 1-1v-1.78c.61-.55 1-1.34 1-2.22V6c0-3.5-3.58-4-8-4s-8 .5-8 4v10zm3.5 1a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm9 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zM6 6h12v4H6V6z"/></svg>',
];
?>

<aside class="sidebar" id="sidebar" role="navigation" aria-label="Passenger Navigation">

  <!-- Logo -->
  <div class="sidebar-logo">
    <div class="logo-icon" aria-hidden="true">
      <?php echo $icons['bus']; ?>
    </div>
    <span class="brand-name">MetroLink</span>
  </div>

  <!-- User Welcome -->
  <div class="sidebar-user">
    <div class="sidebar-avatar" aria-hidden="true"><?php echo htmlspecialchars($passenger_initials); ?></div>
    <div class="sidebar-welcome">
      Welcome,
      <strong><?php echo htmlspecialchars($passenger_name); ?></strong>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav" aria-label="Main menu">
    <ul>
      <?php foreach ($nav_items as $item): ?>
        <li>
          <a href="<?php echo htmlspecialchars($item['href']); ?>"
             class="<?php echo $current_page === $item['id'] ? 'active' : ''; ?>"
             aria-current="<?php echo $current_page === $item['id'] ? 'page' : 'false'; ?>">
            <?php echo $icons[$item['icon']]; ?>
            <?php echo htmlspecialchars($item['label']); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <!-- Logout -->
  <div class="sidebar-footer">
    <!-- PHP integration point: logout action should invalidate session -->
    <form method="post" action="../auth/logout.php">
      <button type="submit" class="btn-logout">
        <?php echo $icons['logout']; ?>
        Log Out
      </button>
    </form>
  </div>

</aside>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebar-overlay" aria-hidden="true"></div>
