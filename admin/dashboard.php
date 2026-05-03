<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$admin_current_page  = 'dashboard';
$topbar_title        = 'Dashboard';
$topbar_title_prefix = 'Admin';
$notification_count  = 0;
$admin_name          = $_SESSION['user_name'] ?? 'Admin';
$assets              = '../assets';

// Fetch stats
$totalUsers      = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'passenger'")->fetchColumn();
$totalVehicles   = (int)$pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$activeVehicles  = (int)$pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'active'")->fetchColumn();
$totalRoutes     = (int)$pdo->query("SELECT COUNT(*) FROM routes WHERE status = 'active'")->fetchColumn();
$totalTrips      = (int)$pdo->query("SELECT COUNT(*) FROM trips")->fetchColumn();
$tripsToday      = (int)$pdo->query("SELECT COUNT(*) FROM trips WHERE DATE(start_time) = CURDATE()")->fetchColumn();
$revenue         = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();
$activePasses    = (int)$pdo->query("SELECT COUNT(*) FROM passes WHERE status = 'active'")->fetchColumn();

$recentUsers = $pdo->query(
    "SELECT name, email, created_at FROM users
     WHERE role = 'passenger' ORDER BY created_at DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — MetroLink Admin</title>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/style.css"/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/admin.css"/>
  <style>
    .stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:24px;}
    .stat-card{background:#fff;border-radius:10px;padding:18px;box-shadow:0 1px 3px rgba(0,0,0,.08);border-left:4px solid #667eea;}
    .stat-label{color:#6b7280;font-size:.85rem;margin-bottom:6px;}
    .stat-value{font-size:1.75rem;font-weight:700;color:#0f172a;}
    .stat-sub{color:#6b7280;font-size:.8rem;margin-top:4px;}
    .stat-card.green{border-left-color:#10b981;}
    .stat-card.orange{border-left-color:#f59e0b;}
    .stat-card.red{border-left-color:#ef4444;}
  </style>
</head>
<body>
<div class="layout">
  <?php include '../includes/sidebar-admin.php'; ?>
  <main class="main-content" id="main-content">
    <?php include '../includes/topbar-admin.php'; ?>
    <div style="padding:0 24px 24px;">

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label">Total Passengers</div>
          <div class="stat-value"><?php echo $totalUsers; ?></div>
        </div>
        <div class="stat-card green">
          <div class="stat-label">Active Vehicles</div>
          <div class="stat-value"><?php echo $activeVehicles; ?></div>
          <div class="stat-sub">of <?php echo $totalVehicles; ?> total</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-label">Active Routes</div>
          <div class="stat-value"><?php echo $totalRoutes; ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Active Passes</div>
          <div class="stat-value"><?php echo $activePasses; ?></div>
        </div>
        <div class="stat-card green">
          <div class="stat-label">Trips Today</div>
          <div class="stat-value"><?php echo $tripsToday; ?></div>
          <div class="stat-sub"><?php echo $totalTrips; ?> all-time</div>
        </div>
        <div class="stat-card orange">
          <div class="stat-label">Total Revenue</div>
          <div class="stat-value">৳<?php echo number_format($revenue, 2); ?></div>
        </div>
      </div>

      <div class="admin-content-grid">
        <section class="card">
          <h2 class="card-title">Recent Passenger Signups</h2>
          <?php if (empty($recentUsers)): ?>
            <p style="color:#6b7280;">No passenger accounts yet.</p>
          <?php else: ?>
            <div class="table-wrapper">
              <table class="fleet-table">
                <thead>
                  <tr><th>Name</th><th>Email</th><th>Joined</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($recentUsers as $u): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($u['name']); ?></td>
                      <td><?php echo htmlspecialchars($u['email']); ?></td>
                      <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($u['created_at']))); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </section>

        <aside class="card">
          <h2 class="card-title">Quick Actions</h2>
          <div style="display:flex;flex-direction:column;gap:10px;">
            <a href="fleet.php" class="btn btn-primary">Manage Fleet</a>
            <a href="schedules.php" class="btn btn-primary">Manage Schedules</a>
            <a href="users.php" class="btn btn-primary">Manage Users</a>
            <a href="reports.php" class="btn btn-primary">View Reports</a>
          </div>
        </aside>
      </div>
    </div>
  </main>
</div>
<script src="<?php echo $assets; ?>/js/main.js"></script>
</body>
</html>
