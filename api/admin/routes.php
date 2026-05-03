<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';


requireAuth('admin');


header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $routeId = isset($_GET['id']) ? intval($_GET['id']) : null;
            
            if ($routeId) {
              
                $stmt = $pdo->prepare(
                    "SELECT r.*, 
                            fr.fare_id, fr.passenger_type, fr.base_fare, fr.per_km_rate
                     FROM routes r
                     LEFT JOIN fare_rules fr ON r.route_id = fr.route_id
                     WHERE r.route_id = ?"
                );
                $stmt->execute([$routeId]);
                $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
              
                $stmt = $pdo->query("SELECT * FROM routes ORDER BY route_id DESC");
                $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $routes
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
            
            if (!$data || empty($data['route_name']) || empty($data['origin']) || empty($data['destination'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                exit;
            }
            
            $pdo->beginTransaction();
            
           
            $stmt = $pdo->prepare(
                "INSERT INTO routes (route_name, origin, destination, distance_km, status) 
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                htmlspecialchars($data['route_name']),
                htmlspecialchars($data['origin']),
                htmlspecialchars($data['destination']),
                $data['distance_km'] ?? 0,
                $data['status'] ?? 'active'
            ]);
            
            $routeId = $pdo->lastInsertId();
            
            
            $fareTypes = ['regular', 'student', 'senior'];
            foreach ($fareTypes as $type) {
                $stmt2 = $pdo->prepare(
                    "INSERT INTO fare_rules (route_id, passenger_type, base_fare, per_km_rate) 
                     VALUES (?, ?, ?, ?)"
                );
                $stmt2->execute([
                    $routeId,
                    $type,
                    $data['fares'][$type]['base'] ?? 20.00,
                    $data['fares'][$type]['per_km'] ?? 1.50
                ]);
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Route created successfully',
                'route_id' => $routeId
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;

    case 'PUT':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || empty($data['route_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Route ID required']);
                exit;
            }
            
         
            $stmt = $pdo->prepare(
                "UPDATE routes SET 
                    route_name = ?, origin = ?, destination = ?, 
                    distance_km = ?, status = ? 
                 WHERE route_id = ?"
            );
            $stmt->execute([
                htmlspecialchars($data['route_name']),
                htmlspecialchars($data['origin']),
                htmlspecialchars($data['destination']),
                $data['distance_km'],
                $data['status'],
                $data['route_id']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Route updated successfully'
            ]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ]);
        }
        break;

    case 'DELETE':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || empty($data['route_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Route ID required']);
                exit;
            }
            
         
            $stmt = $pdo->prepare("DELETE FROM routes WHERE route_id = ?");
            $stmt->execute([$data['route_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Route deleted successfully'
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
