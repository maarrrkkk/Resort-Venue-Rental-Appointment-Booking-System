// assets/js/auth.js
class AuthManager {
    constructor() {
        // Use dynamic API path based on current location
        this.baseURL = this.getBaseURL();
        this.init();
    }

    getBaseURL() {
        // Get the current path and construct base URL dynamically
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

    init() {
        this.checkAuthStatus();
        this.setupEventListeners();
        this.setupMobileMenu();

        // Auto-load dashboard stats if adminDashboard element exists
        if (document.getElementById('adminDashboard')) {
            setTimeout(() => this.loadDashboard(), 150);
        }
    }

    setupEventListeners() {
        const loginForm = document.getElementById('loginForm');
        if (loginForm) loginForm.addEventListener('submit', (e) => this.handleLogin(e));

        const registerForm = document.getElementById('registerForm');
        if (registerForm) registerForm.addEventListener('submit', (e) => this.handleRegister(e));

        const verifyForm = document.getElementById('verifyForm');
        if (verifyForm) verifyForm.addEventListener('submit', (e) => this.handleVerifyEmail(e));

        const resendCodeBtn = document.getElementById('resendCode');
        if (resendCodeBtn) resendCodeBtn.addEventListener('click', (e) => this.resendVerificationCode(e));

        const contactForm = document.getElementById('contactForm');
        if (contactForm) contactForm.addEventListener('submit', (e) => this.handleContact(e));

        const forgotPasswordForm = document.getElementById('forgotPasswordForm');
        if (forgotPasswordForm) forgotPasswordForm.addEventListener('submit', (e) => this.handleForgotPassword(e));

        document.querySelectorAll('.logoutBtn').forEach(btn => {
            btn.addEventListener('click', () => this.logout());
        });
    }

    setupMobileMenu() {
        const hamburger = document.querySelector('.hamburger');
        const nav = document.querySelector('.admin-nav');

        if (hamburger && nav) {
            hamburger.addEventListener('click', () => {
                nav.classList.toggle('active');
                hamburger.classList.toggle('active');
            });

            // Close mobile menu when clicking on a link
            const navLinks = document.querySelectorAll('.admin-nav ul li a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    nav.classList.remove('active');
                    hamburger.classList.remove('active');
                });
            });

            // Close mobile menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!nav.contains(e.target) && !hamburger.contains(e.target)) {
                    nav.classList.remove('active');
                    hamburger.classList.remove('active');
                }
            });
        }
    }

    checkAuthStatus() {
        try {
            const user = localStorage.getItem('resort_user');
            if (user) {
                this.updateNavbar(JSON.parse(user));
            }
        } catch (err) {
            console.error('Auth check error:', err);
            localStorage.removeItem('resort_user');
        }
    }

    updateNavbar(user) {
        const userMenu = document.getElementById('userMenu');
        const authButtons = document.getElementById('authButtons');
        const userName = document.getElementById('userName');
        const bookNowLink = document.querySelector('a[href*="page=booking"]');

        if (user && userMenu && authButtons && userName) {
            userName.textContent = user.name;
            userMenu.classList.remove('d-none');
            authButtons.style.display = 'none';

            // Enable Book Now link
            if (bookNowLink) {
                bookNowLink.style.pointerEvents = 'auto';
                bookNowLink.style.opacity = '1';
            }

            const dashboardLink = userMenu.querySelector('a[href="dashboard.html"]');
            if (dashboardLink) {
                if (user.role === 'admin') {
                    dashboardLink.href = 'admin/index.php?page=dashboard';
                    dashboardLink.innerHTML = '<i class="fas fa-cog me-2"></i>Admin Dashboard';
                } else {
                    dashboardLink.href = 'index.php?page=dashboard';
                    dashboardLink.innerHTML = '<i class="fas fa-calendar me-2"></i>My Bookings';
                }
            }
        } else {
            // Redirect Book Now link to login if not logged in
            if (bookNowLink) {
                bookNowLink.href = 'index.php?page=login';
                bookNowLink.title = 'Please login to book';
            }
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : 'Signing In...';

        try {
            if (submitBtn) { submitBtn.textContent = 'Signing In...'; submitBtn.disabled = true; }

            const res = await fetch(this.baseURL + 'login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email, password }),
                credentials: 'same-origin'
            });

            const result = await res.json();

            if (result.success) {
                localStorage.setItem('resort_user', JSON.stringify(result.user));
                this.showAlert('Login successful!', 'success');
                setTimeout(() => {
                    if (result.user.role === 'admin') {
                        window.location.href = './admin/index.php?page=dashboard';
                    } else {
                        window.location.href = 'index.php?page=home';
                    }
                }, 700);
            } else {
                this.showAlert(result.message || 'Login failed', 'danger');
            }
        } catch (err) {
            console.error('Login error:', err);
            this.showAlert('Connection error. Please try again.', 'danger');
        } finally {
            if (submitBtn) { submitBtn.textContent = originalText; submitBtn.disabled = false; }
        }
    }

    async handleRegister(e) {
        e.preventDefault();
        const formData = {
            name: document.getElementById('name').value,
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            password: document.getElementById('password').value,
            confirmPassword: document.getElementById('confirmPassword').value
        };
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : 'Creating Account...';

        if (formData.password !== formData.confirmPassword) {
            this.showAlert('Passwords do not match', 'danger');
            return;
        }

        try {
            if (submitBtn) { submitBtn.textContent = 'Creating Account...'; submitBtn.disabled = true; }

            const res = await fetch(this.baseURL + 'register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData),
                credentials: 'same-origin'
            });

            const result = await res.json();
            if (result.success) {
                if (result.step === 'verify') {
                    // Show verification step
                    document.getElementById('registerForm').classList.add('d-none');
                    document.getElementById('verificationStep').classList.remove('d-none');
                    document.getElementById('verificationMessage').textContent = result.message;
                    this.showAlert('Verification code sent to your email!', 'success');
                } else {
                    // Direct registration (fallback)
                    localStorage.setItem('resort_user', JSON.stringify(result.user));
                    this.showAlert('Registration successful!', 'success');
                    setTimeout(() => window.location.href = 'index.php', 800);
                }
            } else {
                this.showAlert(result.message || 'Registration failed', 'danger');
            }
        } catch (err) {
            console.error('Register error:', err);
            this.showAlert('Connection error. Please try again.', 'danger');
        } finally {
            if (submitBtn) { submitBtn.textContent = originalText; submitBtn.disabled = false; }
        }
    }

    async handleVerifyEmail(e) {
        e.preventDefault();
        const code = document.getElementById('verificationCode').value;
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : 'Verifying...';

        try {
            if (submitBtn) { submitBtn.textContent = 'Verifying...'; submitBtn.disabled = true; }

            const res = await fetch(this.baseURL + 'verifyEmail.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ code }),
                credentials: 'same-origin'
            });

            const result = await res.json();
            if (result.success) {
                localStorage.setItem('resort_user', JSON.stringify(result.user));
                this.showAlert('Email verified and registration completed!', 'success');
                setTimeout(() => window.location.href = 'index.php', 800);
            } else {
                this.showAlert(result.message || 'Verification failed', 'danger');
            }
        } catch (err) {
            console.error('Verify error:', err);
            this.showAlert('Connection error. Please try again.', 'danger');
        } finally {
            if (submitBtn) { submitBtn.textContent = originalText; submitBtn.disabled = false; }
        }
    }

    async resendVerificationCode(e) {
        e.preventDefault();
        const btn = e.target;
        const originalText = btn.textContent;

        try {
            btn.textContent = 'Sending...';
            btn.disabled = true;

            // Resubmit the registration form data to resend code
            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                password: document.getElementById('password').value
            };

            const res = await fetch(this.baseURL + 'register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData),
                credentials: 'same-origin'
            });

            const result = await res.json();
            if (result.success && result.step === 'verify') {
                this.showAlert('Verification code resent!', 'success');
            } else {
                this.showAlert('Failed to resend code', 'danger');
            }
        } catch (err) {
            console.error('Resend error:', err);
            this.showAlert('Connection error. Please try again.', 'danger');
        } finally {
            btn.textContent = originalText;
            btn.disabled = false;
        }
    }

    async handleForgotPassword(e) {
        e.preventDefault();
        const email = document.getElementById('forgotEmail').value;
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : 'Sending...';

        try {
            if (submitBtn) { submitBtn.textContent = 'Sending...'; submitBtn.disabled = true; }

            const res = await fetch(this.baseURL + 'forgotPassword.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email }),
                credentials: 'same-origin'
            });

            const result = await res.json();

            if (result.success) {
                this.showAlert('Password reset link sent to your email!', 'success');
                bootstrap.Modal.getInstance(document.getElementById('forgotPasswordModal')).hide();
                e.target.reset();
            } else {
                this.showAlert(result.message || 'Failed to send reset link', 'danger');
            }
        } catch (err) {
            console.error('Forgot password error:', err);
            this.showAlert('Connection error. Please try again.', 'danger');
        } finally {
            if (submitBtn) { submitBtn.textContent = originalText; submitBtn.disabled = false; }
        }
    }

    async handleContact(e) {
        e.preventDefault();
        const submitBtn = e.target.querySelector('button[type="submit"]');
        const originalText = submitBtn ? submitBtn.textContent : 'Sending...';

        try {
            if (submitBtn) { submitBtn.textContent = 'Sending...'; submitBtn.disabled = true; }
            await new Promise(r => setTimeout(r, 1000)); // simulate
            this.showAlert('Message sent successfully! We will get back to you soon.', 'success');
            e.target.reset();
        } catch (err) {
            console.error('Contact error:', err);
            this.showAlert('Failed to send message. Please try again.', 'danger');
        } finally {
            if (submitBtn) { submitBtn.textContent = originalText; submitBtn.disabled = false; }
        }
    }

    async logout() {
        try {
            await fetch(this.baseURL + 'logout.php', { credentials: 'same-origin' });
        } catch (err) {
            console.error('Logout API error:', err);
        }
        localStorage.removeItem('resort_user');
        this.showAlert('Logged out successfully', 'success');
        const redirectPath = window.location.pathname.includes('/admin/') ? '../index.php?page=home' : 'index.php?page=home';
        setTimeout(() => window.location.href = redirectPath, 700);
    }

    showAlert(message, type = 'info') {
        document.querySelectorAll('.alert-floating').forEach(a => a.remove());
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-floating`;
        alertDiv.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;min-width:300px;';
        alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }

    isAuthenticated() {
        return localStorage.getItem('resort_user') !== null;
    }

    getCurrentUser() {
        const u = localStorage.getItem('resort_user');
        return u ? JSON.parse(u) : null;
    }

    requireAuth() {
        if (!this.isAuthenticated()) {
            this.showAlert('Please login to access this page', 'warning');
            setTimeout(() => window.location.href = 'index.php?page=login', 900);
            return false;
        }
        return true;
    }

    requireAdmin() {
        const u = this.getCurrentUser();
        if (!u || u.role !== 'admin') {
            this.showAlert('Admin access required', 'danger');
            setTimeout(() => window.location.href = 'index.php?page=home', 900);
            return false;
        }
        return true;
    }

    // ------------- DASHBOARD -------------
    async loadDashboard() {
        if (!this.requireAdmin()) return;

        try {
            const res = await fetch(this.baseURL + 'dashboard.php', { credentials: 'same-origin' });
            if (!res.ok) {
                let errText = '';
                try { const j = await res.json(); errText = j.error || JSON.stringify(j); } catch(e){ errText = await res.text(); }
                throw new Error(`API returned ${res.status}: ${errText}`);
            }
            const data = await res.json();

            // populate UI
            document.getElementById('totalUsers').textContent = data.totalUsers ?? 0;
            document.getElementById('totalVenues').textContent = data.totalVenues ?? 0;
            document.getElementById('totalBookings').textContent = data.totalBookings ?? 0;
            document.getElementById('totalRevenue').textContent = '₱' + parseFloat(data.totalRevenue ?? 0).toFixed(2);

            const container = document.getElementById('recentBookingsContainer');
            if (!container) return;
            if (Array.isArray(data.recentBookings) && data.recentBookings.length > 0) {
                container.innerHTML = data.recentBookings.map(b => `
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span>Booking #${b.id}</span>
                            <span class="badge ${this.getStatusBadgeClass(b.status ?? 'pending')}">${this.capitalize(b.status ?? 'pending')}</span>
                        </div>
                        <div class="card-body">
                            <p><strong>User:</strong> ${this.escapeHtml(b.user_name)}</p>
                            <p><strong>Venue:</strong> ${this.escapeHtml(b.venue_name)}</p>
                            <p><strong>Amount:</strong> ₱${parseFloat(b.amount ?? 0).toFixed(2)}</p>
                            <p><strong>Payment:</strong> ${b.payment_type || 'N/A'}</p>
                            <p><strong>Payment Reference:</strong> ${b.payment_reference || 'N/A'}</p>
                            <p><strong>Receipt:</strong> ${b.gcash_receipt ? `<a href="${b.gcash_receipt}" target="_blank"><img src="${b.gcash_receipt}" alt="GCash Receipt" style="max-width: 100px; max-height: 100px;" class="img-thumbnail"></a>` : 'No receipt'}</p>
                            <p><strong>Booking Date:</strong> ${b.booking_date || 'N/A'}</p>
                            <p><strong>Guests:</strong> ${b.guest_count || 'N/A'}</p>
                            <p><strong>Event Type:</strong> ${b.event_type || 'N/A'}</p>
                            <p><strong>Special Requests:</strong> ${b.special_requests || 'None'}</p>
                            <p><strong>Created:</strong> ${this.formatDate(b.created_at ?? '')}</p>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="updateStatus('${b.id}', '${b.status || 'pending'}')" title="Edit Status">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteBooking('${b.id}')" title="Delete Booking">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<div class="text-center py-3">No recent bookings.</div>';
            }

        } catch (err) {
            console.error('Error loading dashboard:', err);
            this.showAlert('Failed to load dashboard data. Please check server or open devtools (Network).', 'danger');
        }
    }

    escapeHtml(text = '') {
        const d = document.createElement('div');
        d.innerText = String(text);
        return d.innerHTML;
    }
    capitalize(s = '') { return String(s).charAt(0).toUpperCase() + String(s).slice(1); }
    formatDate(d = '') { if(!d) return ''; try { return new Date(d).toLocaleString(); } catch(e){ return d; } }

    getStatusBadgeClass(status) {
        const classes = {
            'pending': 'bg-warning',
            'confirmed': 'bg-success',
            'completed': 'bg-primary',
            'cancelled': 'bg-secondary',
            'suspended': 'bg-danger'
        };
        return classes[status] || 'bg-light text-dark';
    }
}

// Global helper
function logout() { if (window.authManager) window.authManager.logout(); }

function updateStatus(id, currentStatus) {
    document.getElementById('bookingId').value = id;
    document.getElementById('bookingStatus').value = currentStatus;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}

function updateBookingStatus() {
    const id = document.getElementById('bookingId').value;
    const status = document.getElementById('bookingStatus').value;

    fetch(window.authManager.baseURL + 'bookings.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, status })
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
            if (window.authManager) {
                window.authManager.loadDashboard();
                window.authManager.showAlert('Booking status updated successfully.', 'success');
            }
        } else {
            throw new Error(result.message || 'Update failed');
        }
    })
    .catch(error => {
        console.error('Error updating booking status:', error);
        if (window.authManager) {
            window.authManager.showAlert('Failed to update booking status.', 'danger');
        }
    });
}

function deleteBooking(id) {
    if (!confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
        return;
    }

    fetch(window.authManager.baseURL + `bookings.php?id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            if (window.authManager) {
                window.authManager.loadDashboard();
                window.authManager.showAlert('Booking deleted successfully.', 'success');
            }
        } else {
            throw new Error(result.message || 'Delete failed');
        }
    })
    .catch(error => {
        console.error('Error deleting booking:', error);
        if (window.authManager) {
            window.authManager.showAlert('Failed to delete booking.', 'danger');
        }
    });
}

// Admin functions
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('venuesContainer')) {
        loadVenues();
    } else if (document.getElementById('usersContainer')) {
        loadUsers();
    } else if (document.getElementById('bookingsTable')) {
        loadBookings();
    }
});

// Venues functions
async function loadVenues() {
    try {
        const response = await fetch(window.authManager.baseURL + 'venues.php');
        const venues = await response.json();
        const container = document.getElementById('venuesContainer');
        if (!container) return;
        if (venues.length > 0) {
            container.innerHTML = venues.map(venue => `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">${escapeHtml(venue.name)}</h5>
                            <span class="badge ${venue.availability ? 'bg-success' : 'bg-secondary'}">${venue.availability ? 'Available' : 'Unavailable'}</span>
                        </div>
                        <div class="card-body">
                            <p><strong>ID:</strong> ${venue.id}</p>
                            <p><strong>Category:</strong> ${venue.category}</p>
                            <p><strong>Capacity:</strong> ${venue.capacity}</p>
                            <p><strong>Price:</strong> ₱${parseFloat(venue.price).toFixed(2)}</p>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-sm btn-primary me-1" onclick="editVenue('${venue.id}')">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteVenue('${venue.id}')">Delete</button>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="col-12 text-center py-3">No venues found.</div>';
        }
    } catch (error) {
        console.error('Error loading venues:', error);
        if (window.authManager) {
            window.authManager.showAlert('Failed to load venues.', 'danger');
        }
    }
}

function showAddVenueModal() {
    document.getElementById('venueId').value = '';
    document.getElementById('venueForm').reset();
    document.getElementById('venueModalTitle').textContent = 'Add Venue';
    document.getElementById('amenitiesList').innerHTML = '';
    document.getElementById('venueImageUrl').value = '';
    document.getElementById('venueAvailability').checked = true;
    new bootstrap.Modal(document.getElementById('venueModal')).show();
}

function addAmenity() {
    const input = document.querySelector('.amenity-input');
    const value = input.value.trim();
    if (value) {
        const badge = document.createElement('span');
        badge.className = 'badge bg-primary me-1 mb-1';
        badge.textContent = value;
        badge.onclick = function() { this.remove(); };
        document.getElementById('amenitiesList').appendChild(badge);
        input.value = '';
    }
}

function editVenue(id) {
    fetch(window.authManager.baseURL + `venues.php?id=${id}`)
        .then(response => response.json())
        .then(venue => {
            document.getElementById('venueId').value = venue.id;
            document.getElementById('venueName').value = venue.name;
            document.getElementById('venueDescription').value = venue.description;
            document.getElementById('venueCapacity').value = venue.capacity;
            document.getElementById('venuePrice').value = venue.price;
            document.getElementById('venueCategory').value = venue.category;
            document.getElementById('venueLocation').value = venue.location;
            document.getElementById('venueAvailability').checked = venue.availability;
            document.getElementById('venueModalTitle').textContent = 'Edit Venue';

            // Populate amenities
            const amenitiesList = document.getElementById('amenitiesList');
            amenitiesList.innerHTML = '';
            if (venue.amenities && Array.isArray(venue.amenities)) {
                venue.amenities.forEach(amenity => {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-primary me-1 mb-1';
                    badge.textContent = amenity;
                    badge.onclick = function() { this.remove(); };
                    amenitiesList.appendChild(badge);
                });
            }

            // Populate image
            if (venue.images && Array.isArray(venue.images) && venue.images.length > 0) {
                const image = venue.images[0];
                if (image.startsWith('assets/uploads/')) {
                    document.getElementById('imageTypeUpload').checked = true;
                    document.getElementById('urlContainer').style.display = 'none';
                    document.getElementById('uploadContainer').style.display = 'block';
                    // Note: uploaded image can't be pre-filled in file input for security
                } else {
                    document.getElementById('imageTypeUrl').checked = true;
                    document.getElementById('urlContainer').style.display = 'block';
                    document.getElementById('uploadContainer').style.display = 'none';
                    document.getElementById('venueImageUrl').value = image;
                }
            } else {
                document.getElementById('imageTypeUrl').checked = true;
                document.getElementById('urlContainer').style.display = 'block';
                document.getElementById('uploadContainer').style.display = 'none';
            }

            new bootstrap.Modal(document.getElementById('venueModal')).show();
        })
        .catch(error => console.error('Error fetching venue:', error));
}

async function saveVenue() {
    const form = document.getElementById('venueForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Collect amenities
    const amenities = Array.from(document.querySelectorAll('#amenitiesList .badge')).map(badge => badge.textContent);

    // Collect image
    let imageUrl = '';
    const imageType = document.querySelector('input[name="imageType"]:checked').value;
    if (imageType === 'url') {
        imageUrl = document.getElementById('venueImageUrl').value.trim();
    }

    const formData = new FormData();
    formData.append('id', document.getElementById('venueId').value);
    formData.append('name', document.getElementById('venueName').value);
    formData.append('description', document.getElementById('venueDescription').value);
    formData.append('capacity', parseInt(document.getElementById('venueCapacity').value));
    formData.append('price', parseFloat(document.getElementById('venuePrice').value));
    formData.append('category', document.getElementById('venueCategory').value);
    formData.append('location', document.getElementById('venueLocation').value);
    formData.append('amenities', JSON.stringify(amenities));
    formData.append('availability', document.getElementById('venueAvailability').checked ? 1 : 0);
    formData.append('image_url', imageUrl);

    // Add uploaded file to formData
    if (imageType === 'upload') {
        const uploadedImage = document.getElementById('venueImage').files[0];
        if (uploadedImage) {
            formData.append('image', uploadedImage);
        }
    }

    const qrFile = document.getElementById('venueGcashQr').files[0];
    if (qrFile) {
        formData.append('gcash_qr', qrFile);
    }

    try {
        const response = await fetch(window.authManager.baseURL + 'venues.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('venueModal')).hide();
            loadVenues();
            if (window.authManager) {
                window.authManager.showAlert('Venue saved successfully.', 'success');
            }
        } else {
            throw new Error(result.error || 'Save failed');
        }
    } catch (error) {
        console.error('Error saving venue:', error);
        if (window.authManager) {
            window.authManager.showAlert('Failed to save venue.', 'danger');
        }
    }
}

async function deleteVenue(id) {
    if (!confirm('Are you sure you want to delete this venue?')) return;

    try {
        const response = await fetch(window.authManager.baseURL + `venues.php?id=${id}`, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
            loadVenues();
            if (window.authManager) {
                window.authManager.showAlert('Venue deleted successfully.', 'success');
            }
        } else {
            throw new Error(result.error || 'Delete failed');
        }
    } catch (error) {
        console.error('Error deleting venue:', error);
        if (window.authManager) {
            window.authManager.showAlert('Failed to delete venue.', 'danger');
        }
    }
}

// Users functions
async function loadUsers() {
    try {
        const response = await fetch(window.authManager.baseURL + 'users.php');
        const users = await response.json();
        const container = document.getElementById('usersContainer');
        if (!container) return;
        if (users.length > 0) {
            container.innerHTML = users.map(user => `
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">${escapeHtml(user.name)}</h5>
                            <span class="badge ${user.role === 'admin' ? 'bg-danger' : 'bg-info'}">${capitalize(user.role)}</span>
                        </div>
                        <div class="card-body">
                            <p><strong>ID:</strong> ${user.id}</p>
                            <p><strong>Email:</strong> ${escapeHtml(user.email)}</p>
                            <p><strong>Phone:</strong> ${escapeHtml(user.phone)}</p>
                        </div>
                        <div class="card-footer">
                            <button class="btn btn-sm btn-primary me-1" onclick="editUser('${user.id}')">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser('${user.id}')">Delete</button>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="col-12 text-center py-3">No users found.</div>';
        }
    } catch (error) {
        console.error('Error loading users:', error);
        if (window.authManager) {
            window.authManager.showAlert('Failed to load users.', 'danger');
        }
    }
}

function showAddUserModal() {
    document.getElementById('userId').value = '';
    document.getElementById('userForm').reset();
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('passwordField').style.display = 'block';
    document.getElementById('userPassword').required = true;
    new bootstrap.Modal(document.getElementById('userModal')).show();
}

function editUser(id) {
    fetch(window.authManager.baseURL + `users.php?id=${id}`)
        .then(response => response.json())
        .then(user => {
            document.getElementById('userId').value = user.id;
            document.getElementById('userName').value = user.name;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userPhone').value = user.phone;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('passwordField').style.display = 'none';
            document.getElementById('userPassword').required = false;
            new bootstrap.Modal(document.getElementById('userModal')).show();
        })
        .catch(error => console.error('Error fetching user:', error));
}

async function saveUser() {
    const form = document.getElementById('userForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const data = {
        id: document.getElementById('userId').value,
        name: document.getElementById('userName').value,
        email: document.getElementById('userEmail').value,
        phone: document.getElementById('userPhone').value,
        role: document.getElementById('userRole').value,
        password: document.getElementById('userPassword').value
    };

    try {
        const method = data.id ? 'PUT' : 'POST';
        const response = await fetch(window.authManager.baseURL + 'users.php', {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('userModal')).hide();
            loadUsers();
            if (window.authManager) {
                window.authManager.showAlert('User saved successfully.', 'success');
            }
        } else {
            throw new Error(result.error || 'Save failed');
        }
    } catch (error) {
        console.error('Error saving user:', error);
        if (window.authManager) {
            window.authManager.showAlert('Failed to save user.', 'danger');
        }
    }
}

async function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this user?')) return;

    try {
        const response = await fetch(window.authManager.baseURL + `users.php?id=${id}`, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
            loadUsers();
            if (window.authManager) {
                window.authManager.showAlert('User deleted successfully.', 'success');
            }
        } else {
            throw new Error(result.error || 'Delete failed');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        if (window.authManager) {
            window.authManager.showAlert('Failed to delete user.', 'danger');
        }
    }
}


// Helper functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Profile functions
async function loadUserProfile() {
    const user = window.authManager.getCurrentUser();
    if (!user) return;

    console.log('Loading user profile for user:', user.name);

    const userNameEl = document.getElementById('userName');
    const userEmailEl = document.getElementById('userEmail');
    const userPhoneEl = document.getElementById('userPhone');
    const userRoleEl = document.getElementById('userRole');

    console.log('Profile elements found:', {
        userName: !!userNameEl,
        userEmail: !!userEmailEl,
        userPhone: !!userPhoneEl,
        userRole: !!userRoleEl
    });

    if (userNameEl) userNameEl.textContent = user.name;
    if (userEmailEl) userEmailEl.textContent = user.email;
    if (userPhoneEl) userPhoneEl.textContent = user.phone;
    if (userRoleEl) userRoleEl.textContent = user.role;

    // Load user bookings
    try {
        const response = await fetch(window.authManager.baseURL + 'bookings.php?user_id=' + user.id);
        const bookings = await response.json();
        const container = document.getElementById('userBookings');
        console.log('userBookings container found:', !!container);

        if (container) {
            if (bookings.length > 0) {
                container.innerHTML = '<div class="accordion" id="bookingsAccordion">' +
                    bookings.map((b, index) => `
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading${index}">
                                <button class="accordion-button ${index > 0 ? 'collapsed' : ''}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse${index}" aria-expanded="${index === 0 ? 'true' : 'false'}" aria-controls="collapse${index}">
                                    <strong>${b.venue_name}</strong> - ${b.booking_date || 'N/A'} - ₱${parseFloat(b.amount ?? 0).toFixed(2)} - <span class="badge bg-${getStatusColor(b.status)}">${b.status}</span>
                                </button>
                            </h2>
                            <div id="collapse${index}" class="accordion-collapse collapse ${index === 0 ? 'show' : ''}" aria-labelledby="heading${index}" data-bs-parent="#bookingsAccordion">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Booking ID:</strong> ${b.id}</p>
                                            <p><strong>Venue:</strong> ${b.venue_name}</p>
                                            <p><strong>Booking Date:</strong> ${b.booking_date || 'N/A'}</p>
                                            <p><strong>Guests:</strong> ${b.guest_count || 'N/A'}</p>
                                            <p><strong>Event Type:</strong> ${b.event_type || 'N/A'}</p>
                                            <p><strong>Amount:</strong> ₱${parseFloat(b.amount ?? 0).toFixed(2)}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Payment Type:</strong> ${b.payment_type || 'N/A'}</p>
                                            <p><strong>Payment Reference:</strong> ${b.payment_reference || 'N/A'}</p>
                                            <p><strong>Status:</strong> <span class="badge bg-${getStatusColor(b.status)}">${b.status}</span></p>
                                            <p><strong>Special Requests:</strong> ${b.special_requests || 'None'}</p>
                                            <p><strong>Receipt:</strong> ${b.gcash_receipt ? `<a href="${b.gcash_receipt}" target="_blank" class="btn btn-sm btn-outline-primary">View Receipt</a>` : 'No receipt'}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('') + '</div>';
            } else {
                container.innerHTML = '<p>No bookings found.</p>';
            }
        }
    } catch (error) {
        console.error('Error loading user bookings:', error);
        const errorContainer = document.getElementById('userBookings');
        if (errorContainer) {
            errorContainer.innerHTML = '<p>Error loading bookings.</p>';
        }
    }
}

function editProfile() {
    const user = window.authManager.getCurrentUser();
    document.getElementById('editName').value = user.name;
    document.getElementById('editEmail').value = user.email;
    document.getElementById('editPhone').value = user.phone;
    new bootstrap.Modal(document.getElementById('profileModal')).show();
}

async function saveProfile() {
    const form = document.getElementById('profileForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const user = window.authManager.getCurrentUser();
    const data = {
        id: user.id,
        name: document.getElementById('editName').value,
        email: document.getElementById('editEmail').value,
        phone: document.getElementById('editPhone').value
    };

    try {
        const response = await fetch(window.authManager.baseURL + 'users.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            // Update localStorage
            const updatedUser = { ...user, ...data };
            localStorage.setItem('resort_user', JSON.stringify(updatedUser));
            window.authManager.updateNavbar(updatedUser);
            bootstrap.Modal.getInstance(document.getElementById('profileModal')).hide();
            loadUserProfile();
            window.authManager.showAlert('Profile updated successfully.', 'success');
        } else {
            throw new Error(result.error || 'Update failed');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        window.authManager.showAlert('Failed to update profile.', 'danger');
    }
}

function getStatusColor(status) {
    const colors = {
        pending: 'warning',
        confirmed: 'info',
        cancelled: 'danger',
        completed: 'success',
        suspended: 'secondary'
    };
    return colors[status] || 'secondary';
}

// Admin profile functions
async function loadAdminProfile() {
    const user = window.authManager.getCurrentUser();
    if (!user) return;

    document.getElementById('adminName').textContent = user.name;
    document.getElementById('adminEmail').textContent = user.email;
    document.getElementById('adminPhone').textContent = user.phone;
    document.getElementById('adminRole').textContent = user.role;

    // Load quick stats
    try {
        const response = await fetch(window.authManager.baseURL + 'dashboard.php');
        const data = await response.json();
        document.getElementById('totalUsers').textContent = data.totalUsers;
        document.getElementById('totalVenues').textContent = data.totalVenues;
        document.getElementById('totalBookings').textContent = data.totalBookings;
        document.getElementById('totalRevenue').textContent = data.totalRevenue;
    } catch (error) {
        console.error('Error loading admin stats:', error);
    }
}

function editAdminProfile() {
    const user = window.authManager.getCurrentUser();
    document.getElementById('editAdminName').value = user.name;
    document.getElementById('editAdminEmail').value = user.email;
    document.getElementById('editAdminPhone').value = user.phone;
    new bootstrap.Modal(document.getElementById('adminProfileModal')).show();
}

function changePassword() {
    // Reset form
    document.getElementById('changePasswordForm').reset();
    new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
}

async function updatePassword() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmNewPassword').value;

    // Validation
    if (!currentPassword || !newPassword || !confirmPassword) {
        window.authManager.showAlert('All password fields are required.', 'danger');
        return;
    }

    if (newPassword !== confirmPassword) {
        window.authManager.showAlert('New passwords do not match.', 'danger');
        return;
    }

    if (newPassword.length < 6) {
        window.authManager.showAlert('New password must be at least 6 characters.', 'danger');
        return;
    }

    if (currentPassword === newPassword) {
        window.authManager.showAlert('New password must be different from current password.', 'danger');
        return;
    }

    const user = window.authManager.getCurrentUser();
    const data = {
        id: user.id,
        currentPassword: currentPassword,
        newPassword: newPassword
    };

    try {
        const response = await fetch(window.authManager.baseURL + 'changePassword.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data),
            credentials: 'same-origin'
        });

        const result = await response.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
            window.authManager.showAlert('Password updated successfully.', 'success');
        } else {
            window.authManager.showAlert(result.message || 'Failed to update password.', 'danger');
        }
    } catch (error) {
        console.error('Error updating password:', error);
        window.authManager.showAlert('Connection error. Please try again.', 'danger');
    }
}

async function saveAdminProfile() {
    const form = document.getElementById('adminProfileForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const user = window.authManager.getCurrentUser();
    const data = {
        id: user.id,
        name: document.getElementById('editAdminName').value,
        email: document.getElementById('editAdminEmail').value,
        phone: document.getElementById('editAdminPhone').value
    };

    try {
        const response = await fetch(window.authManager.baseURL + 'users.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        if (result.success) {
            const updatedUser = { ...user, ...data };
            localStorage.setItem('resort_user', JSON.stringify(updatedUser));
            window.authManager.updateNavbar(updatedUser);
            bootstrap.Modal.getInstance(document.getElementById('adminProfileModal')).hide();
            loadAdminProfile();
            window.authManager.showAlert('Profile updated successfully.', 'success');
        } else {
            throw new Error(result.error || 'Update failed');
        }
    } catch (error) {
        console.error('Error updating admin profile:', error);
        window.authManager.showAlert('Failed to update profile.', 'danger');
    }
}

// Init - consolidated DOMContentLoaded listeners
document.addEventListener('DOMContentLoaded', function() {
    // Initialize AuthManager first
    window.authManager = new AuthManager();

    // Load profile if on profile page
    if (document.getElementById('userName')) {
        loadUserProfile();
    }
    if (document.getElementById('adminName')) {
        loadAdminProfile();
    }

    // Admin functions - load venues/users only after authManager is initialized
    if (document.getElementById('venuesContainer')) {
        loadVenues();
    } else if (document.getElementById('usersContainer')) {
        loadUsers();
    }
});


