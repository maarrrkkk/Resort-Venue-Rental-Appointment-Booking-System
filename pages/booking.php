<?php

if (!isset($_SESSION['step'])) {
    $_SESSION['step'] = 1;
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
    } elseif (isset($_POST['submit'])) {
        // Save booking to database here
        require_once "config/database.php";

        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            header("Location: index.php?page=login");
            exit;
        }

        // Use current POST data merged with session data for submit
        $currentData = array_merge($formData, $_POST);

        $venueId = $currentData['venue_id'] ?? '';
        $venuePrice = (int)($currentData['venue_price'] ?? 0);
        $guestCount = (int)($currentData['guests'] ?? 1);
        $pricePerGuest = 50;
        $totalAmount = $venuePrice + ($guestCount * $pricePerGuest);

        // Handle GCash receipt upload
        $gcashReceiptPath = null;
        if (isset($_FILES['gcash_receipt']) && $_FILES['gcash_receipt']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'assets/images/gcashreceipt/';
            $fileName = uniqid('gcash_') . '_' . basename($_FILES['gcash_receipt']['name']);
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['gcash_receipt']['tmp_name'], $targetFile)) {
                $gcashReceiptPath = $targetFile;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO bookings (id, user_id, venue_id, booking_date, start_time, end_time, duration, guest_count, event_type, special_requests, total_amount, gcash_receipt, status) VALUES (?, ?, ?, ?, '08:00:00', '17:00:00', 9, ?, ?, ?, ?, ?, 'pending')");
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
            $gcashReceiptPath
        ]);

        // ✅ Reset session for new booking
        $_SESSION['step'] = 1;
        unset($_SESSION['form']);

        // Show alert then redirect back to booking step 1
        echo "<script>
            alert('Booking submitted successfully! Your booking is pending confirmation.');
            window.location.href='index.php?page=booking';
        </script>";
        exit;
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
            if ($venueId) {
                require_once "config/database.php";
                $stmt = $pdo->prepare("SELECT capacity FROM venues WHERE id = ?");
                $stmt->execute([$venueId]);
                $venue = $stmt->fetch(PDO::FETCH_ASSOC);
                $venueCapacity = (int)($venue['capacity'] ?? 0);
            }

            $extraGuests = max(0, $guestCount - $venueCapacity);
            $totalCost = $venuePrice + ($extraGuests * $pricePerGuest);
            ?>
            <h2 class="mb-4 fw-bold text-primary p-4">Step 4: Payment</h2>
            <div class="container py-5">
                <form method="POST" enctype="multipart/form-data">
                <div class="alert alert-info rounded-3 p-4">
                    <h5 class="fw-bold">Complete Your Payment</h5>
                    <p>Please scan the GCash QR code below to pay for your booking. The venue owner will confirm your booking once payment is received.</p>

                    <div class="text-center my-4">
                        <img id="gcashQrImage" src="" alt="GCash QR Code" class="img-fluid" style="max-width: 300px;">
                        <p class="mt-3 text-muted">Scan this QR code with your GCash app</p>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <h6>Booking Summary:</h6>
                            <p>Venue: <strong><?= htmlspecialchars($formData['venue_name'] ?? '') ?></strong></p>
                            <p>Date: <strong><?= htmlspecialchars($formData['date'] ?? '') ?></strong></p>
                            <p>Guests: <strong><?= htmlspecialchars($formData['guests'] ?? '') ?> (Capacity: <?= $venueCapacity ?>)</strong></p>
                            <p>Venue Price: <strong>₱<?= number_format($venuePrice, 2) ?></strong></p>
                            <?php if ($extraGuests > 0): ?>
                            <p>Extra Guests: <strong>₱<?= number_format($extraGuests * $pricePerGuest, 2) ?> (<?= $extraGuests ?> × ₱50)</strong></p>
                            <?php endif; ?>
                            <p class="fs-4 fw-bold text-success">Total Amount: <strong>₱<?= number_format($totalCost, 2) ?></strong></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Payment Instructions (GCash):</h6>
                            <ol class="text-start">
                                <li>
                                    <strong>If you are using the same phone for payment:</strong><br>
                                    - Tap and hold the QR code above, then <u>save it to your gallery</u>.<br>
                                    - Open your GCash app, tap <strong>"Scan QR"</strong>, and choose <strong>"Upload from Gallery"</strong>.<br>
                                    - Select the saved QR code.
                                </li>
                                <li>
                                    <strong>If you are using another device (e.g., laptop + phone):</strong><br>
                                    - Simply open your GCash app on your phone and use <strong>"Scan QR"</strong> to scan the code directly from your screen.
                                </li>
                                <li>Enter the exact amount: <span class="text-primary fw-bold">₱<?= number_format($totalCost, 2) ?></span></li>
                                <li>Complete the payment securely.</li>
                                <li>Take a screenshot of your payment receipt in GCash.</li>
                                <li>Upload your screenshot proof of payment on this website to confirm your booking.</li>
                            </ol>
                            <div class="alert alert-info mt-3">
                                <i class="bi bi-info-circle"></i>
                                Tip: Make sure the amount is exact and the screenshot is clear before uploading.
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label fw-semibold">Upload GCash Receipt Screenshot</label>
                        <input type="file" class="form-control" name="gcash_receipt" accept="image/*" required>
                        <div class="form-text">Please upload a clear screenshot of your GCash payment receipt.</div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <strong>Note:</strong> Your booking will remain pending until the venue owner confirms payment receipt. You will receive a notification once confirmed.
                    </div>

                    <hr>
                    <button type="submit" name="cancel" class="btn btn-secondary px-4 me-2">Cancel</button>
                    <button type="submit" name="submit" class="btn btn-danger px-4">Submit Booking Request</button>
                </div>
                </form>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const venueId = "<?= $formData['venue_id'] ?? '' ?>";

                    if (venueId) {
                        // Fetch venue details to get GCash QR
                        fetch('api/venues.php?id=' + venueId)
                            .then(response => response.json())
                            .then(venue => {
                                if (venue.gcash_qr) {
                                    document.getElementById('gcashQrImage').src = venue.gcash_qr;
                                } else {
                                    document.getElementById('gcashQrImage').style.display = 'none';
                                    const container = document.querySelector('.text-center.my-4');
                                    container.innerHTML = '<p class="text-warning">GCash QR code not available for this venue. Please contact the venue directly.</p>';
                                }
                            })
                            .catch(error => {
                                console.error('Error fetching venue:', error);
                                document.getElementById('gcashQrImage').style.display = 'none';
                                const container = document.querySelector('.text-center.my-4');
                                container.innerHTML = '<p class="text-danger">Unable to load payment information.</p>';
                            });
                    }
                });
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