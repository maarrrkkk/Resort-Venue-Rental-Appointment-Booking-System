<div class="profile-container">
    <h1 class="mb-4">Admin Profile</h1>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Account Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <span id="adminName"></span></p>
                    <p><strong>Email:</strong> <span id="adminEmail"></span></p>
                    <p><strong>Phone:</strong> <span id="adminPhone"></span></p>
                    <p><strong>Role:</strong> <span id="adminRole"></span></p>
                    <button class="btn btn-primary" onclick="editAdminProfile()">Edit Profile</button>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Stats</h5>
                </div>
                <div class="card-body">
                    <p><strong>Total Users:</strong> <span id="totalUsers"></span></p>
                    <p><strong>Total Venues:</strong> <span id="totalVenues"></span></p>
                    <p><strong>Total Bookings:</strong> <span id="totalBookings"></span></p>
                    <p><strong>Total Revenue:</strong> ₱<span id="totalRevenue"></span></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="adminProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="adminProfileForm">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" class="form-control" id="editAdminName" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" class="form-control" id="editAdminEmail" required>
                    </div>
                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" class="form-control" id="editAdminPhone" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveAdminProfile()">Save</button>
            </div>
        </div>
    </div>
</div>