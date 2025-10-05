// Main JavaScript file

class ResortBookingSystem {
    constructor() {
        this.init();
    }

    init() {
        this.setupGlobalEventListeners();
    }

    setupGlobalEventListeners() {
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const href = this.getAttribute('href');
                if (href === '#') return; // Skip empty anchors
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Loading indicator
        this.setupLoadingIndicator();

        // Form validation
        this.setupFormValidation();
    }

    setupLoadingIndicator() {
        const loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'loadingOverlay';
        loadingOverlay.className = 'spinner-overlay d-none';
        loadingOverlay.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        document.body.appendChild(loadingOverlay);
    }

    showLoading() {
        document.getElementById('loadingOverlay').classList.remove('d-none');
    }

    hideLoading() {
        document.getElementById('loadingOverlay').classList.add('d-none');
    }

    setupFormValidation() {
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });

        // Handle Quick Inquiry form
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(contactForm);
                this.showLoading();
                try {
                    const response = await fetch('api/sendInquiry.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        this.showAlert('Inquiry sent successfully!', 'success');
                        contactForm.reset();
                    } else {
                        this.showAlert('Error: ' + result.message, 'danger');
                    }
                } catch (error) {
                    this.showAlert('Error sending inquiry. Please try again.', 'danger');
                } finally {
                    this.hideLoading();
                }
            });
        }
    }

    showAlert(message, type = 'info') {
        if (window.authManager) {
            window.authManager.showAlert(message, type);
        } else {
            alert(message);
        }
    }

    // Utility functions
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    formatTime(timeString) {
        return new Date(`2000-01-01 ${timeString}`).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    validatePhone(phone) {
        const re = /^[\+]?[1-9][\d]{0,15}$/;
        return re.test(phone.replace(/[\s\-\(\)]/g, ''));
    }

    // API helper functions
    async apiCall(endpoint, method = 'GET', data = null) {
        const config = {
            method,
            headers: {
                'Content-Type': 'application/json',
            }
        };

        if (data) {
            config.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(`api/${endpoint}`, config);
            return await response.json();
        } catch (error) {
            console.error('API call failed:', error);
            throw error;
        }
    }

    // Local storage helpers
    setLocalData(key, data) {
        localStorage.setItem(key, JSON.stringify(data));
    }

    getLocalData(key) {
        const data = localStorage.getItem(key);
        return data ? JSON.parse(data) : null;
    }

    removeLocalData(key) {
        localStorage.removeItem(key);
    }
}

// Utility functions for global use
function showLoading() {
    if (window.resortSystem) {
        window.resortSystem.showLoading();
    }
}

function hideLoading() {
    if (window.resortSystem) {
        window.resortSystem.hideLoading();
    }
}

function formatCurrency(amount) {
    return window.resortSystem ? window.resortSystem.formatCurrency(amount) : `$${amount}`;
}

function formatDate(dateString) {
    return window.resortSystem ? window.resortSystem.formatDate(dateString) : dateString;
}

function formatTime(timeString) {
    return window.resortSystem ? window.resortSystem.formatTime(timeString) : timeString;
}

// Initialize main system when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.resortSystem = new ResortBookingSystem();

    // Add fade-in animation to page content
    document.body.classList.add('fade-in');

    // Trigger hero animations
    setTimeout(() => {
        document.body.classList.add('loaded');
    }, 100);

    // Initialize tooltips and popovers
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
});
