<?php
require_once '../../includes/auth.php';
require_once '../../includes/db.php';

// ADMIN_ONLY
requireAuth('admin');

// API_RESPONSE
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            $userId = isset($_GET['id']) ? intval($_GET['id']) : null;
            
            if ($userId) {
                // SECURE_QUERY - Get specific user with profile
                $stmt = $pdo->prepare(
                    "SELECT u.user_id, u.name, u.email, u.role, u.status, u.created_at,
                            p.phone, p.address, p.photo, p.date_of_birth
                     FROM users u
                     LEFT JOIN passenger_profiles p ON u.user_id = p.user_id
                     WHERE u.user_id = ?"
                );
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$user) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'error' => 'User not found']);
                    exit;
                }
                
                // Don't expose password hash
                unset($user['password_hash']);
                
                echo json_encode([
                    'success' => true,
                    'data' => $user
                ]);
                
            } else {
                // SECURE_QUERY - Get all users
                $role = isset($_GET['role']) ? $_GET['role'] : null;
                $status = isset($_GET['status']) ? $_GET['status'] : null;
                
                $query = "SELECT user_id, name, email, role, status, created_at FROM users WHERE 1=1";
                $params = [];
                
                if ($role) {
                    $query .= " AND role = ?";
                    $params[] = $role;
                }
                
                if ($status) {
                    $query .= " AND status = ?";
                    $params[] = $status;
                }
                
                $query .= " ORDER BY created_at DESC";
                
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $users
                ]);
            }
            
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
            
            if (!$data || empty($data['user_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'User ID required']);
                exit;
            }
            
            // Prevent modifying own status/role through this endpoint
            if ($data['user_id'] == $_SESSION['user_id'] && isset($data['status'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Cannot modify own status']);
                exit;
            }
            
            // SECURE_QUERY - Update user
            $stmt = $pdo->prepare(
                "UPDATE users SET status = ?, role = ? WHERE user_id = ?"
            );
            $stmt->execute([
                $data['status'],
                $data['role'],
                $data['user_id']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully'
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
            
            if (!$data || empty($data['user_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'User ID required']);
                exit;
            }
            
            // Prevent self-deletion
            if ($data['user_id'] == $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Cannot delete own account']);
                exit;
            }
            
            // SECURE_QUERY - Delete user (cascade will handle profile)
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$data['user_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
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
