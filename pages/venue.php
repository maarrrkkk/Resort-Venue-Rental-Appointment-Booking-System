<!-- Page Header -->
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h1 class="display-4 mb-3">Venues</h1>
        <p class="lead">
            Choose from our collection of stunning venues from diffrent places, each designed to create unforgettable experiences
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
        <div class="row g-4" id="venuesContainer">
            <!-- Venues will be loaded by JavaScript -->
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

<!-- CTA Section -->
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="mb-3">Ready to Book Your Perfect Venue?</h2>
        <p class="lead mb-4">
            Our team is ready to help you create an unforgettable experience
        </p>
        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
            <a href="booking.html" class="btn btn-light btn-lg">Start Booking</a>
            <a href="index.html#contact" class="btn btn-outline-light btn-lg">Contact Us</a>
        </div>
    </div>
</section>
