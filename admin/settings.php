<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$admin_current_page  = 'settings';
$topbar_title        = 'Settings';
$topbar_title_prefix = 'Admin';
$notification_count  = 0;
$admin_name          = $_SESSION['user_name'] ?? 'Admin';
$assets              = '../assets';

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
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !password_verify($current, $row['password_hash'])) {
                $flash_error = 'Current password is incorrect.';
            } else {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")
                    ->execute([$hash, $_SESSION['user_id']]);
                $flash_success = 'Password updated successfully.';
            }
        }
    } elseif ($action === 'update_profile') {
        $name  = trim($_POST['name']  ?? '');
        $email = trim($_POST['email'] ?? '');
        if (empty($name) || empty($email)) {
            $flash_error = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $flash_error = 'Please enter a valid email.';
        } else {
            try {
                $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?")
                    ->execute([$name, $email, $_SESSION['user_id']]);
                $_SESSION['user_name'] = $name;
                $admin_name = $name;
                $flash_success = 'Profile updated.';
            } catch (PDOException $e) {
                $flash_error = 'That email may already be in use.';
            }
        }
    }
}

// Fetch current admin profile
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$me = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['name' => '', 'email' => ''];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Settings — MetroLink Admin</title>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/style.css"/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/admin.css"/>
  <style>
    .flash{padding:10px 14px;border-radius:6px;margin-bottom:16px;}
    .flash-success{background:#d1fae5;color:#059669;}
    .flash-error{background:#fee2e2;color:#dc2626;}
  </style>
</head>
<body>
<div class="layout">
  <?php include '../includes/sidebar-admin.php'; ?>
  <main class="main-content" id="main-content">
    <?php include '../includes/topbar-admin.php'; ?>
    <div class="admin-content-grid">

      <section class="card">
        <h2 class="card-title">Admin Profile</h2>
        <?php if ($flash_success): ?><div class="flash flash-success"><?php echo htmlspecialchars($flash_success); ?></div><?php endif; ?>
        <?php if ($flash_error): ?><div class="flash flash-error"><?php echo htmlspecialchars($flash_error); ?></div><?php endif; ?>
        <form method="post">
          <input type="hidden" name="action" value="update_profile"/>
          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" class="input-field" value="<?php echo htmlspecialchars($me['name']); ?>" required/>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="input-field" value="<?php echo htmlspecialchars($me['email']); ?>" required/>
          </div>
          <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
      </section>

      <section class="card">
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
      </section>

    </div>
  </main>
</div>
<script src="<?php echo $assets; ?>/js/main.js"></script>
</body>
</html>
