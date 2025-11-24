/**
 * PayPal Smart Buttons Integration for Venue Booking System
 * Handles PayPal payment processing on the frontend
 */

// PayPal configuration
const PAYPAL_CONFIG = {
    currency: 'PHP',
    environment: 'sandbox', // 'sandbox' for testing, 'live' for production
    clientId: '', // Will be loaded from PayPal SDK
    intent: 'capture'
};

/**
 * Initialize PayPal Smart Buttons
 * This function should be called after the PayPal SDK is loaded
 */
function initPayPalButtons(bookingData) {
    return new Promise((resolve, reject) => {
        console.log('Initializing PayPal buttons with data:', bookingData);

        if (typeof paypal === 'undefined') {
            console.error('PayPal SDK not loaded');
            reject(new Error('PayPal SDK not loaded'));
            return;
        }

        // PayPal button container
        const paypalButtonsContainer = document.getElementById('paypal-button-container');
        console.log('PayPal button container found:', !!paypalButtonsContainer);

        if (!paypalButtonsContainer) {
            console.error('PayPal button container not found');
            reject(new Error('PayPal button container not found'));
            return;
        }

        console.log('Rendering PayPal redirect button');

        // Create custom PayPal button for redirect
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-primary btn-lg w-100';
        button.innerHTML = '<i class="fab fa-paypal me-2"></i>Pay with PayPal';
        button.style.backgroundColor = '#0070ba';
        button.style.borderColor = '#0070ba';
        button.style.color = 'white';
        button.style.padding = '12px';
        button.style.fontSize = '16px';

        button.onclick = async function() {
            try {
                showPaymentLoading('Creating PayPal order...');
                const orderData = await createPayPalOrderForRedirect(bookingData);
                hidePaymentLoading();
                // Redirect to PayPal approve URL
                window.location.href = orderData.approveUrl;
            } catch (error) {
                hidePaymentLoading();
                console.error('Error:', error);
                showPaymentError('Failed to create payment. Please try again.');
            }
        };

        paypalButtonsContainer.appendChild(button);
        console.log('PayPal redirect button rendered successfully');
        resolve();
    });
}

/**
 * Create PayPal order
 */
async function createPayPalOrder(bookingData, actions) {
    try {
        console.log('Creating PayPal order with booking data:', bookingData);

        // Show loading state
        showPaymentLoading('Creating PayPal order...');

        // Prepare booking data for API
        const apiBookingData = {
            venue_id: bookingData.venue_id,
            venue_price: bookingData.venue_price,
            guest_count: bookingData.guest_count,
            booking_date: bookingData.booking_date,
            event_type: bookingData.event_type,
            special_requests: bookingData.special_requests
        };

        console.log('Sending API request to paypalCreateOrder.php with data:', apiBookingData);

        // Call backend API to create PayPal order
        const response = await fetch('api/paypalCreateOrder.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(apiBookingData)
        });

        console.log('API response status:', response.status);

        const result = await response.json();
        console.log('API response result:', result);

        if (!response.ok || !result.success) {
            throw new Error(result.error || 'Failed to create PayPal order');
        }

        console.log('PayPal order created successfully, orderID:', result.orderID);

        // Store booking data for later use
        sessionStorage.setItem('paypalBookingData', JSON.stringify({
            ...apiBookingData,
            orderID: result.orderID,
            total_amount: result.total_amount
        }));

        hidePaymentLoading();
        return result.orderID;

    } catch (error) {
        hidePaymentLoading();
        console.error('Error creating PayPal order:', error);
        showPaymentError('Failed to create payment order. Please try again.');
        throw error;
    }
}

/**
 * Handle PayPal payment approval
 */
async function handlePayPalApprove(data, actions, bookingData) {
    try {
        showPaymentLoading('Processing payment...');
        
        // Get stored booking data
        const storedData = JSON.parse(sessionStorage.getItem('paypalBookingData') || '{}');
        
        // Capture the payment
        const response = await fetch('api/paypalCapture.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                orderID: data.orderID,
                booking_data: {
                    total_amount: storedData.total_amount
                }
            })
        });
        
        const result = await response.json();
        
        if (!response.ok || !result.success) {
            throw new Error(result.error || 'Payment capture failed');
        }
        
        hidePaymentLoading();
        
        // Show success message
        showPaymentSuccess('Payment completed successfully! Redirecting...');
        
        // Store payment details for final booking creation
        sessionStorage.setItem('paypalPaymentDetails', JSON.stringify({
            payment_reference: result.captureID,
            payment_method: 'PAYPAL',
            payment_status: 'PAID',
            paypal_order_id: data.orderID,
            paypal_capture_id: result.captureID,
            amount: result.amount,
            currency: result.currency
        }));
        
        // Redirect to booking completion
        setTimeout(() => {
            window.location.href = 'index.php?page=booking&payment=completed&order_id=' + data.orderID;
        }, 2000);
        
        return result;
        
    } catch (error) {
        hidePaymentLoading();
        console.error('Error capturing PayPal payment:', error);
        showPaymentError('Payment processing failed. Please contact support if amount was charged.');
        throw error;
    }
}

/**
 * Load PayPal SDK dynamically
 */
function loadPayPalSDK(clientId) {
    return new Promise((resolve, reject) => {
        // Check if PayPal SDK is already loaded
        if (typeof paypal !== 'undefined') {
            resolve();
            return;
        }
        
        // Create PayPal SDK script
        const script = document.createElement('script');
        script.src = `https://www.paypal.com/sdk/js?client-id=${clientId}&currency=PHP&intent=capture`;
        script.onload = resolve;
        script.onerror = reject;
        
        document.head.appendChild(script);
    });
}

/**
 * Show payment loading state
 */
function showPaymentLoading(message = 'Processing...') {
    const container = document.getElementById('paypal-button-container');
    if (container) {
        container.innerHTML = `
            <div class="d-flex align-items-center justify-content-center p-4">
                <div class="spinner-border text-primary me-3" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <span>${message}</span>
            </div>
        `;
    }
}

/**
 * Hide payment loading state
 */
function hidePaymentLoading() {
    // The loading state will be replaced by the PayPal buttons when re-rendered
}

/**
 * Show payment success message
 */
function showPaymentSuccess(message) {
    const container = document.getElementById('paypal-button-container');
    if (container) {
        container.innerHTML = `
            <div class="alert alert-success d-flex align-items-center p-4">
                <i class="fas fa-check-circle fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">Payment Successful!</h5>
                    <p class="mb-0">${message}</p>
                </div>
            </div>
        `;
    }
}

/**
 * Show payment error message
 */
function showPaymentError(message) {
    const container = document.getElementById('paypal-button-container');
    if (container) {
        container.innerHTML = `
            <div class="alert alert-danger d-flex align-items-center p-4">
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">Payment Error</h5>
                    <p class="mb-0">${message}</p>
                    <button type="button" class="btn btn-outline-danger btn-sm mt-2" onclick="location.reload()">
                        Try Again
                    </button>
                </div>
            </div>
        `;
    }
}

/**
 * Show payment info message
 */
function showPaymentInfo(message) {
    const container = document.getElementById('paypal-button-container');
    if (container) {
        container.innerHTML = `
            <div class="alert alert-info d-flex align-items-center p-4">
                <i class="fas fa-info-circle fa-2x me-3"></i>
                <div>
                    <h5 class="mb-1">Payment Information</h5>
                    <p class="mb-0">${message}</p>
                </div>
            </div>
        `;
    }
}

/**
 * Create PayPal order for redirect flow
 */
async function createPayPalOrderForRedirect(bookingData) {
    // Prepare booking data for API
    const apiBookingData = {
        venue_id: bookingData.venue_id,
        venue_price: bookingData.venue_price,
        guest_count: bookingData.guest_count,
        booking_date: bookingData.booking_date,
        event_type: bookingData.event_type,
        special_requests: bookingData.special_requests
    };

    console.log('Sending API request for redirect with data:', apiBookingData);

    // Call backend API to create PayPal order
    const response = await fetch('api/paypalCreateOrder.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(apiBookingData)
    });

    console.log('API response status:', response.status);

    const result = await response.json();
    console.log('API response result:', result);

    if (!response.ok || !result.success) {
        throw new Error(result.error || 'Failed to create PayPal order');
    }

    console.log('PayPal order created for redirect, orderID:', result.orderID);

    // Store booking data for later use
    sessionStorage.setItem('paypalBookingData', JSON.stringify({
        ...apiBookingData,
        orderID: result.orderID,
        total_amount: result.total_amount
    }));

    return {
        orderID: result.orderID,
        approveUrl: result.approveUrl,
        total_amount: result.total_amount
    };
}

/**
 * Get booking data from form
 */
function getBookingDataFromForm() {
    console.log('Getting booking data from form');

    // This function should extract booking data from your form
    // Adapt this based on your actual form structure
    const form = document.getElementById('paypalBookingForm') || document.getElementById('bookingForm') || document.querySelector('form');

    console.log('Form found:', !!form, form ? form.id : 'no id');

    if (!form) {
        throw new Error('Booking form not found');
    }

    const formData = new FormData(form);

    const bookingData = {
        venue_id: formData.get('venue_id'),
        venue_price: parseFloat(formData.get('venue_price')) || 0,
        guest_count: parseInt(formData.get('guests')) || 1,
        booking_date: formData.get('date'),
        event_type: formData.get('event_type') || formData.get('custom_event_type'),
        special_requests: formData.get('requests') || ''
    };

    console.log('Extracted booking data:', bookingData);

    return bookingData;
}

/**
 * Initialize PayPal payment flow for a booking
 */
async function initializePayPalPayment() {
    try {
        console.log('Starting PayPal payment initialization');

        // Check if already initialized
        if (window.paypalInitialized) {
            console.warn('PayPal already initialized, skipping');
            return;
        }

        // Get PayPal client ID from environment (you may need to fetch this from your server)
        const response = await fetch('api/getPayPalClientId.php').catch(() => null);

        let clientId = '';
        if (response && response.ok) {
            const data = await response.json();
            clientId = data.clientId || '';
        }

        // Fallback: Use a default client ID or get from environment
        if (!clientId) {
            clientId = 'YOUR_PAYPAL_CLIENT_ID_HERE'; // Replace with your actual client ID
        }

        console.log('PayPal client ID obtained:', clientId ? 'Yes' : 'No');

        // Load PayPal SDK
        await loadPayPalSDK(clientId);
        console.log('PayPal SDK loaded successfully');

        // Get booking data
        const bookingData = getBookingDataFromForm();
        console.log('Booking data extracted:', bookingData);

        // Validate required fields
        if (!bookingData.venue_id || !bookingData.venue_price || !bookingData.booking_date) {
            throw new Error('Missing required booking information');
        }

        // Initialize PayPal buttons
        await initPayPalButtons(bookingData);
        console.log('PayPal buttons initialized successfully');

        window.paypalInitialized = true;

    } catch (error) {
        console.error('Failed to initialize PayPal payment:', error);
        showPaymentError('Failed to load payment options. Please try again later.');
    }
}

// Export functions for use in other scripts
window.PayPalIntegration = {
    initializePayPalPayment,
    getBookingDataFromForm,
    showPaymentLoading,
    hidePaymentLoading,
    showPaymentSuccess,
    showPaymentError,
    showPaymentInfo
};