<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$admin_current_page  = 'fleet';
$topbar_title        = 'Fleet & Route Registry';
$topbar_title_prefix = 'Admin';
$notification_count  = 0;
$admin_name          = $_SESSION['user_name'] ?? 'Admin';
$assets              = '../assets';

$flash_success = null;
$flash_error   = null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add_vehicle') {
            $type     = $_POST['type'] ?? 'bus';
            $capacity = (int)($_POST['capacity'] ?? 0);
            $status   = $_POST['status'] ?? 'active';
            $extra    = trim($_POST['extra'] ?? '');

            if ($capacity <= 0) {
                $flash_error = 'Capacity must be greater than zero.';
            } else {
                $pdo->beginTransaction();
                $pdo->prepare("INSERT INTO vehicles (type, capacity, status) VALUES (?, ?, ?)")
                    ->execute([$type, $capacity, $status]);
                $vehicleId = $pdo->lastInsertId();

                if ($type === 'bus') {
                    $pdo->prepare("INSERT INTO bus_details (vehicle_id, route_no, sub_type) VALUES (?, ?, ?)")
                        ->execute([$vehicleId, $extra, 'Standard']);
                } else {
                    $pdo->prepare("INSERT INTO train_details (vehicle_id, line_no) VALUES (?, ?)")
                        ->execute([$vehicleId, $extra]);
                }
                $pdo->commit();
                $flash_success = 'Vehicle added successfully.';
            }
        } elseif ($action === 'delete_vehicle') {
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            $pdo->prepare("DELETE FROM vehicles WHERE vehicle_id = ?")->execute([$vehicleId]);
            $flash_success = 'Vehicle deleted.';
        } elseif ($action === 'update_status') {
            $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
            $newStatus = $_POST['new_status'] ?? 'active';
            $pdo->prepare("UPDATE vehicles SET status = ? WHERE vehicle_id = ?")
                ->execute([$newStatus, $vehicleId]);
            $flash_success = 'Vehicle status updated.';
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $flash_error = 'Operation failed: ' . $e->getMessage();
    }
}

// Fetch fleet
$fleet_vehicles = $pdo->query(
    "SELECT v.vehicle_id, v.type, v.capacity, v.status,
            COALESCE(bd.route_no, td.line_no, '') AS identifier,
            (SELECT r.route_name FROM route_assignments ra
                JOIN routes r ON r.route_id = ra.route_id
                WHERE ra.vehicle_id = v.vehicle_id LIMIT 1) AS route_name
     FROM vehicles v
     LEFT JOIN bus_details bd ON bd.vehicle_id = v.vehicle_id
     LEFT JOIN train_details td ON td.vehicle_id = v.vehicle_id
     ORDER BY v.vehicle_id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent notifications as system alerts
$system_alerts = $pdo->query(
    "SELECT title, message, created_at FROM notifications
     ORDER BY created_at DESC LIMIT 5"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Fleet &amp; Route Registry — MetroLink Admin</title>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/style.css"/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/admin.css"/>
  <style>
    .modal-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;}
    .modal-backdrop.open{display:flex;}
    .modal{background:#fff;padding:24px;border-radius:12px;max-width:460px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.25);}
    .modal h3{margin:0 0 16px;}
    .flash{padding:10px 14px;border-radius:6px;margin-bottom:16px;}
    .flash-success{background:#d1fae5;color:#059669;}
    .flash-error{background:#fee2e2;color:#dc2626;}
    .inline-form{display:inline;}
  </style>
</head>
<body>
<div class="layout">
  <?php include '../includes/sidebar-admin.php'; ?>
  <main class="main-content" id="main-content">
    <?php include '../includes/topbar-admin.php'; ?>
    <div class="admin-content-grid">

      <section class="card" aria-label="Fleet and Route Registry">
        <?php if ($flash_success): ?><div class="flash flash-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>
        <?php if ($flash_error): ?><div class="flash flash-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>

        <div class="fleet-card-header">
          <button id="btn-assign-route" class="btn btn-primary" type="button" onclick="document.getElementById('add-vehicle-modal').classList.add('open')">
            + Add Vehicle
          </button>
          <div class="fleet-card-header-right">
            <div class="search-wrap">
              <input id="fleet-search" type="search" class="input-field" placeholder="Search" aria-label="Search fleet vehicles"/>
            </div>
          </div>
        </div>

        <div class="table-wrapper">
          <table class="fleet-table" aria-label="Fleet vehicles">
            <thead>
              <tr>
                <th>ID</th>
                <th>Type</th>
                <th>Identifier</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Assigned Route</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($fleet_vehicles)): ?>
                <tr><td colspan="7" style="text-align:center;padding:24px;color:#6b7280;">No vehicles registered. Click "Add Vehicle" to create one.</td></tr>
              <?php else: foreach ($fleet_vehicles as $v): ?>
                <tr>
                  <td>#<?php echo $v['vehicle_id']; ?></td>
                  <td><?php echo htmlspecialchars(ucfirst($v['type'])); ?></td>
                  <td><?php echo htmlspecialchars($v['identifier'] ?: '—'); ?></td>
                  <td><?php echo (int)$v['capacity']; ?></td>
                  <td>
                    <?php if ($v['status'] === 'active'): ?>
                      <span class="badge badge-active">Active</span>
                    <?php else: ?>
                      <span class="badge badge-maintenance"><?php echo htmlspecialchars(ucfirst($v['status'])); ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars($v['route_name'] ?: 'Unassigned'); ?></td>
                  <td>
                    <form method="post" class="inline-form">
                      <input type="hidden" name="action" value="update_status"/>
                      <input type="hidden" name="vehicle_id" value="<?php echo $v['vehicle_id']; ?>"/>
                      <select name="new_status" onchange="this.form.submit()" class="input-field" style="padding:4px 8px;font-size:.85rem;">
                        <option value="active"      <?php echo $v['status']==='active'?'selected':''; ?>>Active</option>
                        <option value="inactive"    <?php echo $v['status']==='inactive'?'selected':''; ?>>Inactive</option>
                        <option value="maintenance" <?php echo $v['status']==='maintenance'?'selected':''; ?>>Maintenance</option>
                      </select>
                    </form>
                    <form method="post" class="inline-form" onsubmit="return confirm('Delete this vehicle?');">
                      <input type="hidden" name="action" value="delete_vehicle"/>
                      <input type="hidden" name="vehicle_id" value="<?php echo $v['vehicle_id']; ?>"/>
                      <button type="submit" class="action-link" style="background:none;border:none;color:#dc2626;cursor:pointer;">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </section>

      <aside class="card alerts-card" aria-label="System Alerts">
        <h2 class="card-title">System Alerts</h2>
        <?php if (empty($system_alerts)): ?>
          <p style="color:#6b7280;">No recent alerts.</p>
        <?php else: ?>
          <ul class="alert-list" role="list">
            <?php foreach ($system_alerts as $alert): ?>
              <li class="alert-item">
                <div class="alert-body">
                  <p class="alert-text"><strong><?php echo htmlspecialchars($alert['title'] ?: 'Notification'); ?></strong><br><?php echo htmlspecialchars($alert['message']); ?></p>
                  <time class="alert-time"><?php echo htmlspecialchars($alert['created_at']); ?></time>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </aside>

    </div>
  </main>
</div>

<!-- Add Vehicle Modal -->
<div class="modal-backdrop" id="add-vehicle-modal" onclick="if(event.target===this)this.classList.remove('open')">
  <div class="modal">
    <h3>Add New Vehicle</h3>
    <form method="post">
      <input type="hidden" name="action" value="add_vehicle"/>
      <div class="form-group">
        <label>Type</label>
        <select name="type" class="input-field" required>
          <option value="bus">Bus</option>
          <option value="train">Train</option>
        </select>
      </div>
      <div class="form-group">
        <label>Capacity</label>
        <input type="number" name="capacity" class="input-field" min="1" required/>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status" class="input-field">
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="maintenance">Maintenance</option>
        </select>
      </div>
      <div class="form-group">
        <label>Route No / Line No</label>
        <input type="text" name="extra" class="input-field" placeholder="e.g. 101 or Red Line"/>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" class="btn" onclick="document.getElementById('add-vehicle-modal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add</button>
      </div>
    </form>
  </div>
</div>

<script src="<?php echo $assets; ?>/js/main.js"></script>
</body>
</html>
