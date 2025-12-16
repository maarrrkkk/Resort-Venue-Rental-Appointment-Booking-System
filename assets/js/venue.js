// Venues JavaScript

class VenueManager {
    constructor() {
        this.venues = [];
        this.filteredVenues = [];
        this.venueAvailability = new Map(); // Store availability data
        this.init();
    }

    getBaseURL() {
        // Fallback baseURL construction if authManager is not available
        const path = window.location.pathname;
        let baseURL = '';
        
        // If we're in a subdirectory (like /admin/), go up one level
        if (path.includes('/admin/')) {
            baseURL = '../api/';
        } else if (path.includes('/client/')) {
            baseURL = '../api/';
        } else {
            baseURL = 'api/';
        }
        
        return baseURL;
    }

    async loadVenues() {
        try {
            // Get baseURL from authManager or fallback
            const baseURL = (window.authManager && window.authManager.baseURL) ? window.authManager.baseURL : this.getBaseURL();
            console.log('Loading venues from:', baseURL + 'venues.php');
            
            const response = await fetch(baseURL + 'venues.php');
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            this.venues = await response.json();
            this.filteredVenues = [...this.venues];
            console.log('Venues loaded:', this.venues.length);
            
            // Load availability data for all venues
            await this.loadVenueAvailability();
        } catch (error) {
            console.error('Error loading venues:', error);
            // Set fallback venues data if API fails
            this.venues = [];
            this.filteredVenues = [];
        }
    }

    async loadVenueAvailability() {
        try {
            // Get baseURL from authManager or fallback
            const baseURL = (window.authManager && window.authManager.baseURL) ? window.authManager.baseURL : this.getBaseURL();
            
            // Load availability for next 30 days for each venue
            const today = new Date();
            const futureDate = new Date(today);
            futureDate.setDate(futureDate.getDate() + 30);
            
            const startDate = today.toISOString().split('T')[0];
            const endDate = futureDate.toISOString().split('T')[0];
            
            console.log('Loading availability from', startDate, 'to', endDate);
            
            const availabilityPromises = this.venues.map(async (venue) => {
                try {
                    const url = `${baseURL}venueAvailability.php?venue_id=${venue.id}&start_date=${startDate}&end_date=${endDate}`;
                    console.log('Checking availability for venue:', venue.id, url);
                    
                    const response = await fetch(url);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Store availability data
                        this.venueAvailability.set(venue.id, data.availability);
                        
                        // Update venue object with availability info
                        const availableDates = data.availability.filter(a => a.available).length;
                        const totalDates = data.availability.length;
                        venue.availabilityPercentage = (availableDates / totalDates) * 100;
                        venue.availableDatesCount = availableDates;
                        venue.totalCheckedDates = totalDates;
                        
                        console.log(`Venue ${venue.name}: ${availableDates}/${totalDates} dates available`);
                    } else {
                        console.warn(`Failed to get availability for venue ${venue.id}:`, data.error || 'Unknown error');
                        // Set default availability data
                        this.venueAvailability.set(venue.id, []);
                        venue.availabilityPercentage = 100;
                        venue.availableDatesCount = 30;
                        venue.totalCheckedDates = 30;
                    }
                } catch (error) {
                    console.warn(`Failed to load availability for venue ${venue.id}:`, error);
                    // Set default availability data
                    this.venueAvailability.set(venue.id, []);
                    venue.availabilityPercentage = 100;
                    venue.availableDatesCount = 30;
                    venue.totalCheckedDates = 30;
                }
            });
            
            await Promise.all(availabilityPromises);
            console.log('Venue availability data loaded for all venues');
            
            // Refresh the display if venues are already rendered
            if (document.getElementById('venuesContainer')) {
                this.loadAllVenues();
            }
            
        } catch (error) {
            console.error('Error loading venue availability:', error);
        }
    }

    async init() {
        // Load venues first, then initialize other components
        await this.loadVenues();
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
        const limitedAmenities = venue.amenities ? venue.amenities.slice(0, 3) : [];
        const extraCount = venue.amenities ? venue.amenities.length - 3 : 0;
        
        // Get dynamic availability data with debugging
        const availabilityData = this.venueAvailability.get(venue.id) || [];
        const today = new Date().toISOString().split('T')[0];
        const todayAvailability = availabilityData.find(a => a.date === today);
        const hasTodayBookings = todayAvailability && !todayAvailability.available;
        
        // Calculate availability status with safe defaults
        const availableDatesCount = venue.availableDatesCount || 0;
        const totalDatesCount = venue.totalCheckedDates || 30;
        const availabilityPercentage = venue.availabilityPercentage || 100;
        
        // Debug logging
        console.log(`Venue ${venue.name} availability:`, {
            availableDatesCount,
            totalDatesCount,
            availabilityPercentage,
            hasTodayBookings,
            todayAvailability
        });
        
        // Determine availability status for display
        let availabilityBadge = '';
        let buttonClass = 'btn-danger';
        let buttonText = 'Book This Venue';
        let buttonDisabled = '';
        let availabilityInfo = '';
        
        if (hasTodayBookings) {
            // Venue has bookings today
            availabilityBadge = '<span class="badge bg-danger position-absolute top-0 end-0 m-2">Booked Today</span>';
            buttonClass = 'btn-secondary';
            buttonText = 'Booked Today';
            buttonDisabled = 'disabled';
            availabilityInfo = '<small class="text-danger"><i class="fas fa-times-circle me-1"></i>Not available today</small>';
        } else if (availabilityPercentage === 0) {
            // No availability in the next 30 days
            availabilityBadge = '<span class="badge bg-warning position-absolute top-0 end-0 m-2">Fully Booked</span>';
            buttonClass = 'btn-warning';
            buttonText = 'Fully Booked';
            buttonDisabled = 'disabled';
            availabilityInfo = '<small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i>No dates available</small>';
        } else if (availabilityPercentage < 50) {
            // Low availability
            availabilityBadge = '<span class="badge bg-warning position-absolute top-0 end-0 m-2">Limited Availability</span>';
            buttonClass = 'btn-warning';
            buttonText = `Book (${availableDatesCount} dates left)`;
            availabilityInfo = `<small class="text-warning"><i class="fas fa-calendar me-1"></i>${availableDatesCount} of ${totalDatesCount} days available</small>`;
        } else {
            // Good availability
            availabilityBadge = '<span class="badge bg-success position-absolute top-0 end-0 m-2">Available</span>';
            buttonClass = 'btn-danger';
            buttonText = `Book This Venue (${availableDatesCount} dates)`;
            availabilityInfo = `<small class="text-success"><i class="fas fa-check-circle me-1"></i>${availableDatesCount} of ${totalDatesCount} days available</small>`;
        }

        // Ensure we have required data with fallbacks
        const venueImages = venue.images && venue.images.length > 0 ? venue.images : ['assets/images/default-venue-image.png'];
        const venueAmenities = venue.amenities || [];

        return `
            <div class="col-lg-4 col-md-6 venue-item fade-in" data-category="${venue.category}">
                <div class="card h-100">
                    <div class="position-relative">
                        <img src="${venueImages[0]}" class="card-img-top" alt="${venue.name}" onerror="this.src='assets/images/default-venue-image.png'">
                        <span class="badge bg-secondary position-absolute top-0 start-0 m-2 text-capitalize">${venue.category}</span>
                        ${availabilityBadge}
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${venue.name}</h5>
                        <p class="card-text text-muted">${venue.description || 'No description available'}</p>
                        
                        <div class="mb-3">
                            <small class="text-muted d-flex align-items-center mb-2">
                                <i class="fas fa-users me-2"></i>
                                Capacity: ${venue.capacity || 'N/A'} guests
                            </small>
                            ${availabilityInfo}
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="mb-2">Amenities:</h6>
                            <div class="amenity-list">
                                ${limitedAmenities.map(amenity => `<span class="badge bg-light text-dark amenity-badge">${amenity}</span>`).join('')}
                                ${extraCount > 0 ? `<span class="badge bg-light text-dark amenity-badge">+${extraCount} more</span>` : '<span class="text-muted small">No amenities listed</span>'}
                            </div>
                        </div>
                        
                        <div class="mt-auto">
                            <div class="d-flex gap-7 justify-content-between align-items-center border-top pt-3">
                                <div>
                                    <small class="text-muted">Starting from</small>
                                    <div class="h5 mb-0">₱${(venue.price || 0).toLocaleString()}</div>
                                </div>
                                <button class="btn ${buttonClass} ms-5 venue-select-btn"
                                        data-venue-id="${venue.id}"
                                        ${buttonDisabled}
                                        title="${buttonDisabled ? 'This venue is not available for booking' : 'Click to book this venue'}">
                                    ${buttonText}
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

// Initialize venue manager when DOM is loaded - wait for authManager
document.addEventListener('DOMContentLoaded', function() {
    // Ensure authManager is initialized first, then initialize venueManager
    const initVenueManager = () => {
        if (window.authManager) {
            window.venueManager = new VenueManager();
        } else {
            // Retry after 50ms if authManager not ready
            setTimeout(initVenueManager, 50);
        }
    };
    initVenueManager();
});

// Handle venue selection from URL parameters
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const venueId = urlParams.get("venue_id");
    const venuePrice = urlParams.get("venue_price");  // ✅ fetch price too

    // Wait for venueManager to be available
    const handleVenueSelection = () => {
        if (venueId && window.venueManager && window.venueManager.venues.length > 0) {
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
        } else if (venueId) {
            // Retry if venueManager not ready yet
            setTimeout(handleVenueSelection, 100);
        }
    };
    
    handleVenueSelection();
});

