<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Booking.php";
require_once "../../models/Room.php";
require_once "../../models/Guest.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$booking = new Booking($db);
$room = new Room($db);
$guest = new Guest($db);

// Get search term and filters if any
$search = isset($_GET['search']) ? $_GET['search'] : "";
$filter = [
    'status' => isset($_GET['status']) ? $_GET['status'] : "",
    'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : "",
    'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : "",
    'guest_id' => isset($_GET['guest_id']) ? $_GET['guest_id'] : "",
    'room_id' => isset($_GET['room_id']) ? $_GET['room_id'] : ""
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Lodging House Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Booking Management</h2>
            <a href="../../index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Search and Filter Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Search guest name or room number..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $filter['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $filter['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="checked_in" <?php echo $filter['status'] == 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                            <option value="checked_out" <?php echo $filter['status'] == 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                            <option value="cancelled" <?php echo $filter['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_from" class="form-control" 
                               value="<?php echo $filter['date_from']; ?>" placeholder="From Date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="date_to" class="form-control" 
                               value="<?php echo $filter['date_to']; ?>" placeholder="To Date">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Booking Button -->
        <div class="text-end mb-4">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBookingModal">
                <i class="fas fa-plus"></i> New Booking
            </button>
        </div>

        <!-- Bookings Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Guest</th>
                                <th>Room</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Status</th>
                                <th>Total Price</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $booking->readAll($search, $filter);
                            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                $statusClass = '';
                                switch($row['status']) {
                                    case 'pending':
                                        $statusClass = 'warning';
                                        $statusText = 'Pending';
                                        break;
                                    case 'confirmed':
                                        $statusClass = 'info';
                                        $statusText = 'Confirmed';
                                        break;
                                    case 'checked_in':
                                        $statusClass = 'success';
                                        $statusText = 'Checked In';
                                        break;
                                    case 'checked_out':
                                        $statusClass = 'secondary';
                                        $statusText = 'Checked Out';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'danger';
                                        $statusText = 'Cancelled';
                                        break;
                                    default:
                                        $statusClass = 'light';
                                        $statusText = ucfirst($row['status']);
                                }
                                
                                echo "<tr>";
                                echo "<td>" . $row['booking_id'] . "</td>";
                                echo "<td>" . htmlspecialchars($row['guest_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['room_number']) . " (" . $row['room_type'] . ")</td>";
                                echo "<td>" . date('Y-m-d', strtotime($row['check_in_date'])) . "</td>";
                                echo "<td>" . date('Y-m-d', strtotime($row['check_out_date'])) . "</td>";
                                echo "<td><span class='badge bg-{$statusClass}'>" . $statusText . "</span></td>";
                                echo "<td>$" . number_format($row['total_price'], 2) . "</td>";
                                echo "<td>" . htmlspecialchars($row['created_by_name'] ?? 'N/A') . "</td>";
                                echo "<td>
                                        <button class='btn btn-sm btn-primary edit-booking' data-id='" . $row['booking_id'] . "'>
                                            <i class='fas fa-edit'></i>
                                        </button>
                                        <button class='btn btn-sm btn-success update-status' data-id='" . $row['booking_id'] . "' 
                                                data-status='" . $row['status'] . "'>
                                            <i class='fas fa-sync-alt'></i>
                                        </button>
                                        <a href='../payments/index.php?booking_id=" . $row['booking_id'] . "' class='btn btn-sm btn-success'>
                                            <i class='fas fa-money-bill'></i> Payments
                                        </a>
                                      </td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Booking Modal -->
    <div class="modal fade" id="addBookingModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="create.php" method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="guest_id" class="form-label">Guest</label>
                                <select class="form-control" id="guest_id" name="guest_id" required>
                                    <option value="">Select Guest</option>
                                    <?php
                                    $guests = $guest->readAll();
                                    while ($row = $guests->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value='" . $row['guest_id'] . "'>" . 
                                             htmlspecialchars($row['name']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="room_id" class="form-label">Room</label>
                                <select class="form-control" id="room_id" name="room_id" required>
                                    <option value="">Select Room</option>
                                    <?php
                                    $rooms = $room->readAll();
                                    while ($row = $rooms->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value='" . $row['room_id'] . "'>" . 
                                             htmlspecialchars($row['room_number']) . " - " . 
                                             $row['room_type'] . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="check_in_date" class="form-label">Check In Date</label>
                                <input type="date" class="form-control" id="check_in_date" 
                                       name="check_in_date" required
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="check_out_date" class="form-label">Check Out Date</label>
                                <input type="date" class="form-control" id="check_out_date" 
                                       name="check_out_date" required
                                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="checked_in">Checked In</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Create Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Edit booking
        $('.edit-booking').click(function() {
            var bookingId = $(this).data('id');
            window.location.href = 'edit.php?id=' + bookingId;
        });

        // Update booking status
        $('.update-status').click(function() {
            var bookingId = $(this).data('id');
            var currentStatus = $(this).data('status');
            var nextStatus = getNextStatus(currentStatus);
            
            if (!isValidStatusTransition(currentStatus, nextStatus)) {
                alert('Invalid status transition. Please follow the correct flow: Pending → Confirmed → Checked In → Checked Out');
                return;
            }
            
            if (confirm('Update booking status to ' + nextStatus.replace('_', ' ') + '?')) {
                window.location.href = 'update_status.php?id=' + bookingId + '&status=' + nextStatus;
            }
        });

        function getNextStatus(currentStatus) {
            switch(currentStatus) {
                case 'pending':
                    return 'confirmed';
                case 'confirmed':
                    return 'checked_in';
                case 'checked_in':
                    return 'checked_out';
                default:
                    return currentStatus;
            }
        }

        // Calculate total price when dates or room changes
        $('#check_in_date, #check_out_date, #room_id').change(function() {
            calculateTotalPrice();
        });

        function calculateTotalPrice() {
            var roomId = $('#room_id').val();
            var checkIn = $('#check_in_date').val();
            var checkOut = $('#check_out_date').val();
            
            if (roomId && checkIn && checkOut) {
                $.get('calculate_price.php', {
                    room_id: roomId,
                    check_in: checkIn,
                    check_out: checkOut
                }, function(response) {
                    $('#total_price').val(response);
                });
            }
        }

        // Update the status button display
        $('.update-status').each(function() {
            var currentStatus = $(this).data('status');
            var nextStatus = getNextStatus(currentStatus);
            
            if (currentStatus === 'checked_out' || currentStatus === 'cancelled') {
                $(this).prop('disabled', true)
                       .html('<i class="fas fa-check"></i> ' + 
                            (currentStatus === 'cancelled' ? 'Cancelled' : 'Completed'))
                       .removeClass('btn-primary')
                       .addClass(currentStatus === 'cancelled' ? 'btn-danger' : 'btn-success');
            } else {
                var buttonText = '';
                switch(nextStatus) {
                    case 'confirmed':
                        buttonText = 'Confirm';
                        break;
                    case 'checked_in':
                        buttonText = 'Check In';
                        break;
                    case 'checked_out':
                        buttonText = 'Check Out';
                        break;
                    default:
                        buttonText = 'Update';
                }
                $(this).html('<i class="fas fa-sync-alt"></i> ' + buttonText);
            }
        });

        // Add cancel booking functionality
        $('.cancel-booking').click(function() {
            var bookingId = $(this).data('id');
            if (confirm('Are you sure you want to cancel this booking?')) {
                window.location.href = 'update_status.php?id=' + bookingId + '&status=cancelled';
            }
        });

        // Add this to your existing JavaScript
        function isValidStatusTransition(currentStatus, newStatus) {
            const validTransitions = {
                'pending': ['checked_in'],
                'checked_in': ['checked_out'],
                'checked_out': []
            };
            
            return validTransitions[currentStatus] && validTransitions[currentStatus].includes(newStatus);
        }

        // Add this to your existing JavaScript
        $(document).ready(function() {
            // Set min date for check-in
            $('#check_in_date').change(function() {
                let checkInDate = $(this).val();
                let nextDay = new Date(checkInDate);
                nextDay.setDate(nextDay.getDate() + 1);
                
                // Format the date as YYYY-MM-DD
                let formattedDate = nextDay.toISOString().split('T')[0];
                
                // Update check-out min date
                $('#check_out_date').attr('min', formattedDate);
                
                // If check-out date is now invalid, clear it
                if($('#check_out_date').val() <= checkInDate) {
                    $('#check_out_date').val('');
                }
            });

            // Prevent selecting past dates on page load
            let today = new Date().toISOString().split('T')[0];
            let tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            let tomorrowFormatted = tomorrow.toISOString().split('T')[0];
            
            $('#check_in_date').attr('min', today);
            $('#check_out_date').attr('min', tomorrowFormatted);
        });
    </script>
</body>
</html> 