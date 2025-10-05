<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../config/database.php';

    // Check if $pdo exists
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection ($pdo) not initialized. Check config/database.php');
    }

    // Count total users
    $totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Count total venues
    $totalVenues = (int) $pdo->query("SELECT COUNT(*) FROM venues")->fetchColumn();

    // Count total bookings
    $totalBookings = (int) $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();

    // Sum revenue (bookings.total_amount) only for confirmed or completed bookings
    $totalRevenue = (float) $pdo->query("SELECT IFNULL(SUM(total_amount),0) FROM bookings WHERE status IN ('confirmed', 'completed')")->fetchColumn();

    // Recent bookings: use LEFT JOIN to avoid failures when FK doesn't match
    $stmt = $pdo->query("
        SELECT b.id,
               COALESCE(u.name, 'Unknown') AS user_name,
               COALESCE(v.name, 'Unknown') AS venue_name,
               b.total_amount AS amount,
               b.gcash_receipt,
               b.status,
               b.created_at
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN venues v ON b.venue_id = v.id
        ORDER BY b.created_at DESC
        LIMIT 5
    ");

    $recentBookings = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

    echo json_encode([
        "totalUsers"     => $totalUsers,
        "totalVenues"    => $totalVenues,
        "totalBookings"  => $totalBookings,
        "totalRevenue"   => $totalRevenue,
        "recentBookings" => $recentBookings
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    // Return JSON error (500) — helpful for debugging
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage()
    ]);
    exit;
}
