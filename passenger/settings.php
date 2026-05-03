<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$current_page   = 'settings';
$passenger_name = $_SESSION['user_name'] ?? 'Passenger';
$userId         = $_SESSION['user_id'];
$assets         = '../assets';

$flash_success = null;
$flash_error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($current) || empty($new) || empty($confirm)) {
            $flash_error = 'All password fields are required.';
        } elseif ($new !== $confirm) {
            $flash_error = 'New password and confirmation do not match.';
        } elseif (strlen($new) < 6) {
            $flash_error = 'New password must be at least 6 characters.';
        } else {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($current, $row['password_hash'])) {
                $flash_error = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")
                    ->execute([$hash, $userId]);
                $flash_success = 'Password updated successfully.';
            }
        }
    } elseif ($action === 'delete_account') {
        $confirm = $_POST['confirm_delete'] ?? '';
        if ($confirm !== 'DELETE') {
            $flash_error = 'Please type DELETE to confirm account deletion.';
        } else {
            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$userId]);
            session_destroy();
            header('Location: ../index.php?deleted=1');
            exit();
        }
    }
}

// Fetch user for display
$stmt = $pdo->prepare("SELECT name, email, created_at FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Settings — MetroLink</title>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/style.css"/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/passenger.css"/>
  <style>
    .flash{padding:10px 14px;border-radius:6px;margin-bottom:16px;}
    .flash-success{background:#d1fae5;color:#059669;}
    .flash-error{background:#fee2e2;color:#dc2626;}
    .danger-card{border:1px solid #fecaca;}
    .danger-card .card-title{color:#dc2626;}
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
      <h1 class="page-title">Settings</h1>
    </div>
    <div class="passenger-dashboard">

      <?php if ($flash_success): ?><div class="flash flash-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>
      <?php if ($flash_error): ?><div class="flash flash-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>

      <div class="card">
        <h2 class="card-title">Account Info</h2>
        <p>
          <strong>Name:</strong> <?php echo htmlspecialchars($me['name'] ?? ''); ?><br/>
          <strong>Email:</strong> <?php echo htmlspecialchars($me['email'] ?? ''); ?><br/>
          <strong>Member since:</strong> <?php echo htmlspecialchars(isset($me['created_at']) ? date('Y-m-d', strtotime($me['created_at'])) : ''); ?>
        </p>
        <a href="profile.php" class="btn btn-primary">Edit Profile</a>
      </div>

      <div class="card">
        <h2 class="card-title">Change Password</h2>
        <form method="post">
          <input type="hidden" name="action" value="change_password"/>
          <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" class="input-field" required/>
          </div>
          <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" class="input-field" required minlength="6"/>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="input-field" required minlength="6"/>
          </div>
          <button type="submit" class="btn btn-primary">Update Password</button>
        </form>
      </div>

      <div class="card danger-card">
        <h2 class="card-title">Delete Account</h2>
        <p style="color:#6b7280;">This action is permanent. All your passes, trips and data will be removed.</p>
        <form method="post" onsubmit="return confirm('Are you absolutely sure? This cannot be undone.');">
          <input type="hidden" name="action" value="delete_account"/>
          <div class="form-group">
            <label for="confirm_delete">Type <code>DELETE</code> to confirm</label>
            <input type="text" id="confirm_delete" name="confirm_delete" class="input-field" required/>
          </div>
          <button type="submit" class="btn" style="background:#dc2626;color:#fff;">Delete My Account</button>
        </form>
      </div>

    </div>
  </main>
</div>
<script src="<?php echo $assets; ?>/js/main.js"></script>
</body>
</html>
