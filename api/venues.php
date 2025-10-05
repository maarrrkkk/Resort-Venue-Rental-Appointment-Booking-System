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
                // Get single venue
                $stmt = $pdo->prepare("SELECT * FROM venues WHERE id=?");
                $stmt->execute([$_GET['id']]);
                $venue = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($venue) {
                    $venue['amenities'] = json_decode($venue['amenities'], true);
                    $venue['images'] = json_decode($venue['images'], true);
                    echo json_encode($venue);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Venue not found']);
                }
            } else {
                // List venues
                $stmt = $pdo->query("SELECT * FROM venues ORDER BY name");
                $venues = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($venues as &$venue) {
                    $venue['amenities'] = json_decode($venue['amenities'], true);
                    $venue['images'] = json_decode($venue['images'], true);
                }
                echo json_encode($venues);
            }
            break;

        case 'POST':
            // Create or update venue
            $id = $_POST['id'] ?? '';
            if ($id) {
                // Update existing venue
                $imagePath = '';
                $gcashQrPath = '';

                // Handle image URL or upload
                if (!empty($_POST['image_url'])) {
                    $imagePath = $_POST['image_url'];
                } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../assets/uploads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $fileName = $id . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                        $imagePath = 'assets/uploads/' . $fileName;
                    }
                }

                // Handle GCash QR
                if (isset($_FILES['gcash_qr']) && $_FILES['gcash_qr']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../assets/uploads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $fileName = $id . '_gcash.' . pathinfo($_FILES['gcash_qr']['name'], PATHINFO_EXTENSION);
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['gcash_qr']['tmp_name'], $filePath)) {
                        $gcashQrPath = 'assets/uploads/' . $fileName;
                    }
                }

                $query = "UPDATE venues SET name=?, description=?, capacity=?, price=?, category=?, location=?, amenities=?, images=?, availability=?";
                $params = [
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['capacity'],
                    $_POST['price'],
                    $_POST['category'],
                    $_POST['location'],
                    $_POST['amenities'] ?? '[]',
                    json_encode($imagePath ? [$imagePath] : []),
                    $_POST['availability'] ?? 1
                ];

                if ($gcashQrPath) {
                    $query .= ", gcash_qr=?";
                    $params[] = $gcashQrPath;
                }

                $query .= " WHERE id=?";
                $params[] = $id;

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);

                echo json_encode(['success' => true]);
            } else {
                // Create venue
                $name = $_POST['name'] ?? '';
                if (!$name) throw new Exception('Name required');

                $id = uniqid('venue_');
                $imagePath = '';
                $gcashQrPath = '';

                // Handle image URL or upload
                if (!empty($_POST['image_url'])) {
                    $imagePath = $_POST['image_url'];
                } elseif (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../assets/uploads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    $fileName = $id . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                        $imagePath = 'assets/uploads/' . $fileName;
                    }
                }

                // Handle GCash QR
                if (isset($_FILES['gcash_qr']) && $_FILES['gcash_qr']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../assets/uploads/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $fileName = $id . '_gcash.' . pathinfo($_FILES['gcash_qr']['name'], PATHINFO_EXTENSION);
                    $filePath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['gcash_qr']['tmp_name'], $filePath)) {
                        $gcashQrPath = 'assets/uploads/' . $fileName;
                    }
                }

                $stmt = $pdo->prepare("INSERT INTO venues (id, name, description, capacity, price, category, location, amenities, images, gcash_qr, availability) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $id,
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['capacity'],
                    $_POST['price'],
                    $_POST['category'],
                    $_POST['location'],
                    $_POST['amenities'] ?? '[]',
                    json_encode($imagePath ? [$imagePath] : []),
                    $gcashQrPath,
                    $_POST['availability'] ?? 1
                ]);

                echo json_encode(['success' => true, 'id' => $id]);
            }
            break;

        case 'DELETE':
            // Delete venue
            if (!isset($_GET['id'])) throw new Exception('Venue ID required');

            $id = $_GET['id'];

            // Get venue data to delete images
            $stmt = $pdo->prepare("SELECT images, gcash_qr FROM venues WHERE id=?");
            $stmt->execute([$id]);
            $venue = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($venue) {
                // Delete uploaded images
                $images = json_decode($venue['images'], true);
                foreach ($images as $image) {
                    if (strpos($image, 'assets/uploads/') === 0) {
                        $filePath = __DIR__ . '/../' . $image;
                        if (file_exists($filePath)) unlink($filePath);
                    }
                }

                // Delete GCash QR
                if ($venue['gcash_qr'] && file_exists(__DIR__ . '/../' . $venue['gcash_qr'])) {
                    unlink(__DIR__ . '/../' . $venue['gcash_qr']);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM venues WHERE id=?");
            $stmt->execute([$id]);

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