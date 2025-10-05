<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php?page=login");
    exit;
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings - Resort Venue Booking System</title>
    <link rel="stylesheet" href="../assets/css/client.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">My Bookings</h1>
        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Venue</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="myBookingsTable">
                            <tr><td colspan="6" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            fetchMyBookings();
        });

        function fetchMyBookings() {
            fetch('../api/bookings.php?user_id=<?= $user['id'] ?>')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('myBookingsTable');
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="6" class="text-center">No bookings found.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.map(booking => `
                        <tr>
                            <td>${booking.id}</td>
                            <td>${booking.venue_name}</td>
                            <td>${booking.booking_date}</td>
                            <td>₱${parseFloat(booking.amount).toLocaleString()}</td>
                            <td>
                                <span class="badge ${getStatusBadgeClass(booking.status)}">${booking.status}</span>
                            </td>
                            <td>
                                ${booking.status === 'pending' ? '<button class="btn btn-sm btn-danger" onclick="cancelBooking(\'' + booking.id + '\')">Cancel</button>' : ''}
                            </td>
                        </tr>
                    `).join('');
                })
                .catch(error => {
                    console.error('Error fetching bookings:', error);
                    document.getElementById('myBookingsTable').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading bookings.</td></tr>';
                });
        }

        function getStatusBadgeClass(status) {
            switch (status) {
                case 'pending': return 'bg-warning';
                case 'confirmed': return 'bg-success';
                case 'completed': return 'bg-primary';
                case 'cancelled': return 'bg-secondary';
                case 'suspended': return 'bg-danger';
                default: return 'bg-light text-dark';
            }
        }

        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                fetch('../api/cancelBooking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: bookingId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking cancelled successfully.');
                        fetchMyBookings();
                    } else {
                        alert('Error cancelling booking: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error cancelling booking:', error);
                    alert('Error cancelling booking.');
                });
            }
        }
    </script>
</body>
</html>