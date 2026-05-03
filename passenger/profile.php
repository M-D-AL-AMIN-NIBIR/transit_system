<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../includes/db.php';

$current_page = 'profile';
$assets = '../assets';
$success = false;
$error = null;

$userId = $_SESSION['user_id'];

// Handle POST — update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email)) {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            // Check email not taken by another user
            $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $check->execute([$email, $userId]);
            if ($check->fetch()) {
                $error = 'That email is already in use by another account.';
            } else {
                $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?")
                    ->execute([$name, $email, $userId]);

                $pdo->prepare("UPDATE passenger_profiles SET phone = ? WHERE user_id = ?")
                    ->execute([$phone, $userId]);

                $_SESSION['user_name'] = $name;
                $success = true;
            }
        } catch (PDOException $e) {
            $error = 'Update failed. Please try again.';
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}

// Fetch current profile data
$stmt = $pdo->prepare(
    "SELECT u.name, u.email, pp.phone
     FROM users u
     LEFT JOIN passenger_profiles pp ON pp.user_id = u.user_id
     WHERE u.user_id = ?"
);
$stmt->execute([$userId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

$passenger_name = $profile['name'] ?? ($_SESSION['user_name'] ?? 'Passenger');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profile — MetroLink</title>
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
      <div class="passenger-topbar-right">
        Welcome, <?php echo htmlspecialchars($passenger_name); ?>
      </div>
    </div>
    <div class="page-header">
      <h1 class="page-title">Profile</h1>
    </div>
    <div class="passenger-dashboard">
      <div class="card">
        <h2 class="card-title">Personal Information</h2>
        <?php if ($success): ?>
          <div class="alert alert-success" style="padding:10px 14px;border-radius:6px;background:#d1fae5;color:#059669;margin-bottom:16px;">Profile updated successfully.</div>
        <?php elseif ($error): ?>
          <div class="alert alert-error" style="padding:10px 14px;border-radius:6px;background:#fee2e2;color:#dc2626;margin-bottom:16px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="post" action="">
          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" class="input-field" value="<?php echo htmlspecialchars($profile['name'] ?? $passenger_name); ?>" required/>
          </div>
          <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" class="input-field" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required/>
          </div>
          <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" class="input-field" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>"/>
          </div>
          <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
      </div>
    </div>
  </main>
</div>
<script src="<?php echo $assets; ?>/js/main.js"></script>
</body>
</html>
