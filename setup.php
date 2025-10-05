<?php
/**
 * Resort Venue Rental Appointment Booking System - Database Setup (Full Schema)
 */

require_once __DIR__ . '/includes/config.php';

$host = env('DB_HOST', 'localhost');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');
$dbname = env('DB_NAME', 'resort_booking');
$port = env('DB_PORT', '3306');

try {
    // Connect without selecting DB first
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE $dbname");

    // =========================
    // TABLES
    // =========================
    $schema = <<<SQL
    -- Users table
    CREATE TABLE IF NOT EXISTS users (
        id VARCHAR(50) PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20) NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('client', 'admin') DEFAULT 'client',
        avatar VARCHAR(255) NULL,
        email_verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_role (role)
    );

    -- Venues table
    CREATE TABLE IF NOT EXISTS venues (
        id VARCHAR(50) PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        capacity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        category ENUM('ballroom', 'outdoor', 'conference', 'garden') NOT NULL,
        location VARCHAR(200) NOT NULL,
        amenities JSON NOT NULL,
        images JSON NOT NULL,
        gcash_qr VARCHAR(255) NULL,
        setup_options JSON NULL,
        catering_options JSON NULL,
        booking_requirements JSON NULL,
        availability BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_capacity (capacity),
        INDEX idx_price (price),
        INDEX idx_availability (availability)
    );

    -- Bookings table
    CREATE TABLE IF NOT EXISTS bookings (
        id VARCHAR(50) PRIMARY KEY,
        user_id VARCHAR(50) NOT NULL,
        venue_id VARCHAR(50) NOT NULL,
        booking_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        duration INT NOT NULL COMMENT 'Duration in hours',
        guest_count INT NOT NULL,
        event_type VARCHAR(100) NOT NULL,
        special_requests TEXT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        gcash_receipt VARCHAR(255) NULL,
        status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'suspended') DEFAULT 'pending',
        payment_status ENUM('pending', 'paid', 'refunded', 'partial') DEFAULT 'pending',
        cancellation_reason TEXT NULL,
        admin_notes TEXT NULL,
        confirmation_code VARCHAR(20) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (venue_id) REFERENCES venues(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_venue_id (venue_id),
        INDEX idx_booking_date (booking_date),
        INDEX idx_status (status),
        INDEX idx_payment_status (payment_status),
        UNIQUE KEY unique_venue_datetime (venue_id, booking_date, start_time)
    );


    -- Settings table
    CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT NOT NULL,
        setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
        description TEXT NULL,
        updated_by VARCHAR(50) NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
    );
SQL;

    $pdo->exec($schema);

    // =========================
    // ALTER TABLES (add missing columns)
    // =========================
    $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS gcash_receipt VARCHAR(255) NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_expires TIMESTAMP NULL");

    // =========================
    // DEFAULT DATA
    // =========================
    $adminPass = password_hash(env('ADMIN_PASSWORD', 'admin123'), PASSWORD_BCRYPT);
    $user1Pass = password_hash(env('USER1_PASSWORD', 'admin123'), PASSWORD_BCRYPT);
    $user2Pass = password_hash(env('USER2_PASSWORD', 'admin123'), PASSWORD_BCRYPT);

    $pdo->exec("INSERT IGNORE INTO users (id, name, email, phone, password_hash, role) VALUES
        ('admin1','" . env('ADMIN_NAME', 'Resort Manager') . "','" . env('ADMIN_EMAIL', 'admin@resort.com') . "','" . env('ADMIN_PHONE', '+1-234-567-8999') . "','$adminPass','admin'),
        ('user1','" . env('USER1_NAME', 'John Smith') . "','" . env('USER1_EMAIL', 'john@example.com') . "','" . env('USER1_PHONE', '+1-234-567-8900') . "','$user1Pass','client'),
        ('user2','" . env('USER2_NAME', 'Sarah Johnson') . "','" . env('USER2_EMAIL', 'sarah@example.com') . "','" . env('USER2_PHONE', '+1-234-567-8901') . "','$user2Pass','client')
    ");

    $pdo->exec("INSERT IGNORE INTO venues (id, name, description, capacity, price, category, location, amenities, images) VALUES
        ('venue1','Grand Ballroom','Elegant ballroom perfect for weddings...',200,5000.00,'ballroom','Main Building, Level 2',
        '[\"Crystal Chandeliers\",\"Dance Floor\",\"Stage\"]','[\"https://images.unsplash.com/photo-1724855946369-9b4612c40fc2\"]'),
        ('venue2','Oceanview Terrace','Stunning outdoor venue with panoramic...',150,3500.00,'outdoor','West Wing, Terrace Level',
        '[\"Ocean View\",\"Outdoor Bar\",\"Lounge Areas\"]','[\"https://images.unsplash.com/photo-1625600879300-d59b96290d03\"]'),
        ('venue3','Executive Conference Center','Modern conference facility equipped...',100,2000.00,'conference','Business Center, Ground Floor',
        '[\"AV Equipment\",\"High-Speed WiFi\",\"Video Conferencing\"]','[\"https://images.unsplash.com/photo-1687945727613-a4d06cc41024\"]')
    ");

    $pdo->exec("INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, description) VALUES
        ('site_name','" . env('SITE_NAME', 'Paradise Resort') . "','string','Website name'),
        ('contact_email','" . env('CONTACT_EMAIL', 'events@paradiseresort.com') . "','string','Main contact email'),
        ('contact_phone','" . env('CONTACT_PHONE', '+1 (555) 123-4567') . "','string','Main contact phone'),
        ('booking_advance_days','30','number','Minimum days in advance for booking'),
        ('cancellation_policy_days','14','number','Days before event for free cancellation'),
        ('default_event_duration','4','number','Default event duration in hours'),
        ('max_guests_per_booking','500','number','Maximum guests allowed per booking'),
        ('email_notifications_enabled','true','boolean','Enable email notifications'),
        ('maintenance_mode','false','boolean','Enable maintenance mode')
    ");

    // ✅ Reconnect after setup
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Database setup failed: " . $e->getMessage());
}
