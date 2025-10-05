<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h1 class="card-title mb-0">Manage Venues</h1>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <button class="btn btn-primary" onclick="showAddVenueModal()">
                            <i class="fas fa-plus"></i> Add New Venue
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Capacity</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="venuesTable">
                                <tr><td colspan="7" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlRadio = document.getElementById('imageTypeUrl');
    const uploadRadio = document.getElementById('imageTypeUpload');
    const urlContainer = document.getElementById('urlContainer');
    const uploadContainer = document.getElementById('uploadContainer');

    function toggleImageInputs() {
        if (urlRadio.checked) {
            urlContainer.style.display = 'block';
            uploadContainer.style.display = 'none';
        } else {
            urlContainer.style.display = 'none';
            uploadContainer.style.display = 'block';
        }
    }

    urlRadio.addEventListener('change', toggleImageInputs);
    uploadRadio.addEventListener('change', toggleImageInputs);
});
</script>

<!-- Add/Edit Venue Modal -->
<div class="modal fade" id="venueModal" tabindex="-1" aria-labelledby="venueModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="venueModalTitle">Add Venue</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="venueForm" enctype="multipart/form-data">
                    <input type="hidden" id="venueId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="venueName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="venueName" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="venueCategory" class="form-label">Category</label>
                            <select class="form-select" id="venueCategory" required>
                                <option value="ballroom">Ballroom</option>
                                <option value="outdoor">Outdoor</option>
                                <option value="conference">Conference</option>
                                <option value="garden">Garden</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="venueDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="venueDescription" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="venueCapacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="venueCapacity" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="venuePrice" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" id="venuePrice" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="venueLocation" class="form-label">Location</label>
                            <input type="text" class="form-control" id="venueLocation" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amenities</label>
                        <div id="amenitiesContainer">
                            <div class="input-group mb-2">
                                <input type="text" class="form-control amenity-input" placeholder="Add amenity">
                                <button class="btn btn-outline-secondary" type="button" onclick="addAmenity()">Add</button>
                            </div>
                        </div>
                        <div id="amenitiesList" class="mt-2"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Image</label>
                        <div id="imagesContainer">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="imageType" id="imageTypeUrl" value="url" checked>
                                    <label class="form-check-label" for="imageTypeUrl">
                                        Use Image URL
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="imageType" id="imageTypeUpload" value="upload">
                                    <label class="form-check-label" for="imageTypeUpload">
                                        Upload Image
                                    </label>
                                </div>
                            </div>
                            <div id="urlContainer" class="mb-3">
                                <label for="venueImageUrl" class="form-label">Image URL</label>
                                <input type="url" class="form-control" id="venueImageUrl" placeholder="https://example.com/image.jpg">
                            </div>
                            <div id="uploadContainer" class="mb-3" style="display: none;">
                                <label for="venueImage" class="form-label">Upload Image</label>
                                <input type="file" class="form-control" id="venueImage" accept="image/*">
                                <small class="form-text text-muted">Select an image to upload</small>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="venueAvailability" checked>
                            <label class="form-check-label" for="venueAvailability">
                                Venue is available for booking
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="venueGcashQr" class="form-label">GCash QR Code</label>
                        <input type="file" class="form-control" id="venueGcashQr" accept="image/*">
                        <small class="form-text text-muted">Upload GCash QR code image for this venue</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="saveVenue()">Save</button>
            </div>
        </div>
    </div>
</div>