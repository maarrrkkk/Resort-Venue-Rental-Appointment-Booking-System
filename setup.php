<?php
/**
 * Resort Venue Rental Appointment Booking System - Database Setup
 * Complete database schema with proper table structure
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

    // =====================================
    // CLEAN DATABASE SETUP (Drop existing)
    // =====================================
    
    // Drop all objects in correct order (triggers first, then tables)
    $pdo->exec("DROP TRIGGER IF EXISTS create_payment_record");
    $pdo->exec("DROP TRIGGER IF EXISTS update_payment_record");
    $pdo->exec("DROP VIEW IF EXISTS booking_payment_summary");
    $pdo->exec("DROP TABLE IF EXISTS payments");
    $pdo->exec("DROP TABLE IF EXISTS bookings");
    $pdo->exec("DROP TABLE IF EXISTS venues");
    $pdo->exec("DROP TABLE IF EXISTS settings");
    $pdo->exec("DROP TABLE IF EXISTS users");

    // =====================================
    // CREATE TABLES
    // =====================================

    // 1. USERS TABLE
    $pdo->exec("
        CREATE TABLE users (
            id VARCHAR(50) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(20) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('client', 'admin') DEFAULT 'client',
            avatar VARCHAR(255) NULL,
            email_verified BOOLEAN DEFAULT FALSE,
            reset_token VARCHAR(255) NULL,
            reset_expires TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_email (email),
            INDEX idx_role (role),
            INDEX idx_reset_token (reset_token)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 2. VENUES TABLE
    $pdo->exec("
        CREATE TABLE venues (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 3. BOOKINGS TABLE (with PayPal fields)
    $pdo->exec("
        CREATE TABLE bookings (
            id VARCHAR(50) PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            venue_id VARCHAR(50) NOT NULL,
            booking_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            duration INT NOT NULL,
            guest_count INT NOT NULL,
            event_type VARCHAR(100) NOT NULL,
            special_requests TEXT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            gcash_receipt VARCHAR(255) NULL,
            payment_reference VARCHAR(255) NULL,
            payment_method VARCHAR(50) NULL,
            payment_type VARCHAR(50) NULL,
            payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
            paypal_order_id VARCHAR(255) NULL,
            paypal_capture_id VARCHAR(255) NULL,
            status ENUM('pending', 'confirmed', 'cancelled', 'completed', 'suspended') DEFAULT 'pending',
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
            INDEX idx_payment_method (payment_method),
            INDEX idx_paypal_order_id (paypal_order_id),
            INDEX idx_paypal_capture_id (paypal_capture_id),
            UNIQUE KEY unique_venue_datetime (venue_id, booking_date, start_time)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 4. PAYMENTS TABLE
    $pdo->exec("
        CREATE TABLE payments (
            id VARCHAR(255) PRIMARY KEY,
            booking_id VARCHAR(50) NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            payment_provider VARCHAR(50) NOT NULL,
            provider_transaction_id VARCHAR(255) NULL,
            amount DECIMAL(10,2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'PHP',
            status ENUM('pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled') NOT NULL,
            provider_response TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
            INDEX idx_booking_id (booking_id),
            INDEX idx_status (status),
            INDEX idx_payment_method (payment_method)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // 5. SETTINGS TABLE
    $pdo->exec("
        CREATE TABLE settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT NOT NULL,
            setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
            description TEXT NULL,
            updated_by VARCHAR(50) NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // =====================================
    // CREATE VIEWS
    // =====================================

    $pdo->exec("
        CREATE VIEW booking_payment_summary AS
        SELECT 
            b.id,
            b.user_id,
            u.name as user_name,
            u.email as user_email,
            b.venue_id,
            v.name as venue_name,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.duration,
            b.guest_count,
            b.event_type,
            b.special_requests,
            b.total_amount,
            b.status as booking_status,
            b.payment_status,
            b.payment_method,
            b.payment_type,
            b.payment_reference,
            b.gcash_receipt,
            b.paypal_order_id,
            b.paypal_capture_id,
            b.cancellation_reason,
            b.admin_notes,
            b.confirmation_code,
            b.created_at,
            b.updated_at,
            CASE 
                WHEN b.payment_method = 'PAYPAL' AND b.paypal_capture_id IS NOT NULL THEN 'PayPal Completed'
                WHEN b.payment_method = 'PAYPAL' AND b.paypal_order_id IS NOT NULL THEN 'PayPal Pending'
                WHEN b.payment_method = 'GCASH' AND b.gcash_receipt IS NOT NULL THEN 'GCash Uploaded'
                WHEN b.payment_method = 'GCASH' THEN 'Awaiting GCash Screenshot'
                ELSE 'Awaiting Payment'
            END as payment_display_status,
            CASE 
                WHEN b.payment_status = 'paid' THEN 'success'
                WHEN b.payment_status = 'pending' THEN 'warning'
                WHEN b.payment_status = 'failed' THEN 'danger'
                WHEN b.payment_status = 'refunded' THEN 'info'
                ELSE 'secondary'
            END as payment_status_class
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN venues v ON b.venue_id = v.id
    ");

    // =====================================
    // CREATE TRIGGERS
    // =====================================

    $pdo->exec("
        CREATE TRIGGER create_payment_record 
        AFTER INSERT ON bookings
        FOR EACH ROW
        BEGIN
            INSERT INTO payments (
                id,
                booking_id,
                payment_method,
                payment_provider,
                amount,
                status
            ) VALUES (
                CONCAT('payment_', NEW.id, '_', UNIX_TIMESTAMP()),
                NEW.id,
                COALESCE(NEW.payment_method, 'unknown'),
                CASE 
                    WHEN NEW.payment_method = 'PAYPAL' THEN 'paypal'
                    WHEN NEW.payment_method = 'GCASH' THEN 'gcash'
                    ELSE 'unknown'
                END,
                NEW.total_amount,
                CASE 
                    WHEN NEW.payment_status = 'paid' THEN 'completed'
                    WHEN NEW.payment_status = 'failed' THEN 'failed'
                    ELSE 'pending'
                END
            );
        END
    ");

    $pdo->exec("
        CREATE TRIGGER update_payment_record 
        AFTER UPDATE ON bookings
        FOR EACH ROW
        BEGIN
            UPDATE payments 
            SET 
                status = CASE 
                    WHEN NEW.payment_status = 'paid' THEN 'completed'
                    WHEN NEW.payment_status = 'failed' THEN 'failed'
                    WHEN NEW.payment_status = 'refunded' THEN 'refunded'
                    ELSE 'pending'
                END,
                provider_transaction_id = CASE 
                    WHEN NEW.payment_method = 'PAYPAL' THEN NEW.paypal_capture_id
                    WHEN NEW.payment_method = 'GCASH' THEN NEW.payment_reference
                    ELSE provider_transaction_id
                END,
                updated_at = CURRENT_TIMESTAMP
            WHERE booking_id = NEW.id;
        END
    ");

    // =====================================
    // INSERT DEFAULT DATA
    // =====================================

    // Default admin and test users (with email verification enabled)
    $adminPass = password_hash(env('ADMIN_PASSWORD', 'admin123'), PASSWORD_BCRYPT);
    $user1Pass = password_hash(env('USER1_PASSWORD', 'admin123'), PASSWORD_BCRYPT);
    $user2Pass = password_hash(env('USER2_PASSWORD', 'admin123'), PASSWORD_BCRYPT);

    $pdo->exec("INSERT INTO users (id, name, email, phone, password_hash, role, email_verified) VALUES
        ('admin1','" . env('ADMIN_NAME', 'Resort Manager') . "','" . env('ADMIN_EMAIL', 'admin@resort.com') . "','" . env('ADMIN_PHONE', '+1-234-567-8999') . "','$adminPass','admin', TRUE),
        ('user1','" . env('USER1_NAME', 'John Smith') . "','" . env('USER1_EMAIL', 'john@example.com') . "','" . env('USER1_PHONE', '+1-234-567-8900') . "','$user1Pass','client', TRUE),
        ('user2','" . env('USER2_NAME', 'Sarah Johnson') . "','" . env('USER2_EMAIL', 'sarah@example.com') . "','" . env('USER2_PHONE', '+1-234-567-8901') . "','$user2Pass','client', TRUE)
    ");

    // Default venues
    $pdo->exec("INSERT INTO venues (id, name, description, capacity, price, category, location, amenities, images) VALUES
        ('venue1','Grand Ballroom','Elegant ballroom perfect for weddings and large events with crystal chandeliers and a premium dance floor.',200,5000.00,'ballroom','Main Building, Level 2','[\"Crystal Chandeliers\",\"Dance Floor\",\"Stage\",\"Premium Sound System\",\"Climate Control\"]','[\"https://images.unsplash.com/photo-1724855946369-9b4612c40fc2\",\"https://images.unsplash.com/photo-1560448075-bb4caa6c56fd\"]'),
        ('venue2','Oceanview Terrace','Stunning outdoor venue with panoramic ocean views, perfect for sunset ceremonies and cocktail receptions.',150,3500.00,'outdoor','West Wing, Terrace Level','[\"Ocean View\",\"Outdoor Bar\",\"Lounge Areas\",\"Garden Setting\",\"Natural Lighting\"]','[\"https://images.unsplash.com/photo-1625600879300-d59b96290d03\",\"https://images.unsplash.com/photo-1464366400600-7168b8af9bc3\"]'),
        ('venue3','Executive Conference Center','Modern conference facility equipped with state-of-the-art AV technology for corporate events and meetings.',100,2000.00,'conference','Business Center, Ground Floor','[\"AV Equipment\",\"High-Speed WiFi\",\"Video Conferencing\",\"Whiteboard\",\"Climate Control\"]','[\"https://images.unsplash.com/photo-1687945727613-a4d06cc41024\",\"https://images.unsplash.com/photo-1560472354-b33ff0c44a43\"]')
    ");

    // Default settings
    $pdo->exec("INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
        ('site_name','" . env('SITE_NAME', 'Paradise Resort') . "','string','Website name'),
        ('contact_email','" . env('CONTACT_EMAIL', 'events@paradiseresort.com') . "','string','Main contact email'),
        ('contact_phone','" . env('CONTACT_PHONE', '+1 (555) 123-4567') . "','string','Main contact phone'),
        ('booking_advance_days','30','number','Minimum days in advance for booking'),
        ('cancellation_policy_days','14','number','Days before event for free cancellation'),
        ('default_event_duration','4','number','Default event duration in hours'),
        ('max_guests_per_booking','500','number','Maximum guests allowed per booking'),
        ('email_notifications_enabled','true','boolean','Enable email notifications'),
        ('maintenance_mode','false','boolean','Enable maintenance mode'),
        ('paypal_enabled','true','boolean','Enable PayPal payments'),
        ('gcash_enabled','true','boolean','Enable GCash payments'),
        ('default_currency','PHP','string','Default currency for payments')
    ");

    // Reconnect to ensure proper connection
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Database setup completed successfully!\n\n";
    echo "Tables created:\n";
    echo "- users (for login/registration)\n";
    echo "- venues\n";
    echo "- bookings (with PayPal fields)\n";
    echo "- payments\n";
    echo "- settings\n\n";
    echo "Views created:\n";
    echo "- booking_payment_summary\n\n";
    echo "Triggers created:\n";
    echo "- create_payment_record\n";
    echo "- update_payment_record\n\n";
    echo "Default data inserted:\n";
    echo "- Admin user: admin@resort.com\n";
    echo "- Test users: john@example.com, sarah@example.com\n";
    echo "- Sample venues\n";
    echo "- Default settings\n\n";

} catch (PDOException $e) {
    die("❌ Database setup failed: " . $e->getMessage() . "\n");
}
?>
