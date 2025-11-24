console.log('Booking JS loaded');

// PayPal Integration for Booking System
document.addEventListener("DOMContentLoaded", function() {
    // Initialize PayPal integration if we're on the booking page
    const currentPage = new URLSearchParams(window.location.search).get('page');
    if (currentPage === 'booking') {
        // Check if step 4 (payment) is active - this will be handled by the server-side script
        console.log('Booking page loaded - PayPal integration available');
    }
});

// Helper functions for booking form validation
function validateBookingForm() {
    const venueId = document.getElementById('selectedVenueId')?.value;
    const bookingDate = document.querySelector('input[name="date"]')?.value;
    const guestCount = document.querySelector('input[name="guests"]')?.value;
    
    if (!venueId) {
        alert('Please select a venue first.');
        return false;
    }
    
    if (!bookingDate) {
        alert('Please select a booking date.');
        return false;
    }
    
    if (!guestCount || guestCount < 1) {
        alert('Please specify the number of guests.');
        return false;
    }
    
    return true;
}

// Enhanced booking form submission
function submitBookingForm(formElement) {
    if (!validateBookingForm()) {
        return false;
    }
    
    // Add loading state
    const submitButton = formElement.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
    }
    
    return true;
}

// Utility function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount);
}

// Calculate booking total
function calculateBookingTotal() {
    const venuePrice = parseFloat(document.getElementById('selectedVenuePrice')?.value || 0);
    const guestCount = parseInt(document.querySelector('input[name="guests"]')?.value || 1);
    const pricePerGuest = 50; // Fixed price per guest
    
    // Get venue capacity for extra guest calculation
    const venueId = document.getElementById('selectedVenueId')?.value;
    if (!venueId) return { total: 0, breakdown: [] };
    
    // This would typically fetch from your venue data
    const venueCapacity = 50; // Default capacity, should be dynamic
    
    const extraGuests = Math.max(0, guestCount - venueCapacity);
    const extraGuestCost = extraGuests * pricePerGuest;
    const total = venuePrice + extraGuestCost;
    
    return {
        total: total,
        breakdown: [
            { label: 'Venue Price', amount: venuePrice },
            ...(extraGuests > 0 ? [{ label: `Extra Guests (${extraGuests} × ₱${pricePerGuest})`, amount: extraGuestCost }] : [])
        ]
    };
}