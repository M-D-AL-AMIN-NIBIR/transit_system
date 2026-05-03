<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$current_page   = 'dashboard';
$passenger_name = $_SESSION['user_name'] ?? 'Passenger';
$userId         = $_SESSION['user_id'];
$assets         = '../assets';

// Expire old passes automatically
$pdo->prepare("UPDATE passes SET status = 'expired'
               WHERE user_id = ? AND status = 'active'
                 AND ((valid_to IS NOT NULL AND valid_to < CURDATE())
                      OR (remaining_trips IS NOT NULL AND remaining_trips <= 0))")
    ->execute([$userId]);

// Active pass
$stmt = $pdo->prepare(
    "SELECT * FROM passes
     WHERE user_id = ? AND status = 'active'
     ORDER BY pass_id DESC LIMIT 1"
);
$stmt->execute([$userId]);
$active_pass = $stmt->fetch(PDO::FETCH_ASSOC);

// Recent trips
$stmt = $pdo->prepare(
    "SELECT t.trip_id, t.start_time, t.end_time, t.fare_deducted,
            r.route_name, r.origin, r.destination
     FROM trips t
     LEFT JOIN routes r ON r.route_id = t.route_id
     WHERE t.user_id = ?
     ORDER BY t.start_time DESC LIMIT 20"
);
$stmt->execute([$userId]);
$recent_trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Passenger Dashboard — MetroLink</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/style.css"/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/passenger.css"/>
</head>
<body>
<div class="layout">
  <?php include '../includes/sidebar-passenger.php'; ?>
  <main class="main-content" id="main-content">

    <div class="passenger-topbar">
      <button class="hamburger" id="hamburger" aria-label="Open navigation menu">
        <span></span><span></span><span></span>
      </button>
      <div class="passenger-topbar-right">Welcome, <?php echo htmlspecialchars($passenger_name); ?></div>
    </div>

    <div class="page-header">
      <h1 class="page-title">Passenger Dashboard</h1>
    </div>

    <div class="passenger-dashboard">
      <div class="dashboard-grid">

        <article class="card">
          <h2 class="card-title">Active Pass Status</h2>
          <?php if ($active_pass): ?>
            <div class="pass-pill"><?php echo htmlspecialchars(ucfirst($active_pass['pass_type'])); ?> Pass</div>
            <div class="qr-container">
              <div class="qr-code-box" role="img" aria-label="QR Code">
                <svg viewBox="0 0 110 110" xmlns="http://www.w3.org/2000/svg">
                  <rect width="110" height="110" fill="white"/>
                  <text x="55" y="50" text-anchor="middle" font-size="10" fill="#0f172a">PASS #<?php echo $active_pass['pass_id']; ?></text>
                  <text x="55" y="65" text-anchor="middle" font-size="8" fill="#6b7280">USER #<?php echo $userId; ?></text>
                </svg>
              </div>
              <p class="qr-label">Scan to Ride</p>
            </div>
            <div class="pass-info-box">
              Valid Until: <strong><?php echo htmlspecialchars($active_pass['valid_to'] ?? 'N/A'); ?></strong>
              &nbsp;|&nbsp;
              Trips Left: <strong><?php echo $active_pass['remaining_trips'] === null ? 'Unlimited' : (int)$active_pass['remaining_trips']; ?></strong>
            </div>
          <?php else: ?>
            <p>No active pass found.</p>
          <?php endif; ?>
          <a href="renew.php" class="btn btn-outlined btn-full">Buy / Renew Pass</a>
        </article>

        <article class="card">
          <h2 class="card-title">Find Route</h2>
          <form action="routes.php" method="get">
            <div class="route-inputs">
              <div class="input-with-icon">
                <input name="from" type="text" class="input-field" placeholder="From: Origin" />
              </div>
              <div class="input-with-icon">
                <input name="to" type="text" class="input-field" placeholder="To: Destination" />
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Search Route</button>
          </form>
          <div id="map" role="application" aria-label="Interactive transit map" style="height:280px;margin-top:12px;"></div>
        </article>

      </div>

      <section class="card trip-history-card">
        <div class="trip-history-header">
          <h2 class="card-title" style="margin-bottom:0;">Recent Trip History</h2>
          <a href="trips.php" class="action-link">View all →</a>
        </div>
        <?php if (empty($recent_trips)): ?>
          <p style="color:#6b7280;">No trips yet. Your journey history will appear here.</p>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="trip-table">
              <thead>
                <tr><th>Date &amp; Time</th><th>Route</th><th>From</th><th>To</th><th>Fare</th><th>Status</th></tr>
              </thead>
              <tbody>
                <?php foreach ($recent_trips as $trip): ?>
                  <tr>
                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($trip['start_time']))); ?></td>
                    <td><?php echo htmlspecialchars($trip['route_name'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($trip['origin'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($trip['destination'] ?? '—'); ?></td>
                    <td>৳<?php echo number_format((float)$trip['fare_deducted'], 2); ?></td>
                    <td>
                      <?php if ($trip['end_time']): ?>
                        <span class="badge badge-completed">Completed</span>
                      <?php else: ?>
                        <span class="badge">In Progress</span>
                      <?php endif; ?>
                    </td>
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

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="<?php echo $assets; ?>/js/map.js"></script>
<script src="<?php echo $assets; ?>/js/main.js"></script>
</body>
</html>
