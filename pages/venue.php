<!-- Page Header -->
<section class="py-5 venues-bg text-white">
    <div class="container text-center">
        <h1 class="display-4 mb-3">Venues</h1>
        <p class="lead">
            Choose from our collection of stunning venues from diffrent places, 
            each designed to create unforgettable experiences
        </p>
    </div>
</section>


<!-- Category Filter -->
<section class="py-4 bg-light">
  <div class="container">
    <div class="text-center">
      <div class="category-buttons d-flex flex-wrap justify-content-center gap-2" role="group" aria-label="Venue categories">
        <button type="button" class="btn btn-primary category-filter" data-category="all">All Venues</button>
        <button type="button" class="btn btn-outline-primary category-filter" data-category="ballroom">Ballrooms</button>
        <button type="button" class="btn btn-outline-primary category-filter" data-category="outdoor">Outdoor</button>
        <button type="button" class="btn btn-outline-primary category-filter" data-category="conference">Conference</button>
        <button type="button" class="btn btn-outline-primary category-filter" data-category="garden">Garden</button>
      </div>
    </div>
  </div>
</section>

<!-- Venues Grid -->
<section class="py-5">
    <div class="container">
        <?php
        require_once __DIR__ . "/../config/database.php";
        
        // Get the current date in the correct format
        $today = date('Y-m-d');
        
        // Debug: Show current date
        // echo "<!-- DEBUG: Current date: $today -->";
        
        // Get all venues
        $venuesStmt = $pdo->query("SELECT * FROM venues ORDER BY name");
        $allVenues = $venuesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get today's bookings using direct date comparison
        $todayBookingsStmt = $pdo->prepare("
            SELECT venue_id, COUNT(*) as booking_count
            FROM bookings 
            WHERE booking_date = ? 
            AND status IN ('confirmed', 'pending')
            GROUP BY venue_id
        ");
        $todayBookingsStmt->execute([$today]);
        $todayBookingsMap = [];
        while ($booking = $todayBookingsStmt->fetch(PDO::FETCH_ASSOC)) {
            $todayBookingsMap[$booking['venue_id']] = $booking['booking_count'];
        }
        
        // Add availability status to each venue
        foreach ($allVenues as &$venue) {
            $venueId = $venue['id'];
            $todayBookingCount = $todayBookingsMap[$venueId] ?? 0;
            
            if ($todayBookingCount > 0) {
                $venue['availability_status'] = 'booked_today';
                $venue['today_bookings'] = $todayBookingCount;
            } else {
                $venue['availability_status'] = 'available';
                $venue['today_bookings'] = 0;
            }
        }
        unset($venue); // Break the reference
        
        $venues = $allVenues;
        ?>
        
        <div class="row g-4" id="venuesContainer">
            <?php foreach ($venues as $venue): 
                $amenities = json_decode($venue['amenities'], true) ?: [];
                $images = json_decode($venue['images'], true) ?: ['assets/images/default-venue-image.png'];
                $limitedAmenities = array_slice($amenities, 0, 3);
                $extraCount = count($amenities) - 3;
                
                // Determine availability status and styling
                switch ($venue['availability_status']) {
                    case 'booked_today':
                        $badgeClass = 'bg-danger';
                        $badgeText = 'Booked Today';
                        $buttonClass = 'btn-secondary';
                        $buttonText = 'Booked Today';
                        $buttonDisabled = 'disabled';
                        $statusIcon = 'fas fa-times-circle';
                        $statusColor = 'text-danger';
                        $statusMessage = 'Not available today';
                        break;
                    default:
                        $badgeClass = 'bg-success';
                        $badgeText = 'Available';
                        $buttonClass = 'btn-danger';
                        $buttonText = 'Book This Venue';
                        $buttonDisabled = '';
                        $statusIcon = 'fas fa-check-circle';
                        $statusColor = 'text-success';
                        $statusMessage = 'Fully available';
                }
            ?>
                <div class="col-lg-4 col-md-6 venue-item fade-in" data-category="<?= htmlspecialchars($venue['category']) ?>">
                    <div class="card h-100">
                        <div class="position-relative">
                            <img src="<?= htmlspecialchars($images[0]) ?>" class="card-img-top" alt="<?= htmlspecialchars($venue['name']) ?>" style="height: 200px; object-fit: cover;" onerror="this.src='assets/images/default-venue-image.png'">
                            <span class="badge bg-secondary position-absolute top-0 start-0 m-2 text-capitalize"><?= htmlspecialchars($venue['category']) ?></span>
                            <span class="badge <?= $badgeClass ?> position-absolute top-0 end-0 m-2"><?= $badgeText ?></span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($venue['name']) ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars($venue['description']) ?></p>
                            
                            <div class="mb-3">
                                <small class="text-muted d-flex align-items-center mb-2">
                                    <i class="fas fa-users me-2"></i>
                                    Capacity: <?= $venue['capacity'] ?> guests
                                </small>
                                <small class="<?= $statusColor ?> d-flex align-items-center">
                                    <i class="<?= $statusIcon ?> me-2"></i>
                                    <?= $statusMessage ?>
                                    <?php if ($venue['today_bookings'] > 0): ?>
                                        (<?= $venue['today_bookings'] ?> booking<?= $venue['today_bookings'] > 1 ? 's' : '' ?>)
                                    <?php endif; ?>
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <h6 class="mb-2">Amenities:</h6>
                                <div class="amenity-list">
                                    <?php foreach ($limitedAmenities as $amenity): ?>
                                        <span class="badge bg-light text-dark amenity-badge"><?= htmlspecialchars($amenity) ?></span>
                                    <?php endforeach; ?>
                                    <?php if ($extraCount > 0): ?>
                                        <span class="badge bg-light text-dark amenity-badge">+<?= $extraCount ?> more</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-auto">
                                <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                    <div>
                                        <small class="text-muted">Starting from</small>
                                        <div class="h5 mb-0">₱<?= number_format($venue['price']) ?></div>
                                    </div>
                                    <button class="btn <?= $buttonClass ?> venue-select-btn" 
                                            data-venue-id="<?= $venue['id'] ?>" 
                                            <?= $buttonDisabled ?>
                                            title="<?= $statusMessage ?>">
                                        <?= $buttonText ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div id="noVenuesMessage" class="text-center py-5 d-none">
            <i class="fas fa-building fa-3x text-muted mb-3"></i>
            <p class="lead text-muted">No venues found in this category.</p>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 mb-3">Why Choose Paradise Resort?</h2>
            <p class="lead text-muted">
                We provide exceptional service and amenities for every event
            </p>
        </div>

        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="feature-icon bg-primary bg-opacity-10 rounded-circle mx-auto mb-3">
                    <i class="fas fa-check-circle text-primary fa-2x"></i>
                </div>
                <h4 class="mb-3">Professional Service</h4>
                <p class="text-muted">
                    Dedicated event coordinators ensure every detail is perfect
                </p>
            </div>

            <div class="col-md-4 text-center">
                <div class="feature-icon bg-primary bg-opacity-10 rounded-circle mx-auto mb-3">
                    <i class="fas fa-map-marker-alt text-primary fa-2x"></i>
                </div>
                <h4 class="mb-3">Prime Location</h4>
                <p class="text-muted">
                    Stunning settings with breathtaking views and easy access
                </p>
            </div>

            <div class="col-md-4 text-center">
                <div class="feature-icon bg-primary bg-opacity-10 rounded-circle mx-auto mb-3">
                    <i class="fas fa-users text-primary fa-2x"></i>
                </div>
                <h4 class="mb-3">Flexible Capacity</h4>
                <p class="text-muted">
                    Venues suitable for intimate gatherings to large celebrations
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Include the venue JavaScript for filtering functionality -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Venue filtering functionality
    const categoryButtons = document.querySelectorAll('.category-filter');
    const venueItems = document.querySelectorAll('.venue-item');
    
    categoryButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            const category = e.target.dataset.category;
            
            // Update active button
            categoryButtons.forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline-primary');
            });
            e.target.classList.remove('btn-outline-primary');
            e.target.classList.add('btn-primary');
            
            // Filter venues
            venueItems.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                    item.classList.add('fade-in');
                } else {
                    item.style.display = 'none';
                    item.classList.remove('fade-in');
                }
            });
            
            // Show/hide no venues message
            const visibleVenues = Array.from(venueItems).filter(item => item.style.display !== 'none');
            const noVenuesMessage = document.getElementById('noVenuesMessage');
            if (visibleVenues.length === 0) {
                noVenuesMessage.classList.remove('d-none');
            } else {
                noVenuesMessage.classList.add('d-none');
            }
        });
    });
    
    // Venue selection functionality
    document.addEventListener('click', (e) => {
        if (e.target.closest('.venue-select-btn')) {
            const button = e.target.closest('.venue-select-btn');
            const venueId = button.dataset.venueId;
            
            // Check if button is disabled
            if (button.disabled) {
                alert('This venue is not available for booking today.');
                return;
            }
            
            // Check if user is logged in
            if (!window.authManager || !window.authManager.isAuthenticated()) {
                window.location.href = 'index.php?page=login';
                return;
            }
            
            // Get venue details and redirect to booking
            const venueCard = button.closest('.venue-item');
            const venueName = venueCard.querySelector('.card-title').textContent;
            const venuePrice = venueCard.querySelector('.h5').textContent.replace(/[₱,]/g, '');
            
            const params = new URLSearchParams({
                page: 'booking',
                venue_id: venueId,
                venue_name: venueName,
                venue_price: venuePrice
            });
            
            window.location.href = 'index.php?' + params.toString();
        }
    });
});
</script>
