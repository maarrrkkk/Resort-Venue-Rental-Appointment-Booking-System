<?php

// Check if user is logged in
$user = $_SESSION['user'] ?? null;
if (!$user) {
    header("Location: index.php?page=login");
    exit;
}
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">My Bookings</h1>
            
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Venue</th>
                                    <th>Date</th>
                                    <th>Guests</th>
                                    <th>Event Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="myBookingsTable">
                                <tr>
                                    <td colspan="8" class="text-center">
                                        <div class="d-flex align-items-center justify-content-center p-4">
                                            <div class="spinner-border text-primary me-3" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <span>Loading your bookings...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    fetchMyBookings();
});

function fetchMyBookings() {
    const userId = "<?php echo htmlspecialchars($user['id']); ?>";
    
    fetch(`api/bookings.php?user_id=${encodeURIComponent(userId)}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('myBookingsTable');
            
            if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                            <p class="mb-0">No bookings found.</p>
                            <p class="small">Start by browsing our venues and making your first booking!</p>
                            <a href="index.php?page=venue" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-2"></i>Browse Venues
                            </a>
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = data.map(booking => {
                const statusClass = getStatusBadgeClass(booking.status);
                const paymentStatusClass = getPaymentStatusBadgeClass(booking.payment_type);
                
                return `
                    <tr>
                        <td>
                            <code class="small">${booking.id}</code>
                        </td>
                        <td>
                            <strong>${booking.venue_name}</strong>
                        </td>
                        <td>
                            ${new Date(booking.booking_date).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: 'numeric'
                            })}
                        </td>
                        <td>
                            <i class="fas fa-users me-1"></i>${booking.guest_count}
                        </td>
                        <td>
                            <span class="badge bg-light text-dark">${booking.event_type}</span>
                        </td>
                        <td>
                            <strong>₱${parseFloat(booking.amount).toLocaleString()}</strong>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <span class="badge ${statusClass}">${booking.status.toUpperCase()}</span>
                                <span class="badge ${paymentStatusClass}">${booking.payment_type}</span>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                ${booking.status === 'pending' ? 
                                    `<button class="btn btn-outline-danger" onclick="cancelBooking('${booking.id}')" title="Cancel Booking">
                                        <i class="fas fa-times"></i>
                                    </button>` : ''
                                }
                                <button class="btn btn-outline-info" onclick="viewBookingDetails('${booking.id}')" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        })
        .catch(error => {
            console.error('Error fetching bookings:', error);
            document.getElementById('myBookingsTable').innerHTML = `
                <tr>
                    <td colspan="8" class="text-center text-danger py-4">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3 d-block"></i>
                        <p class="mb-2">Error loading bookings.</p>
                        <button class="btn btn-outline-danger btn-sm" onclick="fetchMyBookings()">
                            <i class="fas fa-redo me-2"></i>Try Again
                        </button>
                    </td>
                </tr>
            `;
        });
}

function getStatusBadgeClass(status) {
    switch (status) {
        case 'pending': return 'bg-warning text-dark';
        case 'confirmed': return 'bg-success';
        case 'completed': return 'bg-primary';
        case 'cancelled': return 'bg-secondary';
        case 'suspended': return 'bg-danger';
        default: return 'bg-light text-dark';
    }
}

function getPaymentStatusBadgeClass(paymentType) {
    switch (paymentType) {
        case 'paid': return 'bg-success';
        case 'pending': return 'bg-warning text-dark';
        case 'failed': return 'bg-danger';
        case 'refunded': return 'bg-info';
        default: return 'bg-light text-dark';
    }
}

function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking?\n\nThis action cannot be undone.')) {
        // Show loading state
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        fetch('api/cancelBooking.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ id: bookingId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showAlert('Booking cancelled successfully!', 'success');
                // Refresh the bookings list
                fetchMyBookings();
            } else {
                throw new Error(data.error || 'Failed to cancel booking');
            }
        })
        .catch(error => {
            console.error('Error cancelling booking:', error);
            showAlert('Error cancelling booking: ' + error.message, 'danger');
        })
        .finally(() => {
            // Restore button state
            button.innerHTML = originalContent;
            button.disabled = false;
        });
    }
}

function viewBookingDetails(bookingId) {
    // For now, just show an alert with booking ID
    // In a real implementation, you might open a modal or navigate to a details page
    showAlert(`Booking Details\n\nBooking ID: ${bookingId}\n\nContact support for detailed information about this booking.`, 'info');
}

function showAlert(message, type) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>

<style>
.table th {
    border-top: none;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.875rem;
    letter-spacing: 0.5px;
}

.table td {
    vertical-align: middle;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table-responsive {
    border-radius: 0.375rem;
}

code {
    background-color: #f8f9fa;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.8rem;
}

@media (max-width: 768px) {
    .container {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    .table {
        font-size: 0.875rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.2rem 0.4rem;
        font-size: 0.75rem;
    }
}
</style>