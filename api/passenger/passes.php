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
            // SECURE_QUERY - Fetch user's passes
            $stmt = $pdo->prepare(
                "SELECT p.*, py.amount, py.method, py.status as payment_status
                 FROM passes p
                 LEFT JOIN pass_purchases pp ON p.pass_id = pp.pass_id
                 LEFT JOIN payments py ON pp.payment_id = py.payment_id
                 WHERE p.user_id = ?
                 ORDER BY p.created_at DESC"
            );
            $stmt->execute([$userId]);
            $passes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $passes
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
            
            if (!$data || empty($data['pass_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Pass ID required']);
                exit;
            }
            
            // SECURE_QUERY - Verify pass belongs to user and update
            $stmt = $pdo->prepare(
                "UPDATE passes SET status = 'cancelled' 
                 WHERE pass_id = ? AND user_id = ? AND status = 'active'"
            );
            $stmt->execute([$data['pass_id'], $userId]);
            
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Pass not found or already cancelled']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Pass cancelled successfully'
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
