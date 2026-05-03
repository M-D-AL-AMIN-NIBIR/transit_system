<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// PASSENGER_ONLY
requireAuth('passenger');

// API_RESPONSE
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'];

switch ($method) {
    case 'GET':
        try {
            // SECURE_QUERY - Fetch user's trip history
            $stmt = $pdo->prepare(
                "SELECT t.*, r.route_name, r.origin, r.destination, v.type as vehicle_type
                 FROM trips t
                 LEFT JOIN routes r ON t.route_id = r.route_id
                 LEFT JOIN vehicles v ON t.vehicle_id = v.vehicle_id
                 WHERE t.user_id = ?
                 ORDER BY t.start_time DESC"
            );
            $stmt->execute([$userId]);
            $trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $trips
            ]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    case 'POST':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || empty($data['route_id']) || empty($data['vehicle_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                exit;
            }
            
            // Check if user has valid pass
            $stmt = $pdo->prepare(
                "SELECT pass_id, remaining_trips, pass_type FROM passes 
                 WHERE user_id = ? AND status = 'active' 
                 AND valid_from <= CURDATE() AND valid_to >= CURDATE()"
            );
            $stmt->execute([$userId]);
            $pass = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$pass) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'No valid pass found']);
                exit;
            }
            
            // Check remaining trips for trip-based passes
            if ($pass['pass_type'] === 'trip-based' && $pass['remaining_trips'] <= 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'No remaining trips on pass']);
                exit;
            }
            
            // Calculate fare
            $stmt = $pdo->prepare(
                "SELECT base_fare FROM fare_rules 
                 WHERE route_id = ? AND passenger_type = 'regular'"
            );
            $stmt->execute([$data['route_id']]);
            $fare = $stmt->fetchColumn() ?: 30.00;
            
            // SECURE_QUERY - Start trip
            $stmt = $pdo->prepare(
                "INSERT INTO trips (user_id, pass_id, route_id, vehicle_id, start_time, fare_deducted) 
                 VALUES (?, ?, ?, ?, NOW(), ?)"
            );
            $stmt->execute([
                $userId,
                $pass['pass_id'],
                $data['route_id'],
                $data['vehicle_id'],
                $fare
            ]);
            
            $tripId = $pdo->lastInsertId();
            
            // Decrement remaining trips for trip-based passes
            if ($pass['pass_type'] === 'trip-based') {
                $stmt = $pdo->prepare(
                    "UPDATE passes SET remaining_trips = remaining_trips - 1 
                     WHERE pass_id = ?"
                );
                $stmt->execute([$pass['pass_id']]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Trip started successfully',
                'trip_id' => $tripId
            ]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    case 'PUT':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || empty($data['trip_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Trip ID required']);
                exit;
            }
            
            // SECURE_QUERY - End trip
            $stmt = $pdo->prepare(
                "UPDATE trips SET end_time = NOW() 
                 WHERE trip_id = ? AND user_id = ? AND end_time IS NULL"
            );
            $stmt->execute([$data['trip_id'], $userId]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Trip not found or already ended']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Trip ended successfully'
            ]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
exit;
