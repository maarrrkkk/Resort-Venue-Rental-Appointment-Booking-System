<?php

if (!isset($_SESSION['step'])) {
    $_SESSION['step'] = 1;
}

// Helper function to process PayPal payment completion
function processPayPalPayment($orderId, $formData, $pdo) {
    try {
        error_log("Processing PayPal payment for orderId: $orderId");

        // If no payment details in session, try to capture the payment
        $paymentDetails = $_SESSION['paypal_payment_details'] ?? null;

        if (!$paymentDetails) {
            error_log("No payment details in session, capturing payment for orderId: $orderId");

            // PayPal API configuration
            $clientId = env('PAYPAL_CLIENT_ID');
            $clientSecret = env('PAYPAL_SECRET');
            $mode = env('PAYPAL_MODE', 'sandbox');

            if (empty($clientId) || empty($clientSecret)) {
                error_log("PayPal credentials not configured");
                header("Location: index.php?page=booking&error=config_error");
                exit;
            }

            // PayPal API endpoints
            $baseUrl = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

            // Get PayPal access token
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1/oauth2/token');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
            curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Accept-Language: en_US',
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                error_log('PayPal API request failed: ' . curl_error($ch));
                header("Location: index.php?page=booking&error=api_error");
                exit;
            }

            curl_close($ch);

            if ($httpCode !== 200) {
                error_log('PayPal authentication failed');
                header("Location: index.php?page=booking&error=auth_failed");
                exit;
            }

            $authData = json_decode($response, true);
            $accessToken = $authData['access_token'] ?? null;

            if (!$accessToken) {
                error_log('Failed to obtain PayPal access token');
                header("Location: index.php?page=booking&error=token_error");
                exit;
            }

            // Capture the payment
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v2/checkout/orders/' . $orderId . '/capture');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'PayPal-Request-Id: ' . uniqid()
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                error_log('PayPal payment capture failed: ' . curl_error($ch));
                header("Location: index.php?page=booking&error=capture_error");
                exit;
            }

            curl_close($ch);

            if ($httpCode !== 201) {
                $errorData = json_decode($response, true);
                error_log('PayPal payment capture failed: ' . ($errorData['message'] ?? 'Unknown error'));
                header("Location: index.php?page=booking&error=capture_failed");
                exit;
            }

            $captureData = json_decode($response, true);

            // Extract capture information
            $purchaseUnit = $captureData['purchase_units'][0] ?? null;
            $payment = $purchaseUnit['payments']['captures'][0] ?? null;

            if (!$payment) {
                error_log('Payment capture information not found');
                header("Location: index.php?page=booking&error=capture_info_missing");
                exit;
            }

            $captureID = $payment['id'];
            $status = $payment['status'];
            $amount = $payment['amount']['value'];
            $currency = $payment['amount']['currency_code'];

            // Verify payment status
            if ($status !== 'COMPLETED') {
                error_log("Payment not completed, status: $status");
                header("Location: index.php?page=booking&error=payment_not_completed");
                exit;
            }

            $paymentDetails = [
                'payment_reference' => $captureID,
                'payment_method' => 'PAYPAL',
                'payment_status' => 'PAID',
                'paypal_order_id' => $orderId,
                'paypal_capture_id' => $captureID,
                'amount' => $amount
            ];

            $_SESSION['paypal_payment_details'] = $paymentDetails;
            error_log("Payment captured successfully: " . json_encode($paymentDetails));
        }
        
        // Create booking with PayPal payment information
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            header("Location: index.php?page=login");
            exit;
        }
        
        $venueId = $formData['venue_id'] ?? '';
        $venuePrice = (int)($formData['venue_price'] ?? 0);
        $guestCount = (int)($formData['guests'] ?? 1);
        $pricePerGuest = 50;
        $totalAmount = $venuePrice + ($guestCount * $pricePerGuest);
        
        $stmt = $pdo->prepare("
            INSERT INTO bookings (
                id, user_id, venue_id, booking_date, start_time, end_time, duration,
                guest_count, event_type, special_requests, total_amount,
                payment_reference, payment_method, payment_type, payment_status,
                paypal_order_id, paypal_capture_id, status
            ) VALUES (?, ?, ?, ?, '08:00:00', '17:00:00', 9, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')
        ");
        
        $bookingId = uniqid('booking_');
        $stmt->execute([
            $bookingId,
            $user['id'],
            $venueId,
            $formData['date'] ?? '',
            $guestCount,
            $formData['event_type'] ?? '',
            $formData['requests'] ?? '',
            $totalAmount,
            $paymentDetails['payment_reference'],
            $paymentDetails['payment_method'],
            'PayPal',
            $paymentDetails['payment_status'],
            $paymentDetails['paypal_order_id'],
            $paymentDetails['paypal_capture_id']
        ]);
        
        // Clear session data
        unset($_SESSION['paypal_payment_details']);
        unset($_SESSION['paypalBookingData']);
        $_SESSION['step'] = 1;
        unset($_SESSION['form']);
        
        // Show success message and redirect
        echo "<script>
            alert('Booking completed successfully with PayPal payment!');
            window.location.href='index.php?page=my_bookings';
        </script>";
        exit;
        
    } catch (Exception $e) {
        error_log("PayPal payment processing failed: " . $e->getMessage());
        echo "<script>
            alert('Payment completed but booking creation failed. Please contact support.');
            window.location.href='index.php?page=booking';
        </script>";
        exit;
    }
}

// ✅ Handle GET venue selection (when coming from venue.php)
if (isset($_GET['venue_id'])) {
    $_SESSION['form']['venue_id']   = $_GET['venue_id'];
    $_SESSION['form']['venue_name'] = $_GET['venue_name'] ?? '';
    $_SESSION['form']['venue_price'] = $_GET['venue_price'] ?? 0;

    // Redirect to remove query params from URL
    header("Location: index.php?page=booking");
    exit;
}

// ✅ Always pull step & form data from session
$step = $_SESSION['step'];
$formData = $_SESSION['form'] ?? [];

// ✅ Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['next'])) {
        $_SESSION['step']++;
    } elseif (isset($_POST['back'])) {
        $_SESSION['step']--;
    } elseif (isset($_POST['cancel'])) {
        $_SESSION['step'] = 1;
        unset($_SESSION['form']);
        header("Location: index.php?page=booking");
        exit;
    } elseif (isset($_POST['submit_gcash'])) {
        // Handle legacy GCash payment submission
        require_once "config/database.php";

        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            header("Location: index.php?page=login");
            exit;
        }

        // Check for upload errors first
        $uploadError = null;
        if (isset($_FILES['gcash_receipt'])) {
            if ($_FILES['gcash_receipt']['error'] !== UPLOAD_ERR_OK) {
                $uploadError = 'File upload failed. Please try again.';
            } else {
                $uploadDir = 'assets/images/gcashreceipt/';

                // Create directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $fileName = uniqid('gcash_') . '_' . basename($_FILES['gcash_receipt']['name']);
                $targetFile = $uploadDir . $fileName;

                // Validate file type (only images)
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $fileType = $_FILES['gcash_receipt']['type'];

                if (!in_array($fileType, $allowedTypes)) {
                    $uploadError = 'Only image files (JPEG, PNG, GIF, WebP) are allowed.';
                } elseif (!move_uploaded_file($_FILES['gcash_receipt']['tmp_name'], $targetFile)) {
                    $uploadError = 'Failed to save uploaded file. Please try again.';
                } else {
                    // Store full URL using BASE_URL
                    require_once 'includes/config.php';
                    $baseUrl = env('BASE_URL', 'http://localhost');
                    $gcashReceiptPath = $baseUrl . '/' . $targetFile;
                }
            }
        }

        // If there's an upload error, show it and don't proceed
        if ($uploadError) {
            echo "<script>
                alert('Upload Error: " . addslashes($uploadError) . "');
                window.location.href='index.php?page=booking';
            </script>";
            exit;
        }

        // Use current POST data merged with session data for submit
        $currentData = array_merge($formData, $_POST);

        $venueId = $currentData['venue_id'] ?? '';
        $venuePrice = (int)($currentData['venue_price'] ?? 0);
        $guestCount = (int)($currentData['guests'] ?? 1);
        $pricePerGuest = 50;
        $totalAmount = $venuePrice + ($guestCount * $pricePerGuest);

        // Generate payment reference for GCash
        $paymentReference = 'GCASH_' . uniqid();

        $stmt = $pdo->prepare("
            INSERT INTO bookings (
                id, user_id, venue_id, booking_date, start_time, end_time, duration, 
                guest_count, event_type, special_requests, total_amount, gcash_receipt,
                payment_reference, payment_method, payment_type, payment_status, status
            ) VALUES (?, ?, ?, ?, '08:00:00', '17:00:00', 9, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', 'pending')
        ");
        $bookingId = uniqid('booking_');
        $stmt->execute([
            $bookingId,
            $user['id'],
            $venueId,
            $currentData['date'] ?? '',
            $guestCount,
            $currentData['event_type'] ?? '',
            $currentData['requests'] ?? '',
            $totalAmount,
            $gcashReceiptPath,
            $paymentReference,
            'GCASH',
            'GCash'
        ]);

        // Reset session for new booking
        $_SESSION['step'] = 1;
        unset($_SESSION['form']);

        // Show alert then redirect back to booking step 1
        echo "<script>
            alert('GCash payment screenshot uploaded successfully! Your booking is pending confirmation.');
            window.location.href='index.php?page=booking';
        </script>";
        exit;
    } elseif (isset($_POST['submit'])) {
        // Handle PayPal payment submission (backup method)
        // This handles the case where PayPal JavaScript didn't work properly
        processPayPalPayment('manual_submit', $formData, $pdo);
    }

    // Merge POST into session form data
    $_SESSION['form'] = array_merge($_SESSION['form'] ?? [], $_POST);

    // Redirect back to prevent resubmission
    header("Location: index.php?page=booking");
    exit;
}
?>


<div class="container py-5">

    <!-- Linear Progress Bar -->
    <div class="progress-wrapper">
        <div class="progress">
            <div class="progress-bar" role="progressbar"
                style="width: <?= ($step / 4) * 100 ?>%;"
                aria-valuenow="<?= $step ?>"
                aria-valuemin="1"
                aria-valuemax="4">
            </div>
        </div>
        <div class="d-flex justify-content-between mt-2">
            <small class="<?= $step >= 1 ? 'fw-bold text-primary' : 'text-muted' ?>">Step 1</small>
            <small class="<?= $step >= 2 ? 'fw-bold text-primary' : 'text-muted' ?>">Step 2</small>
            <small class="<?= $step >= 3 ? 'fw-bold text-primary' : 'text-muted' ?>">Step 3</small>
            <small class="<?= $step == 4 ? 'fw-bold text-primary' : 'text-muted' ?>">Step 4</small>
        </div>
    </div>

    <!-- Step Content -->
    <div class="card shadow-md border-0 rounded-4">
        <?php if ($step == 1): ?>
            <h2 class="mb-4 fw-bold text-primary p-4">Step 1: Select Venue & Date</h2>
            <!-- Step 1: Select Venue & Date -->
            <div class="container py-5">
                <div class="row g-4">
                    <!-- Left Column: Form -->
                    <div class="col-lg-7">
                        <div class="card shadow-lg border-0 rounded-4 p-4 h-100">
                            <h2 class="mb-4 fw-bold text-primary">Step 1: Select Venue & Date</h2>
                            <form id="bookingForm" method="POST">

                                <!-- Venue Selection Button -->
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Selected Venue</label>
                                    <div class="d-flex flex-column flex-sm-row align-items-stretch gap-2">
                                        <input type="text"
                                            class="form-control"
                                            id="selectedVenueName"
                                            value="<?= htmlspecialchars($formData['venue_name'] ?? ($_GET['venue_name'] ?? '')) ?>"
                                            readonly>

                                        <a href="index.php?page=venue" class="btn btn-outline-primary w-100 w-sm-auto">
                                            Choose Venue
                                        </a>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Event Date</label>
                                    <input type="date" class="form-control" name="date" required>
                                </div>

                                <!-- Hidden input for selected venue -->
                                <input type="hidden" name="venue_id" id="selectedVenueId"
                                    value="<?= htmlspecialchars($formData['venue_id'] ?? ($_GET['venue_id'] ?? '')) ?>">
                                <input type="hidden" name="venue_name" id="selectedVenueNameHidden"
                                    value="<?= htmlspecialchars($formData['venue_name'] ?? ($_GET['venue_name'] ?? '')) ?>">
                                <input type="hidden" name="venue_price" id="selectedVenuePrice"
                                    value="<?= htmlspecialchars($formData['venue_price'] ?? ($_GET['venue_price'] ?? '')) ?>">

                                <button type="submit" name="next" class="btn btn-primary px-4">Next</button>
                            </form>
                        </div>
                    </div>

                    <!-- Right Column: Venue Preview -->
                    <div class="col-lg-5">
                        <div class="card shadow-lg border-0 rounded-4 h-100">
                            <div class="card-body" id="venuePreview">
                                <div class="text-muted text-center my-5" id="venuePreviewDefault">
                                    <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                                    <p>No venue selected yet</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



        <?php elseif ($step == 2): ?>
            <h2 class="mb-4 fw-bold text-primary p-4">Step 2: Event Details</h2>
            <div class="container py-5">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Event Type</label>
                        <select class="form-select" name="event_type" id="eventType" required>
                            <option value="" disabled <?= empty($formData['event_type']) ? 'selected' : '' ?>>-- Select Event Type --</option>
                            <option value="Wedding" <?= ($formData['event_type'] ?? '') === 'Wedding' ? 'selected' : '' ?>>Wedding</option>
                            <option value="Birthday Party" <?= ($formData['event_type'] ?? '') === 'Birthday Party' ? 'selected' : '' ?>>Birthday Party</option>
                            <option value="Conference" <?= ($formData['event_type'] ?? '') === 'Conference' ? 'selected' : '' ?>>Conference</option>
                            <option value="Corporate Event" <?= ($formData['event_type'] ?? '') === 'Corporate Event' ? 'selected' : '' ?>>Corporate Event</option>
                            <option value="Concert" <?= ($formData['event_type'] ?? '') === 'Concert' ? 'selected' : '' ?>>Concert</option>
                            <option value="Workshop" <?= ($formData['event_type'] ?? '') === 'Workshop' ? 'selected' : '' ?>>Workshop</option>
                            <option value="Seminar" <?= ($formData['event_type'] ?? '') === 'Seminar' ? 'selected' : '' ?>>Seminar</option>
                            <option value="Exhibition" <?= ($formData['event_type'] ?? '') === 'Exhibition' ? 'selected' : '' ?>>Exhibition</option>
                            <option value="Other" <?= ($formData['event_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <!-- Hidden input for custom event type -->
                    <div class="mb-3" id="customEventTypeContainer" style="display: none;">
                        <label class="form-label fw-semibold">Specify Event Type</label>
                        <input type="text" class="form-control" name="custom_event_type"
                            value="<?= htmlspecialchars($formData['custom_event_type'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Number of Guests</label>
                        <input type="number" class="form-control" name="guests" min="1"
                            value="<?= htmlspecialchars($formData['guests'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Special Requests</label>
                        <textarea class="form-control" name="requests" rows="3"><?= htmlspecialchars($formData['requests'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="back" class="btn btn-outline-secondary px-4 me-2" formnovalidate>Back</button>
                    <button type="submit" name="next" class="btn btn-primary px-4">Next</button>
                </form>
            </div>




        <?php elseif ($step == 3): ?>
            <h2 class="mb-4 fw-bold text-primary p-4">Step 3: Confirmation</h2>
            <div class="container py-5">
                <form method="POST">
                    <div class="alert alert-success rounded-3 p-4">
                        <h5 class="fw-bold">Review your booking</h5>

                        <?php
                        $venueId = $formData['venue_id'] ?? null;
                        $venuePrice = (int)($formData['venue_price'] ?? 0);   // flat venue price
                        $guestCount = (int)($formData['guests'] ?? 1);
                        $pricePerGuest = 50; // fixed 50 pesos per guest

                        // Get venue capacity
                        $venueCapacity = 0;
                        if ($venueId) {
                            require_once "config/database.php";
                            $stmt = $pdo->prepare("SELECT capacity FROM venues WHERE id = ?");
                            $stmt->execute([$venueId]);
                            $venue = $stmt->fetch(PDO::FETCH_ASSOC);
                            $venueCapacity = (int)($venue['capacity'] ?? 0);
                        }

                        // Calculate extra guests beyond venue capacity
                        $extraGuests = max(0, $guestCount - $venueCapacity);
                        $totalCost = $venuePrice + ($extraGuests * $pricePerGuest);
                        ?>

                        <!-- Venue Preview Card -->
                        <div id="confirmationVenuePreview" class="card mb-4 shadow-sm border-0">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img id="confirmationVenueImage" src="" class="img-fluid rounded-start h-100" alt="Venue Image" style="object-fit: cover;">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title fw-bold" id="confirmationVenueName"></h5>
                                        <p class="mb-2"><i class="fas fa-users me-2"></i> <span id="confirmationVenueCapacity"></span> guests</p>
                                        <p class="mb-2"><i class="fas fa-dollar-sign me-2"></i> Price: ₱<span id="confirmationVenuePrice"></span></p>
                                        <h6>Amenities:</h6>
                                        <div id="confirmationVenueAmenities" class="mb-2"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p>Date: <span class="fw-semibold"><?= htmlspecialchars($formData['date'] ?? '') ?></span></p>
                        <p>Guests: <span class="fw-semibold"><?= $guestCount ?></span></p>
                        <p>Venue Capacity: <span class="fw-semibold"><?= $venueCapacity ?></span></p>
                        <p>Event Type: <span class="fw-semibold"><?= htmlspecialchars($formData['event_type'] ?? '') ?></span></p>
                        <p>Venue Price: <span class="fw-semibold">₱<?= number_format($venuePrice, 2) ?></span></p>
                        <?php if ($extraGuests > 0): ?>
                        <p>Extra Guests (<?= $extraGuests ?> × ₱50):
                            <span class="fw-semibold">₱<?= number_format($extraGuests * $pricePerGuest, 2) ?></span>
                        </p>
                        <?php else: ?>
                        <p>Guest Charges: <span class="fw-semibold">Included in venue price</span></p>
                        <?php endif; ?>
                        <hr>
                        <h5>Total Cost: <span class="fw-bold text-success">₱<?= number_format($totalCost, 2) ?></span></h5>

                        <hr>
                        <button type="submit" name="back" class="btn btn-outline-secondary px-4 me-2" formnovalidate>Back</button>
                        <button type="submit" name="next" class="btn btn-warning px-4">Proceed to Payment</button>
                    </div>
                </form>
            </div>

            <!-- JS to load venue details dynamically -->
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const venueId = "<?= $formData['venue_id'] ?? '' ?>";

                    function renderConfirmationVenue() {
                        if (!venueId || !window.venueManager) {
                            setTimeout(renderConfirmationVenue, 100); // retry until ready
                            return;
                        }

                        const venue = window.venueManager.getVenueById(venueId);
                        if (!venue) return;

                        document.getElementById("confirmationVenueImage").src = venue.images[0];
                        document.getElementById("confirmationVenueName").textContent = venue.name;
                        document.getElementById("confirmationVenueCapacity").textContent = venue.capacity;
                        document.getElementById("confirmationVenuePrice").textContent = venue.price.toLocaleString();

                        const amenitiesContainer = document.getElementById("confirmationVenueAmenities");
                        amenitiesContainer.innerHTML = venue.amenities.map(a => 
                            `<span class="badge bg-light text-dark me-1">${a}</span>`
                        ).join('');
                    }

                    renderConfirmationVenue();
                });
                </script>



        <?php elseif ($step == 4): ?>
            <?php
            // Calculate costs for step 4
            $venueId = $formData['venue_id'] ?? null;
            $venuePrice = (int)($formData['venue_price'] ?? 0);
            $guestCount = (int)($formData['guests'] ?? 1);
            $pricePerGuest = 50;

            $venueCapacity = 0;
            $venueGCashQR = '';
            if ($venueId) {
                require_once "config/database.php";
                $stmt = $pdo->prepare("SELECT capacity, gcash_qr FROM venues WHERE id = ?");
                $stmt->execute([$venueId]);
                $venue = $stmt->fetch(PDO::FETCH_ASSOC);
                $venueCapacity = (int)($venue['capacity'] ?? 0);
                $venueGCashQR = $venue['gcash_qr'] ?? '';
            }

            $extraGuests = max(0, $guestCount - $venueCapacity);
            $totalCost = $venuePrice + ($extraGuests * $pricePerGuest);
            
            // Handle PayPal payment completion
            if (isset($_GET['payment']) && $_GET['payment'] === 'completed') {
                $orderId = $_GET['token'] ?? $_GET['order_id'] ?? '';
                if ($orderId) {
                    // Process PayPal payment completion
                    processPayPalPayment($orderId, $formData, $pdo);
                }
            }
            ?>
            <h2 class="mb-4 fw-bold text-primary p-4">Step 4: Payment</h2>
            <div class="container py-5">
                <form method="POST" id="paypalBookingForm">
                <div class="alert alert-info rounded-3 p-4">
                    <h5 class="fw-bold">Complete Your Payment</h5>
                    <p>Choose your preferred payment method to complete your booking. PayPal provides secure, instant payment processing.</p>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Booking Summary:</h6>
                            <p>Venue: <strong><?= htmlspecialchars($formData['venue_name'] ?? '') ?></strong></p>
                            <p>Date: <strong><?= htmlspecialchars($formData['date'] ?? '') ?></strong></p>
                            <p>Guests: <strong><?= htmlspecialchars($formData['guests'] ?? '') ?> (Capacity: <?= $venueCapacity ?>)</strong></p>
                            <p>Event Type: <strong><?= htmlspecialchars($formData['event_type'] ?? '') ?></strong></p>
                            <p>Venue Price: <strong>₱<?= number_format($venuePrice, 2) ?></strong></p>
                            <?php if ($extraGuests > 0): ?>
                            <p>Extra Guests: <strong>₱<?= number_format($extraGuests * $pricePerGuest, 2) ?> (<?= $extraGuests ?> × ₱50)</strong></p>
                            <?php endif; ?>
                            <hr>
                            <p class="fs-4 fw-bold text-success">Total Amount: <strong>₱<?= number_format($totalCost, 2) ?></strong></p>
                            
                            <!-- Hidden form fields for PayPal integration -->
                            <input type="hidden" name="venue_id" value="<?= htmlspecialchars($formData['venue_id'] ?? '') ?>">
                            <input type="hidden" name="venue_price" value="<?= $venuePrice ?>">
                            <input type="hidden" name="guests" value="<?= $guestCount ?>">
                            <input type="hidden" name="date" value="<?= htmlspecialchars($formData['date'] ?? '') ?>">
                            <input type="hidden" name="event_type" value="<?= htmlspecialchars($formData['event_type'] ?? '') ?>">
                            <input type="hidden" name="requests" value="<?= htmlspecialchars($formData['requests'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">PayPal Payment</h6>
                            <div class="alert alert-success">
                                <i class="fab fa-paypal me-2"></i>
                                <strong>Secure PayPal Payment</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Pay securely with your PayPal account</li>
                                    <li>Instant payment confirmation</li>
                                    <li>Protected by PayPal's security</li>
                                    <li>No need to upload screenshots</li>
                                </ul>
                            </div>
                            
                            <!-- PayPal Smart Buttons Container -->
                            <div id="paypal-button-container" class="mt-3"></div>
                            
                            <!-- Alternative Payment Option (Legacy GCash) -->
                            <div class="mt-4">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleGCashPayment()">
                                    <i class="fas fa-qrcode me-2"></i>
                                    Use GCash Instead
                                </button>
                                
                                <div id="gcash-payment-option" style="display: none;" class="mt-3">
                                    <p class="text-muted small">Legacy GCash payment (requires manual screenshot upload)</p>
                                    <?php if (!empty($venueGCashQR)): ?>
                                    <div class="text-center mb-3">
                                        <h6 class="fw-bold">Scan QR Code to Pay</h6>
                                        <img src="<?= htmlspecialchars($venueGCashQR) ?>" alt="GCash QR Code" class="img-fluid rounded" style="max-width: 200px; max-height: 200px;">
                                        <p class="text-muted small mt-2">Scan this QR code with your GCash app to pay ₱<?= number_format($totalCost, 2) ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <button type="submit" name="submit_gcash" class="btn btn-outline-warning btn-sm">
                                        <i class="fas fa-upload me-2"></i>
                                        Upload GCash Screenshot
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <strong>Payment Security:</strong> All payments are processed securely through PayPal. Your booking will be confirmed immediately after successful payment.
                    </div>

                    <hr>
                    <button type="submit" name="cancel" class="btn btn-secondary px-4 me-2" formnovalidate>Cancel</button>
                </div>
                </form>
            </div>

            <script>
                // Toggle GCash payment option
                function toggleGCashPayment() {
                    const gcashOption = document.getElementById('gcash-payment-option');
                    if (gcashOption.style.display === 'none') {
                        gcashOption.style.display = 'block';
                    } else {
                        gcashOption.style.display = 'none';
                    }
                }

                // Handle PayPal payment completion
                <?php if (isset($_GET['payment']) && $_GET['payment'] === 'completed'): ?>
                document.addEventListener("DOMContentLoaded", function() {
                    // Show success message for completed payment
                    const container = document.getElementById('paypal-button-container');
                    if (container) {
                        container.innerHTML = `
                            <div class="alert alert-success d-flex align-items-center p-3">
                                <i class="fas fa-check-circle fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Payment Completed Successfully!</h6>
                                    <p class="mb-0">Your booking is being processed. You will receive a confirmation shortly.</p>
                                </div>
                            </div>
                        `;
                    }
                });
                <?php else: ?>
                // Initialize PayPal payment when page loads (only if not completed)
                document.addEventListener("DOMContentLoaded", function() {
                    console.log('Initializing PayPal on step 4');
                    // Initialize PayPal payment flow
                    if (window.PayPalIntegration) {
                        window.PayPalIntegration.initializePayPalPayment().catch(error => {
                            console.error('Failed to initialize PayPal:', error);
                        });
                    }
                });
                <?php endif; ?>
            </script>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($formData['venue_id'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const venueId = "<?= $formData['venue_id'] ?>";
    const venuePreview = document.getElementById("venuePreview");

    function renderPreview() {
        if (!window.venueManager) {
            setTimeout(renderPreview, 100); // wait until venue.js is ready
            return;
        }

        const venue = window.venueManager.getVenueById(venueId);
        if (venue && venuePreview) {
            const defaultMsg = document.getElementById("venuePreviewDefault");
            if (defaultMsg) defaultMsg.remove();

            venuePreview.innerHTML = `
                <div class="preview-image-wrapper text-center">
                    <img src="${venue.images[0]}" class="img-fluid rounded mb-3 preview-image" alt="${venue.name}">
                </div>
                <h4 class="fw-bold">${venue.name}</h4>
                <p class="text-muted">${venue.description}</p>
                <p><i class="fas fa-users me-2"></i> Capacity: ${venue.capacity}</p>
                <p><i class="fas fa-dollar-sign me-2"></i> Price: ₱${venue.price.toLocaleString()}</p>
                <h6>Amenities:</h6>
                <div class="mb-2">
                    ${venue.amenities.map(a => `<span class="badge bg-light text-dark me-1">${a}</span>`).join('')}
                </div>
            `;
        }
    }

    renderPreview();
});
</script>
<?php endif; ?>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        const eventTypeSelect = document.getElementById("eventType");
        const customContainer = document.getElementById("customEventTypeContainer");

        console.log('Booking page DOM loaded. Elements found:', {
            eventTypeSelect: !!eventTypeSelect,
            customContainer: !!customContainer
        });

        if (!eventTypeSelect || !customContainer) {
            console.warn('Required elements for event type toggle not found on this page');
            return;
        }

        function toggleCustomInput() {
            if (eventTypeSelect.value === "Other") {
                customContainer.style.display = "block";
            } else {
                customContainer.style.display = "none";
            }
        }

        eventTypeSelect.addEventListener("change", toggleCustomInput);
        toggleCustomInput(); // Run on load in case "Other" is already selected
    });
</script>

<!-- Include PayPal JavaScript Integration -->
<script src="assets/js/paypal.js"></script>