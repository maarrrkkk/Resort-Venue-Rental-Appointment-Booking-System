<?php
$user = $_SESSION['user'] ?? null;
?>
<div class="profile-container min-vh-100 d-flex align-items-center justify-content-center">
    <div class="container">
        <h1 class="mb-4">My Profile</h1>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Account Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <span id="userName"><?php echo htmlspecialchars($user['name'] ?? ''); ?></span></p>
                        <p><strong>Email:</strong> <span id="userEmail"><?php echo htmlspecialchars($user['email'] ?? ''); ?></span></p>
                        <p><strong>Phone:</strong> <span id="userPhone"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></span></p>
                        <p><strong>Role:</strong> <span id="userRole"><?php echo htmlspecialchars($user['role'] ?? ''); ?></span></p>
                        <button class="btn btn-primary" onclick="editProfile()">Edit Profile</button>
                        <button class="btn btn-warning ms-2" onclick="changePassword()">Change Password</button>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>My Bookings</h5>
                    </div>
                    <div class="card-body">
                        <div id="userBookings">
                            <p>Loading bookings...</p>
                        </div>
                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                loadUserBookings();
                            });

                            function loadUserBookings() {
                                const userId = "<?php echo htmlspecialchars($user['id'] ?? ''); ?>";
                                const bookingsContainer = document.getElementById('userBookings');

                                if (!userId) {
                                    bookingsContainer.innerHTML = '<p class="text-muted">Please log in to view your bookings.</p>';
                                    return;
                                }

                                fetch(`api/bookings.php?user_id=${encodeURIComponent(userId)}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.length === 0) {
                                            bookingsContainer.innerHTML = '<p class="text-muted">No bookings found.</p>';
                                            return;
                                        }

                                        let html = '';
                                        data.forEach(booking => {
                                            const statusClass = {
                                                'confirmed': 'success',
                                                'pending': 'warning',
                                                'cancelled': 'danger',
                                                'completed': 'info'
                                            }[booking.status] || 'secondary';

                                            const paymentStatusClass = {
                                                'paid': 'success',
                                                'pending': 'warning',
                                                'failed': 'danger'
                                            }[booking.payment_type] || 'secondary';

                                            html += `
                                                <div class="card mb-3">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <h6 class="card-title">${booking.venue_name}</h6>
                                                                <p class="card-text mb-1">
                                                                    <i class="fas fa-calendar me-2"></i>
                                                                    ${new Date(booking.booking_date).toLocaleDateString()}
                                                                </p>
                                                                <p class="card-text mb-1">
                                                                    <i class="fas fa-users me-2"></i>
                                                                    ${booking.guest_count} guests
                                                                </p>
                                                                <p class="card-text mb-1">
                                                                    <i class="fas fa-tag me-2"></i>
                                                                    ${booking.event_type}
                                                                </p>
                                                                <p class="card-text mb-1">
                                                                    <i class="fas fa-money-bill me-2"></i>
                                                                    ₱${parseFloat(booking.amount).toLocaleString()}
                                                                </p>
                                                            </div>
                                                            <div class="col-md-4 text-md-end">
                                                                <span class="badge bg-${statusClass} mb-2">${booking.status.toUpperCase()}</span><br>
                                                                <span class="badge bg-${paymentStatusClass} mb-2">${booking.payment_type}</span><br>
                                                                <small class="text-muted">Booking ID: ${booking.id}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            `;
                                        });

                                        bookingsContainer.innerHTML = html;
                                    })
                                    .catch(error => {
                                        console.error('Error loading bookings:', error);
                                        bookingsContainer.innerHTML = '<p class="text-danger">Error loading bookings. Please try again later.</p>';
                                    });
                            }
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label>Current Password</label>
                        <input type="password" class="form-control" id="currentPassword" required>
                    </div>
                    <div class="mb-3">
                        <label>New Password</label>
                        <input type="password" class="form-control" id="newPassword" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label>Confirm New Password</label>
                        <input type="password" class="form-control" id="confirmNewPassword" required minlength="6">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="updatePassword()">Update Password</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="profileForm">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" class="form-control" id="editName" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" id="editEmail" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" class="form-control" id="editPhone" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveProfile()">Save</button>
            </div>
        </div>
    </div>
</div>