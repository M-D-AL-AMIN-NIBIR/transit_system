<?php
require_once '../../includes/db.php';

// API_RESPONSE
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['vehicle_id'], $data['latitude'], $data['longitude'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Validate coordinates
if (!is_numeric($data['latitude']) || !is_numeric($data['longitude'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid coordinates']);
    exit;
}

// Validate coordinate ranges
if ($data['latitude'] < -90 || $data['latitude'] > 90 || 
    $data['longitude'] < -180 || $data['longitude'] > 180) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Coordinates out of range']);
    exit;
}

try {
    // SECURE_QUERY - Insert or update tracking record
    // Using INSERT with ON DUPLICATE KEY UPDATE for live tracking
    $stmt = $pdo->prepare(
        "INSERT INTO live_tracking (vehicle_id, latitude, longitude, updated_at) 
         VALUES (?, ?, ?, CURRENT_TIMESTAMP)
         ON DUPLICATE KEY UPDATE
         latitude = VALUES(latitude),
         longitude = VALUES(longitude),
         updated_at = VALUES(updated_at)"
    );
    
    $stmt->execute([
        $data['vehicle_id'],
        $data['latitude'],
        $data['longitude']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Location updated successfully',
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
exit;
