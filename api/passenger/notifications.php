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
            // SECURE_QUERY - Fetch user's notifications
            $unreadOnly = isset($_GET['unread']) && $_GET['unread'] === 'true';
            
            $query = "SELECT notif_id, title, message, is_read, created_at 
                      FROM notifications 
                      WHERE user_id = ?";
            
            if ($unreadOnly) {
                $query .= " AND is_read = FALSE";
            }
            
            $query .= " ORDER BY created_at DESC LIMIT 50";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get unread count
            $stmt2 = $pdo->prepare(
                "SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE"
            );
            $stmt2->execute([$userId]);
            $unreadCount = $stmt2->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'data' => $notifications,
                'unread_count' => $unreadCount
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
            
            if (!$data || empty($data['notif_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Notification ID required']);
                exit;
            }
            
            // Mark as read
            $stmt = $pdo->prepare(
                "UPDATE notifications SET is_read = TRUE 
                 WHERE notif_id = ? AND user_id = ?"
            );
            $stmt->execute([$data['notif_id'], $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read'
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
            
            if (!$data || empty($data['notif_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Notification ID required']);
                exit;
            }
            
            // SECURE_QUERY - Delete notification
            $stmt = $pdo->prepare(
                "DELETE FROM notifications WHERE notif_id = ? AND user_id = ?"
            );
            $stmt->execute([$data['notif_id'], $userId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Notification deleted'
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
