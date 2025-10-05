<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h1 class="card-title mb-0">Manage Users</h1>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <button class="btn btn-primary" onclick="showAddUserModal()">
                            <i class="fas fa-plus"></i> Add New User
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="usersTable">
                                <tr><td colspan="6" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit User Modal -->
<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalTitle">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userForm" enctype="multipart/form-data">
                    <input type="hidden" id="userId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="userName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="userName" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="userEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="userEmail" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="userPhone" class="form-label">Phone</label>
                            <input type="text" class="form-control" id="userPhone" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="userRole" class="form-label">Role</label>
                            <select class="form-select" id="userRole" required>
                                <option value="client">Client</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3" id="passwordField">
                        <label for="userPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="userPassword">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveUser()">Save</button>
            </div>
        </div>
    </div>
</div>