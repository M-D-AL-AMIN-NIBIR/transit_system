<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$admin_current_page  = 'schedules';
$topbar_title        = 'Schedules';
$topbar_title_prefix = 'Admin';
$notification_count  = 0;
$admin_name          = $_SESSION['user_name'] ?? 'Admin';
$assets              = '../assets';

$flash_success = null;
$flash_error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_schedule') {
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            $routeId   = (int)($_POST['route_id'] ?? 0);
            $time      = $_POST['schedule_time'] ?? '';
            $days      = trim($_POST['days'] ?? '');

            if (!$vehicleId || !$routeId || !$time) {
                $flash_error = 'Vehicle, route, and time are required.';
            } else {
                $pdo->prepare(
                    "INSERT INTO route_assignments (vehicle_id, route_id, schedule_time, days)
                     VALUES (?, ?, ?, ?)"
                )->execute([$vehicleId, $routeId, $time, $days]);
                $flash_success = 'Schedule added.';
            }
        } elseif ($action === 'delete_schedule') {
            $id = (int)($_POST['assignment_id'] ?? 0);
            $pdo->prepare("DELETE FROM route_assignments WHERE assignment_id = ?")->execute([$id]);
            $flash_success = 'Schedule deleted.';
        } elseif ($action === 'add_route') {
            $name   = trim($_POST['route_name'] ?? '');
            $origin = trim($_POST['origin'] ?? '');
            $dest   = trim($_POST['destination'] ?? '');
            $dist   = (float)($_POST['distance_km'] ?? 0);
            if (!$name || !$origin || !$dest) {
                $flash_error = 'Route name, origin and destination are required.';
            } else {
                $pdo->prepare(
                    "INSERT INTO routes (route_name, origin, destination, distance_km, status)
                     VALUES (?, ?, ?, ?, 'active')"
                )->execute([$name, $origin, $dest, $dist]);
                $flash_success = 'Route added.';
            }
        }
    } catch (PDOException $e) {
        $flash_error = 'Operation failed: ' . $e->getMessage();
    }
}

$schedules = $pdo->query(
    "SELECT ra.assignment_id, ra.schedule_time, ra.days,
            v.vehicle_id, v.type,
            r.route_id, r.route_name, r.origin, r.destination
     FROM route_assignments ra
     JOIN vehicles v ON v.vehicle_id = ra.vehicle_id
     JOIN routes r   ON r.route_id   = ra.route_id
     ORDER BY ra.schedule_time"
)->fetchAll(PDO::FETCH_ASSOC);

$vehicles = $pdo->query("SELECT vehicle_id, type, capacity FROM vehicles WHERE status = 'active' ORDER BY vehicle_id")->fetchAll(PDO::FETCH_ASSOC);
$routes   = $pdo->query("SELECT route_id, route_name, origin, destination FROM routes WHERE status = 'active' ORDER BY route_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Schedules — MetroLink Admin</title>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/style.css"/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/admin.css"/>
  <style>
    .flash{padding:10px 14px;border-radius:6px;margin-bottom:16px;}
    .flash-success{background:#d1fae5;color:#059669;}
    .flash-error{background:#fee2e2;color:#dc2626;}
    .inline-form{display:inline;}
    .schedule-form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px;align-items:end;}
  </style>
</head>
<body>
<div class="layout">
  <?php include '../includes/sidebar-admin.php'; ?>
  <main class="main-content" id="main-content">
    <?php include '../includes/topbar-admin.php'; ?>
    <div class="admin-content-grid">

      <section class="card" style="grid-column:1 / -1;">
        <h2 class="card-title">Route Schedules</h2>
        <?php if ($flash_success): ?><div class="flash flash-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>
        <?php if ($flash_error): ?><div class="flash flash-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>

        <h3 style="font-size:1rem;margin:16px 0 10px;">Add New Schedule</h3>
        <?php if (empty($vehicles) || empty($routes)): ?>
          <p style="color:#6b7280;">
            You need at least one active vehicle and one active route before adding a schedule.
            <?php if (empty($vehicles)): ?><a href="fleet.php">Add a vehicle →</a><?php endif; ?>
          </p>
        <?php else: ?>
          <form method="post" class="schedule-form-grid">
            <input type="hidden" name="action" value="add_schedule"/>
            <div>
              <label>Vehicle</label>
              <select name="vehicle_id" class="input-field" required>
                <?php foreach ($vehicles as $v): ?>
                  <option value="<?php echo $v['vehicle_id']; ?>">#<?php echo $v['vehicle_id']; ?> - <?php echo htmlspecialchars(ucfirst($v['type'])); ?> (<?php echo $v['capacity']; ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label>Route</label>
              <select name="route_id" class="input-field" required>
                <?php foreach ($routes as $r): ?>
                  <option value="<?php echo $r['route_id']; ?>"><?php echo htmlspecialchars($r['route_name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label>Time</label>
              <input type="time" name="schedule_time" class="input-field" required/>
            </div>
            <div>
              <label>Days</label>
              <input type="text" name="days" class="input-field" placeholder="Mon-Fri" value="Daily"/>
            </div>
            <div>
              <button type="submit" class="btn btn-primary">Add Schedule</button>
            </div>
          </form>
        <?php endif; ?>

        <h3 style="font-size:1rem;margin:24px 0 10px;">Existing Schedules (<?php echo count($schedules); ?>)</h3>
        <?php if (empty($schedules)): ?>
          <p style="color:#6b7280;">No schedules configured yet.</p>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="fleet-table">
              <thead>
                <tr>
                  <th>Vehicle</th>
                  <th>Route</th>
                  <th>Origin → Destination</th>
                  <th>Time</th>
                  <th>Days</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($schedules as $s): ?>
                  <tr>
                    <td>#<?php echo $s['vehicle_id']; ?> <?php echo htmlspecialchars(ucfirst($s['type'])); ?></td>
                    <td><?php echo htmlspecialchars($s['route_name']); ?></td>
                    <td><?php echo htmlspecialchars($s['origin'] . ' → ' . $s['destination']); ?></td>
                    <td><?php echo htmlspecialchars(substr($s['schedule_time'], 0, 5)); ?></td>
                    <td><?php echo htmlspecialchars($s['days'] ?: '—'); ?></td>
                    <td>
                      <form method="post" class="inline-form" onsubmit="return confirm('Delete this schedule?');">
                        <input type="hidden" name="action" value="delete_schedule"/>
                        <input type="hidden" name="assignment_id" value="<?php echo $s['assignment_id']; ?>"/>
                        <button type="submit" class="action-link" style="background:none;border:none;color:#dc2626;cursor:pointer;">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </section>

      <section class="card" style="grid-column:1 / -1;">
        <h2 class="card-title">Add New Route</h2>
        <form method="post" class="schedule-form-grid">
          <input type="hidden" name="action" value="add_route"/>
          <div>
            <label>Route Name</label>
            <input type="text" name="route_name" class="input-field" required/>
          </div>
          <div>
            <label>Origin</label>
            <input type="text" name="origin" class="input-field" required/>
          </div>
          <div>
            <label>Destination</label>
            <input type="text" name="destination" class="input-field" required/>
          </div>
          <div>
            <label>Distance (km)</label>
            <input type="number" step="0.1" name="distance_km" class="input-field" min="0"/>
          </div>
          <div>
            <button type="submit" class="btn btn-primary">Add Route</button>
          </div>
        </form>
      </section>

    </div>
  </main>
</div>
<script src="<?php echo $assets; ?>/js/main.js"></script>
</body>
</html>
