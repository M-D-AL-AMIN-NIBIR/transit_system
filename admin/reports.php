<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$admin_current_page  = 'reports';
$topbar_title        = 'Reports';
$topbar_title_prefix = 'Admin';
$notification_count  = 0;
$admin_name          = $_SESSION['user_name'] ?? 'Admin';
$assets              = '../assets';

// Trips per route
$tripsPerRoute = $pdo->query(
    "SELECT r.route_name, r.origin, r.destination,
            COUNT(t.trip_id) AS trip_count,
            COALESCE(SUM(t.fare_deducted), 0) AS total_fare
     FROM routes r
     LEFT JOIN trips t ON t.route_id = r.route_id
     GROUP BY r.route_id
     ORDER BY trip_count DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Revenue by method
$revenueByMethod = $pdo->query(
    "SELECT method, COUNT(*) AS count, COALESCE(SUM(amount), 0) AS total
     FROM payments WHERE status = 'completed'
     GROUP BY method
     ORDER BY total DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Passenger activity
$topPassengers = $pdo->query(
    "SELECT u.name, u.email, COUNT(t.trip_id) AS trip_count
     FROM users u
     LEFT JOIN trips t ON t.user_id = u.user_id
     WHERE u.role = 'passenger'
     GROUP BY u.user_id
     HAVING trip_count > 0
     ORDER BY trip_count DESC LIMIT 10"
)->fetchAll(PDO::FETCH_ASSOC);

$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'completed'")->fetchColumn();
$totalTrips   = (int)$pdo->query("SELECT COUNT(*) FROM trips")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reports — MetroLink Admin</title>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/style.css"/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/admin.css"/>
</head>
<body>
<div class="layout">
  <?php include '../includes/sidebar-admin.php'; ?>
  <main class="main-content" id="main-content">
    <?php include '../includes/topbar-admin.php'; ?>
    <div class="admin-content-grid">

      <section class="card" style="grid-column:1 / -1;">
        <h2 class="card-title">Summary</h2>
        <p><strong>Total Trips:</strong> <?php echo $totalTrips; ?> &nbsp;|&nbsp; <strong>Total Revenue:</strong> ৳<?php echo number_format($totalRevenue, 2); ?></p>
      </section>

      <section class="card" style="grid-column:1 / -1;">
        <h2 class="card-title">Trips per Route</h2>
        <?php if (empty($tripsPerRoute)): ?>
          <p style="color:#6b7280;">No route data available.</p>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="fleet-table">
              <thead>
                <tr><th>Route</th><th>Origin → Destination</th><th>Trips</th><th>Revenue</th></tr>
              </thead>
              <tbody>
                <?php foreach ($tripsPerRoute as $r): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($r['route_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['origin'] . ' → ' . $r['destination']); ?></td>
                    <td><?php echo $r['trip_count']; ?></td>
                    <td>৳<?php echo number_format($r['total_fare'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <section class="card">
        <h2 class="card-title">Revenue by Payment Method</h2>
        <?php if (empty($revenueByMethod)): ?>
          <p style="color:#6b7280;">No payment data yet.</p>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="fleet-table">
              <thead>
                <tr><th>Method</th><th>Transactions</th><th>Total</th></tr>
              </thead>
              <tbody>
                <?php foreach ($revenueByMethod as $m): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($m['method']); ?></td>
                    <td><?php echo $m['count']; ?></td>
                    <td>৳<?php echo number_format($m['total'], 2); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <section class="card">
        <h2 class="card-title">Top Passengers</h2>
        <?php if (empty($topPassengers)): ?>
          <p style="color:#6b7280;">No passenger activity yet.</p>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="fleet-table">
              <thead>
                <tr><th>Name</th><th>Email</th><th>Trips</th></tr>
              </thead>
              <tbody>
                <?php foreach ($topPassengers as $p): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><?php echo htmlspecialchars($p['email']); ?></td>
                    <td><?php echo $p['trip_count']; ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

    </div>
  </main>
</div>
<script src="<?php echo $assets; ?>/js/main.js"></script>
</body>
</html>
