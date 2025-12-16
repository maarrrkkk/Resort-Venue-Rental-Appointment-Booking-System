<?php

// Check if user is logged in
$user = $_SESSION['user'] ?? null;
if (!$user) {
    header("Location: index.php?page=login");
    exit;
}

// Get booking details from session or URL parameters
$bookingDetails = $_SESSION['completed_booking'] ?? null;

// If no booking details in session, try to get from URL
if (!$bookingDetails && isset($_GET['booking_id'])) {
    require_once "config/database.php";
    
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, v.name as venue_name, v.location as venue_location 
            FROM bookings b 
            JOIN venues v ON b.venue_id = v.id 
            WHERE b.id = ? AND b.user_id = ?
        ");
        $stmt->execute([$_GET['booking_id'], $user['id']]);
        $bookingDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching booking details: " . $e->getMessage());
    }
}

// If still no booking details, redirect to profile
if (!$bookingDetails) {
    header("Location: index.php?page=profile");
    exit;
}

// Clear the session booking details
unset($_SESSION['completed_booking']);
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Card -->
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5 text-center">
                    <!-- Success Icon -->
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                    </div>
                    
                    <!-- Success Message -->
                    <h1 class="fw-bold text-success mb-3">Payment Complete!</h1>
                    <p class="lead text-muted mb-4">Your venue booking has been successfully confirmed.</p>
                    
                    <!-- Booking Details Card -->
                    <div class="card bg-light border-0 rounded-3 mb-4">
                        <div class="card-body">
                            <h5 class="fw-bold mb-3">Booking Details</h5>
                            <div class="row text-start">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Venue:</strong><br><?= htmlspecialchars($bookingDetails['venue_name']) ?></p>
                                    <p class="mb-2"><strong>Date:</strong><br><?= date('F j, Y', strtotime($bookingDetails['booking_date'])) ?></p>
                                    <p class="mb-2"><strong>Guests:</strong><br><?= htmlspecialchars($bookingDetails['guest_count']) ?> people</p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Event Type:</strong><br><?= htmlspecialchars($bookingDetails['event_type']) ?></p>
                                    <p class="mb-2"><strong>Total Amount:</strong><br>₱<?= number_format($bookingDetails['total_amount'], 2) ?></p>
                                    <p class="mb-2"><strong>Booking ID:</strong><br><span class="text-muted"><?= htmlspecialchars($bookingDetails['id']) ?></span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Information -->
                    <div class="alert alert-success border-0 rounded-3 mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-8 text-start">
                                <h6 class="mb-1"><i class="fas fa-shield-alt me-2"></i>Payment Secured</h6>
                                <p class="mb-0 small">Your payment has been processed securely through PayPal.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <img src="assets/images/payments/paypal.png" alt="PayPal" style="height: 40px;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Next Steps -->
                    <div class="text-start mb-4">
                        <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2"></i>What happens next?</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>You'll receive a confirmation email shortly</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Our team will contact you to confirm details</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>You'll receive venue preparation guidelines</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i>We'll follow up after your event</li>
                        </ul>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        <a href="index.php?page=profile" class="btn btn-primary btn-lg px-4">
                            <i class="fas fa-user me-2"></i>View My Profile
                        </a>
                        <a href="index.php?page=my_bookings" class="btn btn-outline-primary btn-lg px-4">
                            <i class="fas fa-calendar me-2"></i>My Bookings
                        </a>
                        <a href="index.php?page=venue" class="btn btn-outline-secondary btn-lg px-4">
                            <i class="fas fa-home me-2"></i>Browse Venues
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="card border-0 rounded-4 mt-4">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="fw-bold mb-1">Need Help?</h6>
                            <p class="text-muted mb-0">Our team is here to assist you with any questions about your booking.</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <p class="mb-1"><i class="fas fa-phone me-2"></i>+1 (555) 123-4567</p>
                            <p class="mb-0"><i class="fas fa-envelope me-2"></i>events@paradiseresort.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Auto-redirect option -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Optional: Auto-redirect to profile after 30 seconds
    setTimeout(function() {
        const autoRedirect = confirm("Would you like to automatically redirect to your profile page?");
        if (autoRedirect) {
            window.location.href = 'index.php?page=profile';
        }
    }, 30000);
});

// Add some animation
const successCard = document.querySelector('.card');
if (successCard) {
    successCard.style.opacity = '0';
    successCard.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        successCard.style.transition = 'all 0.6s ease';
        successCard.style.opacity = '1';
        successCard.style.transform = 'translateY(0)';
    }, 100);
}
</script>

<style>
.card {
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.alert {
    transition: all 0.3s ease;
}

.alert:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .container {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    .card-body {
        padding: 2rem 1.5rem;
    }
}
</style>