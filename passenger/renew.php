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
$error          = null;


$PASS_CATALOG = [
    'daily'       => ['label' => 'Daily Pass',       'price' => 60.00,   'days' => 1,  'trips' => null],
    'weekly'      => ['label' => 'Weekly Pass',      'price' => 350.00,  'days' => 7,  'trips' => null],
    'monthly'     => ['label' => 'Monthly Pass',     'price' => 1200.00, 'days' => 30, 'trips' => null],
    'trip-based'  => ['label' => '10-Trip Pass',     'price' => 250.00,  'days' => 60, 'trips' => 10],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type   = $_POST['pass_type']      ?? '';
    $method = $_POST['payment_method'] ?? '';

    if (!isset($PASS_CATALOG[$type])) {
        $error = 'Please select a valid pass type.';
    } elseif (empty($method)) {
        $error = 'Please select a payment method.';
    } else {
        $cfg = $PASS_CATALOG[$type];
        try {
            $pdo->beginTransaction();

           
            $pdo->prepare("INSERT INTO payments (user_id, amount, method, status) VALUES (?, ?, ?, 'completed')")
                ->execute([$userId, $cfg['price'], $method]);
            $paymentId = $pdo->lastInsertId();

         
            $validFrom = date('Y-m-d');
            $validTo   = date('Y-m-d', strtotime("+{$cfg['days']} days"));
            $pdo->prepare(
                "INSERT INTO passes (user_id, pass_type, valid_from, valid_to, remaining_trips, status)
                 VALUES (?, ?, ?, ?, ?, 'active')"
            )->execute([$userId, $type, $validFrom, $validTo, $cfg['trips']]);
            $passId = $pdo->lastInsertId();

       
            $pdo->prepare("INSERT INTO pass_purchases (pass_id, payment_id) VALUES (?, ?)")
                ->execute([$passId, $paymentId]);

     
            $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)")
                ->execute([$userId, 'Pass Purchased', ucfirst($type) . ' pass activated. Valid until ' . $validTo . '.']);

            $pdo->commit();
            header('Location: passes.php?success=purchased');
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = 'Purchase failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Buy a Pass — MetroLink</title>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/style.css"/>
  <link rel="stylesheet" href="<?php echo $assets; ?>/css/passenger.css"/>
  <style>
    .flash-error{padding:10px 14px;border-radius:6px;margin-bottom:16px;background:#fee2e2;color:#dc2626;}
    .pass-options{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;margin-bottom:20px;}
    .pass-option{border:2px solid #e5e7eb;border-radius:10px;padding:18px;cursor:pointer;transition:all .2s;background:#fff;}
    .pass-option:hover{border-color:#667eea;}
    .pass-option input{position:absolute;opacity:0;}
    .pass-option input:checked + .opt-content{color:#667eea;}
    .pass-option:has(input:checked){border-color:#667eea;background:#eef2ff;}
    .opt-label{font-weight:700;font-size:1.1rem;margin-bottom:4px;}
    .opt-price{font-size:1.5rem;font-weight:800;color:#0f172a;}
    .opt-meta{font-size:.85rem;color:#6b7280;margin-top:6px;}
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
      <h1 class="page-title">Buy / Renew Pass</h1>
    </div>
    <div class="passenger-dashboard">
      <div class="card">
        <?php if ($error): ?><div class="flash-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <form method="post">
          <h2 class="card-title">Select a Pass</h2>
          <div class="pass-options">
            <?php foreach ($PASS_CATALOG as $key => $cfg): ?>
              <label class="pass-option">
                <input type="radio" name="pass_type" value="<?php echo $key; ?>" required/>
                <div class="opt-content">
                  <div class="opt-label"><?php echo htmlspecialchars($cfg['label']); ?></div>
                  <div class="opt-price">৳<?php echo number_format($cfg['price'], 2); ?></div>
                  <div class="opt-meta">
                    Valid <?php echo $cfg['days']; ?> day<?php echo $cfg['days']>1?'s':''; ?>
                    <?php if ($cfg['trips']): ?> · <?php echo $cfg['trips']; ?> trips<?php endif; ?>
                  </div>
                </div>
              </label>
            <?php endforeach; ?>
          </div>

          <h2 class="card-title" style="margin-top:24px;">Payment Method</h2>
          <div class="form-group">
            <select name="payment_method" class="input-field" required>
              <option value="">— Choose a payment method —</option>
              <option value="bKash">bKash</option>
              <option value="Nagad">Nagad</option>
              <option value="Rocket">Rocket</option>
              <option value="Card">Credit / Debit Card</option>
            </select>
          </div>

          <div style="display:flex;gap:10px;">
            <a href="passes.php" class="btn">Cancel</a>
            <button type="submit" class="btn btn-primary">Confirm Purchase</button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>
<script src="<?php echo $assets; ?>/js/main.js"></script>
</body>
</html>
