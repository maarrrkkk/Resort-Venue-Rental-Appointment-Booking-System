<?php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents('php://input'), true);

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Get single user
                $stmt = $pdo->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id=?");
                $stmt->execute([$_GET['id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    echo json_encode($user);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'User not found']);
                }
            } else {
                // List users
                $stmt = $pdo->query("SELECT id, name, email, phone, role, created_at FROM users ORDER BY name");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($users);
            }
            break;

        case 'POST':
            // Create user
            if (!$data) throw new Exception('No data provided');

            $stmt = $pdo->prepare("INSERT INTO users (id, name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, ?, ?)");
            $id = uniqid('user_');
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

            $stmt->execute([$id, $data['name'], $data['email'], $data['phone'], $passwordHash, $data['role']]);

            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'PUT':
            // Update user
            if (!$data || !isset($data['id'])) throw new Exception('User ID required');

            $query = "UPDATE users SET name=?, email=?, phone=?, role=? WHERE id=?";
            $params = [$data['name'], $data['email'], $data['phone'], $data['role'], $data['id']];

            if (!empty($data['password'])) {
                $query = "UPDATE users SET name=?, email=?, phone=?, role=?, password_hash=? WHERE id=?";
                $params = [$data['name'], $data['email'], $data['phone'], $data['role'], password_hash($data['password'], PASSWORD_BCRYPT), $data['id']];
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            echo json_encode(['success' => true]);
            break;

        case 'DELETE':
            // Delete user
            if (!isset($_GET['id'])) throw new Exception('User ID required');

            $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
            $stmt->execute([$_GET['id']]);

            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('Method not allowed');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>