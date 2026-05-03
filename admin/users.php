<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$admin_current_page  = 'users';
$topbar_title        = 'Users';
$topbar_title_prefix = 'Admin';
$notification_count  = 0;
$admin_name          = $_SESSION['user_name'] ?? 'Admin';
$assets              = '../assets';

$flash_success = null;
$flash_error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    // Prevent admin from modifying their own account
    if ($userId === (int)$_SESSION['user_id']) {
        $flash_error = 'You cannot modify your own account from here.';
    } else {
        try {
            if ($action === 'set_status') {
                $newStatus = $_POST['new_status'] ?? 'active';
                $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?")
                    ->execute([$newStatus, $userId]);
                $flash_success = 'User status updated.';
            } elseif ($action === 'delete_user') {
                $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$userId]);
                $flash_success = 'User deleted.';
            }
        } catch (PDOException $e) {
            $flash_error = 'Operation failed: ' . $e->getMessage();
        }
    }
}

$users = $pdo->query(
    "SELECT user_id, name, email, role, status, created_at
     FROM users ORDER BY user_id DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Users — MetroLink Admin</title>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/style.css"/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/admin.css"/>
  <style>
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
      <section class="card" style="grid-column:1 / -1;">
        <h2 class="card-title">All Users (<?php echo count($users); ?>)</h2>
        <?php if ($flash_success): ?><div class="flash flash-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>
        <?php if ($flash_error): ?><div class="flash flash-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>

        <?php if (empty($users)): ?>
          <p style="color:#6b7280;">No users found.</p>
        <?php else: ?>
          <div class="table-wrapper">
            <table class="fleet-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status</th>
                  <th>Joined</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u): ?>
                  <tr>
                    <td>#<?php echo $u['user_id']; ?></td>
                    <td><?php echo htmlspecialchars($u['name']); ?></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><span class="badge"><?php echo htmlspecialchars(ucfirst($u['role'])); ?></span></td>
                    <td>
                      <?php if ($u['status'] === 'active'): ?>
                        <span class="badge badge-active">Active</span>
                      <?php else: ?>
                        <span class="badge badge-maintenance"><?php echo htmlspecialchars(ucfirst($u['status'])); ?></span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($u['created_at']))); ?></td>
                    <td>
                      <?php if ((int)$u['user_id'] === (int)$_SESSION['user_id']): ?>
                        <span style="color:#6b7280;">You</span>
                      <?php else: ?>
                        <form method="post" class="inline-form">
                          <input type="hidden" name="action" value="set_status"/>
                          <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>"/>
                          <select name="new_status" onchange="this.form.submit()" class="input-field" style="padding:4px 8px;font-size:.85rem;">
                            <option value="active"    <?php echo $u['status']==='active'?'selected':''; ?>>Active</option>
                            <option value="inactive"  <?php echo $u['status']==='inactive'?'selected':''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $u['status']==='suspended'?'selected':''; ?>>Suspended</option>
                          </select>
                        </form>
                        <form method="post" class="inline-form" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                          <input type="hidden" name="action" value="delete_user"/>
                          <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>"/>
                          <button type="submit" class="action-link" style="background:none;border:none;color:#dc2626;cursor:pointer;">Delete</button>
                        </form>
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
