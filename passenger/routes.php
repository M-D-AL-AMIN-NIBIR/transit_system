<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$current_page   = 'routes-schedules';
$passenger_name = $_SESSION['user_name'] ?? 'Passenger';
$userId         = $_SESSION['user_id'];
$assets         = '../assets';

$flash_success = null;
$flash_error   = null;

// Auto-expire passes
$pdo->prepare("UPDATE passes SET status = 'expired'
               WHERE user_id = ? AND status = 'active'
                 AND ((valid_to IS NOT NULL AND valid_to < CURDATE())
                      OR (remaining_trips IS NOT NULL AND remaining_trips <= 0))")
    ->execute([$userId]);

// Handle start-trip action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'start_trip') {
    $routeId = (int)($_POST['route_id'] ?? 0);

    try {
        $pdo->beginTransaction();

        // Check for active pass
        $stmt = $pdo->prepare(
            "SELECT * FROM passes
             WHERE user_id = ? AND status = 'active'
             ORDER BY pass_id DESC LIMIT 1 FOR UPDATE"
        );
        $stmt->execute([$userId]);
        $pass = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pass) {
            $flash_error = 'You need an active pass to take a trip.';
            $pdo->rollBack();
        } else {
            // Check if user already has an in-progress trip
            $stmt = $pdo->prepare("SELECT trip_id FROM trips WHERE user_id = ? AND end_time IS NULL LIMIT 1");
            $stmt->execute([$userId]);
            if ($stmt->fetch()) {
                $flash_error = 'You already have a trip in progress. End it before starting a new one.';
                $pdo->rollBack();
            } else {
                // Get fare from fare_rules (regular)
                $stmt = $pdo->prepare(
                    "SELECT base_fare FROM fare_rules
                     WHERE route_id = ? AND passenger_type = 'regular' LIMIT 1"
                );
                $stmt->execute([$routeId]);
                $fare = (float)($stmt->fetchColumn() ?: 20.00);

                // Get an assigned vehicle if any
                $stmt = $pdo->prepare(
                    "SELECT ra.vehicle_id FROM route_assignments ra
                     JOIN vehicles v ON v.vehicle_id = ra.vehicle_id
                     WHERE ra.route_id = ? AND v.status = 'active'
                     ORDER BY RAND() LIMIT 1"
                );
                $stmt->execute([$routeId]);
                $vehicleId = $stmt->fetchColumn() ?: null;

                // Create trip
                $pdo->prepare(
                    "INSERT INTO trips (user_id, pass_id, route_id, vehicle_id, start_time, fare_deducted)
                     VALUES (?, ?, ?, ?, NOW(), ?)"
                )->execute([$userId, $pass['pass_id'], $routeId, $vehicleId, $fare]);

                // For trip-based passes, decrement remaining_trips
                if ($pass['pass_type'] === 'trip-based' && $pass['remaining_trips'] !== null) {
                    $pdo->prepare("UPDATE passes SET remaining_trips = remaining_trips - 1 WHERE pass_id = ?")
                        ->execute([$pass['pass_id']]);
                }

                $pdo->commit();
                $flash_success = 'Trip started! View it on your Trip History page.';
            }
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $flash_error = 'Failed to start trip: ' . $e->getMessage();
    }
}

// Check if user has an active pass (for UI decision)
$stmt = $pdo->prepare("SELECT 1 FROM passes WHERE user_id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$userId]);
$hasActivePass = (bool)$stmt->fetchColumn();

$from = trim($_GET['from'] ?? '');
$to   = trim($_GET['to']   ?? '');

// Fetch routes, optionally filtered
if ($from !== '' || $to !== '') {
    $stmt = $pdo->prepare(
        "SELECT * FROM routes
         WHERE status = 'active'
           AND (origin LIKE ? OR destination LIKE ?)
         ORDER BY route_name"
    );
    $stmt->execute(['%' . ($from ?: $to) . '%', '%' . ($to ?: $from) . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM routes WHERE status = 'active' ORDER BY route_name");
}
$routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Schedule info per route
$scheduleMap = [];
$schedules = $pdo->query(
    "SELECT ra.route_id, ra.schedule_time, ra.days, v.type
     FROM route_assignments ra
     JOIN vehicles v ON v.vehicle_id = ra.vehicle_id
     WHERE v.status = 'active'
     ORDER BY ra.schedule_time"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($schedules as $s) {
    $scheduleMap[$s['route_id']][] = $s;
}

// Fare map per route (regular)
$fareMap = [];
$fares = $pdo->query(
    "SELECT route_id, base_fare FROM fare_rules WHERE passenger_type = 'regular'"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($fares as $f) {
    $fareMap[$f['route_id']] = (float)$f['base_fare'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Routes &amp; Schedules — MetroLink</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/style.css"/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/passenger.css"/>
  <style>
    .route-item{border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-bottom:10px;}
    .route-item h3{margin:0 0 6px;font-size:1rem;}
    .route-meta{color:#6b7280;font-size:.85rem;margin-bottom:8px;}
    .schedule-pills{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;}
    .schedule-pill{background:#eef2ff;color:#4338ca;padding:3px 8px;border-radius:4px;font-size:.75rem;}
    .route-actions{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:8px;}
    .route-fare{font-size:.9rem;color:#0f172a;}
    .flash{padding:10px 14px;border-radius:6px;margin-bottom:16px;}
    .flash-success{background:#d1fae5;color:#059669;}
    .flash-error{background:#fee2e2;color:#dc2626;}
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
      <h1 class="page-title">Routes &amp; Schedules</h1>
    </div>
    <div class="passenger-dashboard">
      <div class="dashboard-grid">

        <article class="card">
          <h2 class="card-title">Find Route</h2>
          <form method="get">
            <div class="route-inputs">
              <div class="input-with-icon">
                <input name="from" type="text" class="input-field" placeholder="From: Origin" value="<?php echo htmlspecialchars($from); ?>"/>
              </div>
              <div class="input-with-icon">
                <input name="to" type="text" class="input-field" placeholder="To: Destination" value="<?php echo htmlspecialchars($to); ?>"/>
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-full">Search Route</button>
            <?php if ($from || $to): ?>
              <a href="routes.php" style="display:block;text-align:center;margin-top:8px;color:#667eea;">Clear search</a>
            <?php endif; ?>
          </form>
          <div id="map" role="application" aria-label="Interactive transit map" style="height:300px;margin-top:12px;"></div>
        </article>

        <article class="card">
          <h2 class="card-title">Available Routes (<?php echo count($routes); ?>)</h2>
          <?php if ($flash_success): ?><div class="flash flash-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>
          <?php if ($flash_error): ?><div class="flash flash-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>
          <?php if (!$hasActivePass): ?>
            <div class="flash" style="background:#fef3c7;color:#92400e;">
              You don't have an active pass. <a href="renew.php" style="color:#92400e;font-weight:600;">Buy a pass →</a> to start taking trips.
            </div>
          <?php endif; ?>
          <?php if (empty($routes)): ?>
            <p style="color:#6b7280;">
              <?php if ($from || $to): ?>
                No routes match your search. <a href="routes.php">View all routes</a>.
              <?php else: ?>
                No routes available at this time.
              <?php endif; ?>
            </p>
          <?php else: ?>
            <?php foreach ($routes as $r): ?>
              <div class="route-item">
                <h3><?php echo htmlspecialchars($r['route_name']); ?></h3>
                <div class="route-meta">
                  <?php echo htmlspecialchars($r['origin']); ?> → <?php echo htmlspecialchars($r['destination']); ?>
                  <?php if ($r['distance_km']): ?>
                    · <?php echo htmlspecialchars($r['distance_km']); ?> km
                  <?php endif; ?>
                </div>
                <?php if (!empty($scheduleMap[$r['route_id']])): ?>
                  <div class="schedule-pills">
                    <?php foreach (array_slice($scheduleMap[$r['route_id']], 0, 6) as $s): ?>
                      <span class="schedule-pill"><?php echo htmlspecialchars(substr($s['schedule_time'], 0, 5)); ?> <?php echo htmlspecialchars(ucfirst($s['type'])); ?></span>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div style="font-size:.8rem;color:#9ca3af;margin-bottom:10px;">No scheduled vehicles yet</div>
                <?php endif; ?>
                <div class="route-actions">
                  <div class="route-fare">
                    Fare: <strong>৳<?php echo number_format($fareMap[$r['route_id']] ?? 20.00, 2); ?></strong>
                  </div>
                  <?php if ($hasActivePass): ?>
                    <form method="post" onsubmit="return confirm('Start a trip on this route?');">
                      <input type="hidden" name="action" value="start_trip"/>
                      <input type="hidden" name="route_id" value="<?php echo $r['route_id']; ?>"/>
                      <button type="submit" class="btn btn-primary" style="padding:6px 14px;font-size:.85rem;">Take Trip</button>
                    </form>
                  <?php else: ?>
                    <a href="renew.php" class="btn" style="padding:6px 14px;font-size:.85rem;">Buy Pass to Ride</a>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </article>

      </div>
    </div>
  </main>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="<?php echo $assets; ?>/js/map.js"></script>
<script src="<?php echo $assets; ?>/js/main.js"></script>
</body>
</html>
