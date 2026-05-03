<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';


requireAuth('admin');

// API_RESPONSE
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            // SECURE_QUERY - Fetch all vehicles with optional filtering
            $query = "SELECT v.*, 
                      bd.bus_id, bd.route_no, bd.sub_type,
                      td.train_id, td.line_no
                      FROM vehicles v
                      LEFT JOIN bus_details bd ON v.vehicle_id = bd.vehicle_id
                      LEFT JOIN train_details td ON v.vehicle_id = td.vehicle_id
                      ORDER BY v.vehicle_id DESC";
            
            $stmt = $pdo->query($query);
            $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $vehicles
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
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
                exit;
            }
            
           
            if (empty($data['type']) || empty($data['capacity'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Missing required fields']);
                exit;
            }
            
            $pdo->beginTransaction();
            
           
            $stmt = $pdo->prepare("INSERT INTO vehicles (type, capacity, status) VALUES (?, ?, ?)");
            $stmt->execute([
                $data['type'],
                $data['capacity'],
                $data['status'] ?? 'active'
            ]);
            
            $vehicleId = $pdo->lastInsertId();
            
        
            if ($data['type'] === 'bus' && !empty($data['route_no'])) {
                $stmt2 = $pdo->prepare("INSERT INTO bus_details (vehicle_id, route_no, sub_type) VALUES (?, ?, ?)");
                $stmt2->execute([
                    $vehicleId,
                    $data['route_no'],
                    $data['sub_type'] ?? null
                ]);
            } elseif ($data['type'] === 'train' && !empty($data['line_no'])) {
                $stmt2 = $pdo->prepare("INSERT INTO train_details (vehicle_id, line_no) VALUES (?, ?)");
                $stmt2->execute([
                    $vehicleId,
                    $data['line_no']
                ]);
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Vehicle added successfully',
                'vehicle_id' => $vehicleId
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
            
            if (!$data || empty($data['vehicle_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Vehicle ID required']);
                exit;
            }
            
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("UPDATE vehicles SET type = ?, capacity = ?, status = ? WHERE vehicle_id = ?");
            $stmt->execute([
                $data['type'],
                $data['capacity'],
                $data['status'],
                $data['vehicle_id']
            ]);
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Vehicle updated successfully'
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

    case 'DELETE':
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data || empty($data['vehicle_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Vehicle ID required']);
                exit;
            }
            
           
            $stmt = $pdo->prepare("DELETE FROM vehicles WHERE vehicle_id = ?");
            $stmt->execute([$data['vehicle_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Vehicle deleted successfully'
            ]);
            
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
exit;
