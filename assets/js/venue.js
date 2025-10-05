// Venues JavaScript

class VenueManager {
    constructor() {
        this.venues = [];
        this.filteredVenues = [];
        this.loadVenues().then(() => this.init());
    }

    async loadVenues() {
        try {
            const response = await fetch('api/venues.php');
            this.venues = await response.json();
            this.filteredVenues = [...this.venues];
        } catch (error) {
            console.error('Error loading venues:', error);
        }
    }

    init() {
        this.loadFeaturedVenues();
        this.loadAllVenues();
        this.setupEventListeners();
        this.setupVenueSelection();
    }

    setupVenueSelection() {
        const venueSelectionContainer = document.getElementById('venueSelectionContainer');
        const venuePreview = document.getElementById('venuePreview');
        const selectedVenueInput = document.getElementById('selectedVenueId');

        if (venueSelectionContainer && this.venues.length > 0) {
            // Render horizontal venue option cards
            venueSelectionContainer.innerHTML = this.getVenues().map(venue => `
              <div class="card mb-3 venue-option" data-venue-id="${venue.id}">
                <div class="row g-0">
                    <div class="col-md-4">
                    <img src="${venue.images[0]}" class="img-fluid rounded-start venue-img" alt="${venue.name}">
                    </div>
                    <div class="col-md-8">
                    <div class="card-body">
                        <h5 class="card-title mb-1">${venue.name}</h5>
                        <p class="card-text text-muted small">${venue.description.substring(0,80)}...</p>
                        <small class="text-muted"><i class="fas fa-users me-1"></i>${venue.capacity} guests</small>
                    </div>
                    </div>
                </div>
              </div>
            `).join('');

            // Handle click selection
            document.querySelectorAll('.venue-option').forEach(card => {
                card.addEventListener('click', () => {
                    // Remove active from all
                    document.querySelectorAll('.venue-option').forEach(c => c.classList.remove('active'));
                    card.classList.add('active');

                    const venueId = card.dataset.venueId;
                    const venue = this.getVenueById(venueId);

                    // Set hidden input
                    selectedVenueInput.value = venue.id;

                    // Update preview
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
                });
            });
        }
    }

    setupEventListeners() {
        // Category filter buttons
        const categoryButtons = document.querySelectorAll('.category-filter');
        categoryButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const category = e.target.dataset.category;
                this.filterVenues(category);
                this.updateActiveFilter(e.target);
            });
        });

        // Venue selection for booking
        document.addEventListener('click', (e) => {
            if (e.target.closest('.venue-select-btn')) {
                const venueId = e.target.closest('.venue-select-btn').dataset.venueId;
                this.selectVenueForBooking(venueId);
            }
        });
    }

    loadFeaturedVenues() {
        const container = document.getElementById('featuredVenues');
        if (!container) return;

        const featuredVenues = this.venues.slice(0, 3);
        container.innerHTML = featuredVenues.map(venue => this.createVenueCard(venue, true)).join('');
    }

    loadAllVenues() {
        const container = document.getElementById('venuesContainer');
        if (!container) return;

        container.innerHTML = this.venues.map(venue => this.createVenueCard(venue, false)).join('');
    }

    createVenueCard(venue, isFeatured = false) {
        const limitedAmenities = venue.amenities.slice(0, 3);
        const extraCount = venue.amenities.length - 3;

        return `
            <div class="col-lg-4 col-md-6 venue-item fade-in" data-category="${venue.category}">
                <div class="card h-100">
                    <div class="position-relative">
                        <img src="${venue.images[0]}" class="card-img-top" alt="${venue.name}">
                        <span class="badge bg-secondary position-absolute top-0 start-0 m-2 text-capitalize">${venue.category}</span>
                        ${venue.availability ? '<span class="badge bg-success position-absolute top-0 end-0 m-2">Available</span>' : '<span class="badge bg-danger position-absolute top-0 end-0 m-2">Unavailable</span>'}
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${venue.name}</h5>
                        <p class="card-text text-muted">${venue.description}</p>
                        
                        <div class="mb-3">
                            <small class="text-muted d-flex align-items-center mb-2">
                                <i class="fas fa-users me-2"></i>
                                Capacity: ${venue.capacity} guests
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="mb-2">Amenities:</h6>
                            <div class="amenity-list">
                                ${limitedAmenities.map(amenity => `<span class="badge bg-light text-dark amenity-badge">${amenity}</span>`).join('')}
                                ${extraCount > 0 ? `<span class="badge bg-light text-dark amenity-badge">+${extraCount} more</span>` : ''}
                            </div>
                        </div>
                        
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center border-top pt-3">
                                <div>
                                    <small class="text-muted">Starting from</small>
                                    <div class="h5 mb-0">₱${venue.price.toLocaleString()}</div>
                                </div>
                                <button class="btn btn-danger venue-select-btn" 
                                        data-venue-id="${venue.id}" 
                                        ${!venue.availability ? 'disabled' : ''}>
                                    ${venue.availability ? 'Book This Venue' : 'Unavailable'}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    filterVenues(category) {
        const venueItems = document.querySelectorAll('.venue-item');
        
        venueItems.forEach(item => {
            if (category === 'all' || item.dataset.category === category) {
                item.style.display = 'block';
                item.classList.add('fade-in');
            } else {
                item.style.display = 'none';
                item.classList.remove('fade-in');
            }
        });
    }

    updateActiveFilter(activeButton) {
        document.querySelectorAll('.category-filter').forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        
        activeButton.classList.remove('btn-outline-primary');
        activeButton.classList.add('btn-primary');
    }

    selectVenueForBooking(venueId) {
        // Check if user is logged in
        if (!window.authManager || !window.authManager.isAuthenticated()) {
            window.location.href = 'index.php?page=login';
            return;
        }

        const venue = this.getVenueById(venueId);
        if (!venue) return;

        // Redirect with price included
        const params = new URLSearchParams({
            page: 'booking',
            venue_id: venue.id,
            venue_name: venue.name,
            venue_price: venue.price   // ✅ add this
        });

        window.location.href = 'index.php?' + params.toString();
    }

    getVenueById(venueId) {
        return this.venues.find(venue => venue.id === venueId);
    }

    getVenues() {
        return this.venues;
    }

    async loadVenuesFromAPI() {
        try {
            const response = await fetch('api/venues/list.php');
            const result = await response.json();
            
            if (result.success) {
                this.venues = result.venues;
                this.loadFeaturedVenues();
                this.loadAllVenues();
            }
        } catch (error) {
            console.error('Error loading venues:', error);
        }
    }
}

// Initialize venue manager when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.venueManager = new VenueManager();
});

document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const venueId = urlParams.get("venue_id");
    const venuePrice = urlParams.get("venue_price");  // ✅ fetch price too

    if (venueId && window.venueManager) {
        const venue = window.venueManager.getVenueById(venueId);
        const venuePreview = document.getElementById("venuePreview");
        const selectedVenueInput = document.getElementById("selectedVenueId");
        const venueNameInput = document.getElementById("selectedVenueName");
        const venuePriceInput = document.getElementById("selectedVenuePrice"); // ✅ hidden field

        if (venue) {
            if (selectedVenueInput) selectedVenueInput.value = venue.id;
            if (venueNameInput) venueNameInput.value = venue.name;
            if (venuePriceInput) venuePriceInput.value = venue.price; // ✅ save price into hidden input

            if (venuePreview) {
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
    }
});

