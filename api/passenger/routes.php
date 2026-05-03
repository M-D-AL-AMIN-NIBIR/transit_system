<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// AUTH_REQUIRED - Both admin and passenger can view routes
requireAuth();

// API_RESPONSE
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Check if specific vehicle tracking is requested
    $vehicleId = isset($_GET['vehicle_id']) ? intval($_GET['vehicle_id']) : null;
    
    if ($vehicleId) {
        // SECURE_QUERY - Get live tracking for specific vehicle
        $stmt = $pdo->prepare(
            "SELECT v.vehicle_id, v.type, v.capacity, r.route_name, r.origin, r.destination,
                    lt.latitude, lt.longitude, lt.updated_at
             FROM vehicles v
             LEFT JOIN route_assignments ra ON v.vehicle_id = ra.vehicle_id
             LEFT JOIN routes r ON ra.route_id = r.route_id
             LEFT JOIN live_tracking lt ON v.vehicle_id = lt.vehicle_id
             WHERE v.vehicle_id = ? AND v.status = 'active'"
        );
        $stmt->execute([$vehicleId]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$vehicle) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Vehicle not found']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $vehicle
        ]);
        
    } else {
        // SECURE_QUERY - Get all active routes
        $stmt = $pdo->query(
            "SELECT r.*, v.vehicle_id, v.type, v.capacity, ra.schedule_time
             FROM routes r
             LEFT JOIN route_assignments ra ON r.route_id = ra.route_id
             LEFT JOIN vehicles v ON ra.vehicle_id = v.vehicle_id
             WHERE r.status = 'active'
             ORDER BY r.route_id, ra.schedule_time"
        );
        $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // SECURE_QUERY - Get active vehicles with live tracking
        $stmt2 = $pdo->query(
            "SELECT v.vehicle_id, v.type, v.capacity, lt.latitude, lt.longitude, lt.updated_at
             FROM vehicles v
             LEFT JOIN live_tracking lt ON v.vehicle_id = lt.vehicle_id
             WHERE v.status = 'active'"
        );
        $vehicles = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'routes' => $routes,
                'vehicles' => $vehicles
            ]
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
exit;
