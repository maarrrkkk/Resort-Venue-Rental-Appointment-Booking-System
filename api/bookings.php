<?php
require_once "../config/database.php";

header("Content-Type: application/json");

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getBookings();
        break;
    case 'PUT':
        updateBookingStatus();
        break;
    case 'DELETE':
        deleteBooking();
        break;
    default:
        echo json_encode(["success" => false, "message" => "Method not allowed"]);
        break;
}

function getBookings() {
    $user_id = $_GET['user_id'] ?? null;

    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "User ID required"]);
        return;
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT b.id, b.booking_date, b.total_amount as amount, b.status, v.name as venue_name, u.name as user_name
            FROM bookings b
            JOIN venues v ON b.venue_id = v.id
            JOIN users u ON b.user_id = u.id
            WHERE b.user_id = :user_id
            ORDER BY b.created_at DESC
        ");
        $stmt->execute(['user_id' => $user_id]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($bookings);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

function updateBookingStatus() {
    $data = json_decode(file_get_contents("php://input"), true);
    $id = $data['id'] ?? null;
    $status = $data['status'] ?? null;

    if (!$id || !$status) {
        echo json_encode(["success" => false, "message" => "ID and status required"]);
        return;
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE bookings SET status = :status WHERE id = :id");
        $stmt->execute(['status' => $status, 'id' => $id]);
        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}

function deleteBooking() {
    $id = $_GET['id'] ?? null;

    if (!$id) {
        echo json_encode(["success" => false, "message" => "ID required"]);
        return;
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = :id");
        $stmt->execute(['id' => $id]);
        echo json_encode(["success" => true]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
}
?>