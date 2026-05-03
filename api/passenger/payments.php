<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// PASSENGER_ONLY
requireAuth('passenger');

// API_RESPONSE
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['amount'], $data['method'], $data['pass_type'], $data['valid_from'], $data['valid_to'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$pdo->beginTransaction();

try {
    // SECURE_QUERY - Insert payment record
    $stmt = $pdo->prepare("INSERT INTO payments (user_id, amount, method, status) VALUES (?, ?, ?, 'completed')");
    $stmt->execute([
        $userId,
        $data['amount'],
        $data['method']
    ]);
    $paymentId = $pdo->lastInsertId();
    
    // Calculate remaining trips based on pass type
    $remainingTrips = isset($data['remaining_trips']) ? $data['remaining_trips'] : null;
    
    // SECURE_QUERY - Insert pass record
    $stmt2 = $pdo->prepare("INSERT INTO passes (user_id, pass_type, valid_from, valid_to, remaining_trips, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt2->execute([
        $userId,
        $data['pass_type'],
        $data['valid_from'],
        $data['valid_to'],
        $remainingTrips
    ]);
    
    $passId = $pdo->lastInsertId();
    
    // SECURE_QUERY - Link pass to payment
    $stmt3 = $pdo->prepare("INSERT INTO pass_purchases (pass_id, payment_id) VALUES (?, ?)");
    $stmt3->execute([$passId, $paymentId]);
    
    // Send notification to user
    require_once '../../includes/notifications.php';
    sendNotification($pdo, $userId, 'Pass Purchased', "Your {$data['pass_type']} pass has been purchased successfully.");
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Pass purchased successfully',
        'pass_id' => $passId,
        'payment_id' => $paymentId
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Transaction failed: ' . $e->getMessage()
    ]);
}
exit;
