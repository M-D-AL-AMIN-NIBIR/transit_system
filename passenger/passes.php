<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'passenger') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db.php';

$current_page   = 'passes';
$passenger_name = $_SESSION['user_name'] ?? 'Passenger';
$userId         = $_SESSION['user_id'];
$assets         = '../assets';
$flash_success  = $_GET['success'] ?? null;

// Handle cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel_pass') {
    $passId = (int)($_POST['pass_id'] ?? 0);
    $pdo->prepare("UPDATE passes SET status = 'cancelled' WHERE pass_id = ? AND user_id = ?")
        ->execute([$passId, $userId]);
    header('Location: passes.php?success=cancelled');
    exit();
}

// Auto-expire
$pdo->prepare("UPDATE passes SET status = 'expired'
               WHERE user_id = ? AND status = 'active'
                 AND ((valid_to IS NOT NULL AND valid_to < CURDATE())
                      OR (remaining_trips IS NOT NULL AND remaining_trips <= 0))")
    ->execute([$userId]);

$stmt = $pdo->prepare(
    "SELECT * FROM passes WHERE user_id = ? ORDER BY pass_id DESC"
);
$stmt->execute([$userId]);
$passes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$active_passes = array_filter($passes, fn($p) => $p['status'] === 'active');
$past_passes   = array_filter($passes, fn($p) => $p['status'] !== 'active');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Passes — MetroLink</title>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/style.css"/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/passenger.css"/>
  <style>
    .flash{padding:10px 14px;border-radius:6px;margin-bottom:16px;background:#d1fae5;color:#059669;}
    .pass-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;}
    .pass-card{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#fff;border-radius:12px;padding:20px;}
    .pass-card.expired{background:linear-gradient(135deg,#9ca3af,#6b7280);}
    .pass-card.cancelled{background:linear-gradient(135deg,#ef4444,#991b1b);}
    .pass-type{font-size:1.25rem;font-weight:700;margin-bottom:8px;}
    .pass-meta{font-size:.85rem;opacity:.9;line-height:1.6;}
    .inline-form{display:inline;margin-top:10px;}
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
      <h1 class="page-title">My Passes</h1>
    </div>
    <div class="passenger-dashboard">

      <div class="card">
        <?php if ($flash_success): ?><div class="flash">Pass cancelled successfully.</div><?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
          <h2 class="card-title" style="margin:0;">Active Passes</h2>
          <a href="renew.php" class="btn btn-primary">+ Buy New Pass</a>
        </div>

        <?php if (empty($active_passes)): ?>
          <p>No active passes. <a href="renew.php">Purchase a pass</a> to get started.</p>
        <?php else: ?>
          <div class="pass-grid">
            <?php foreach ($active_passes as $p): ?>
              <div class="pass-card">
                <div class="pass-type"><?php echo htmlspecialchars(ucfirst($p['pass_type'])); ?> Pass</div>
                <div class="pass-meta">
                  Pass #<?php echo $p['pass_id']; ?><br/>
                  <?php if ($p['valid_to']): ?>Valid until <?php echo htmlspecialchars($p['valid_to']); ?><br/><?php endif; ?>
                  <?php if ($p['remaining_trips'] !== null): ?>Trips remaining: <strong><?php echo (int)$p['remaining_trips']; ?></strong><br/><?php endif; ?>
                  Issued: <?php echo htmlspecialchars(date('Y-m-d', strtotime($p['created_at']))); ?>
                </div>
                <form method="post" class="inline-form" onsubmit="return confirm('Cancel this pass? This cannot be undone.');">
                  <input type="hidden" name="action" value="cancel_pass"/>
                  <input type="hidden" name="pass_id" value="<?php echo $p['pass_id']; ?>"/>
                  <button type="submit" style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.4);padding:6px 14px;border-radius:6px;cursor:pointer;font-size:.85rem;">Cancel Pass</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if (!empty($past_passes)): ?>
        <div class="card">
          <h2 class="card-title">Past Passes</h2>
          <div class="pass-grid">
            <?php foreach ($past_passes as $p): ?>
              <div class="pass-card <?php echo htmlspecialchars($p['status']); ?>">
                <div class="pass-type"><?php echo htmlspecialchars(ucfirst($p['pass_type'])); ?> Pass</div>
                <div class="pass-meta">
                  Status: <strong><?php echo htmlspecialchars(ucfirst($p['status'])); ?></strong><br/>
                  <?php if ($p['valid_to']): ?>Valid until <?php echo htmlspecialchars($p['valid_to']); ?><br/><?php endif; ?>
                  Issued: <?php echo htmlspecialchars(date('Y-m-d', strtotime($p['created_at']))); ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>
<script src="<?php echo $assets; ?>/js/main.js"></script>
</body>
</html>
