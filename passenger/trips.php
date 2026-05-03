<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$current_page   = 'trip-history';
$passenger_name = $_SESSION['user_name'] ?? 'Passenger';
$userId         = $_SESSION['user_id'];
$assets         = '../assets';

$flash_success = null;

// Handle End Trip
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'end_trip') {
    $tripId = (int)($_POST['trip_id'] ?? 0);
    $pdo->prepare(
        "UPDATE trips SET end_time = NOW()
         WHERE trip_id = ? AND user_id = ? AND end_time IS NULL"
    )->execute([$tripId, $userId]);
    $flash_success = 'Trip ended successfully.';
}

$stmt = $pdo->prepare(
    "SELECT t.trip_id, t.start_time, t.end_time, t.fare_deducted,
            r.route_name, r.origin, r.destination,
            v.type AS vehicle_type
     FROM trips t
     LEFT JOIN routes r   ON r.route_id   = t.route_id
     LEFT JOIN vehicles v ON v.vehicle_id = t.vehicle_id
     WHERE t.user_id = ?
     ORDER BY t.start_time DESC"
);
$stmt->execute([$userId]);
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalFare  = array_sum(array_column($trips, 'fare_deducted'));
$totalTrips = count($trips);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Trip History — MetroLink</title>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/style.css"/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/passenger.css"/>
  <style>
    .flash{padding:10px 14px;border-radius:6px;margin-bottom:16px;background:#d1fae5;color:#059669;}
  </style>
</head>
<body>
<div class="layout">
  <?php include '../includes/sidebar-passenger.php'; ?>
  <main class="main-content" id="main-content">
    <div class="passenger-topbar">
      <button class="hamburger" id="hamburger" aria-label="Open navigation menu"><span></span><span></span><span></span></button>
      <div class="passenger-topbar-right">Welcome, <?php echo htmlspecialchars($passenger_name); ?></div>
    </div>
    <div class="page-header">
      <h1 class="page-title">Trip History</h1>
    </div>
    <div class="passenger-dashboard">

      <?php if ($flash_success): ?><div class="flash"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>

      <div class="card">
        <p><strong>Total Trips:</strong> <?php echo $totalTrips; ?> &nbsp;|&nbsp; <strong>Total Spent:</strong> ৳<?php echo number_format($totalFare, 2); ?></p>
      </div>

      <section class="card trip-history-card">
        <h2 class="card-title">All Trips</h2>
        <?php if (empty($trips)): ?>
          <p style="color:#6b7280;">No trips recorded yet. Your journey history will appear here.</p>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="trip-table">
              <thead>
                <tr>
                  <th>Date &amp; Time</th>
                  <th>Vehicle</th>
                  <th>Route</th>
                  <th>From</th>
                  <th>To</th>
                  <th>Fare</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($trips as $t): ?>
                  <tr>
                    <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($t['start_time']))); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($t['vehicle_type'] ?? '—')); ?></td>
                    <td><?php echo htmlspecialchars($t['route_name'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($t['origin'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($t['destination'] ?? '—'); ?></td>
                    <td>৳<?php echo number_format((float)$t['fare_deducted'], 2); ?></td>
                    <td>
                      <?php if ($t['end_time']): ?>
                        <span class="badge badge-completed">Completed</span>
                      <?php else: ?>
                        <span class="badge">In Progress</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!$t['end_time']): ?>
                        <form method="post" style="display:inline;" onsubmit="return confirm('End this trip?');">
                          <input type="hidden" name="action" value="end_trip"/>
                          <input type="hidden" name="trip_id" value="<?php echo $t['trip_id']; ?>"/>
                          <button type="submit" class="btn btn-primary" style="padding:4px 10px;font-size:.8rem;">End Trip</button>
                        </form>
                      <?php else: ?>
                        <span style="color:#9ca3af;">—</span>
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
<script src="<?php echo $assets; ?>/js/main.js"></script>
</body>
</html>
