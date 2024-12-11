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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update booking
    $booking->booking_id = $_POST['booking_id'];
    $booking->guest_id = $_POST['guest_id'];
    $booking->room_id = $_POST['room_id'];
    $booking->check_in_date = $_POST['check_in_date'];
    $booking->check_out_date = $_POST['check_out_date'];
    $booking->status = $_POST['status'];
    $booking->created_by = $_SESSION['admin_id'];
    
    // Calculate new total price
    $booking->total_price = $booking->calculateTotalPrice();

    if ($booking->update()) {
        $_SESSION['success'] = "Booking updated successfully.";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Unable to update booking. Room might not be available for selected dates.";
    }
}

// Get booking data for form
if (isset($_GET['id'])) {
    $booking->booking_id = $_GET['id'];
    $booking->readOne();
} else {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking - Lodging House Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Edit Booking</h4>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            <div class="card-body">
                <form action="edit.php" method="POST">
                    <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking->booking_id); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="guest_id" class="form-label">Guest</label>
                            <select class="form-control" id="guest_id" name="guest_id" required>
                                <?php
                                $guests = $guest->readAll();
                                while ($row = $guests->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = ($row['guest_id'] == $booking->guest_id) ? 'selected' : '';
                                    echo "<option value='" . $row['guest_id'] . "' {$selected}>" . 
                                         htmlspecialchars($row['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="room_id" class="form-label">Room</label>
                            <select class="form-control" id="room_id" name="room_id" required>
                                <?php
                                $rooms = $room->readAll();
                                while ($row = $rooms->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = ($row['room_id'] == $booking->room_id) ? 'selected' : '';
                                    echo "<option value='" . $row['room_id'] . "' {$selected}>" . 
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
                            <input type="date" class="form-control" id="check_in_date" name="check_in_date" 
                                   value="<?php echo $booking->check_in_date; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="check_out_date" class="form-label">Check Out Date</label>
                            <input type="date" class="form-control" id="check_out_date" name="check_out_date" 
                                   value="<?php echo $booking->check_out_date; ?>" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="pending" <?php echo $booking->status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $booking->status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="checked_in" <?php echo $booking->status == 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                            <option value="checked_out" <?php echo $booking->status == 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                            <option value="cancelled" <?php echo $booking->status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Created By</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($booking->created_by_name ?? 'System'); ?>" readonly>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Update Booking</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
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

        $(document).ready(function() {
            const validTransitions = {
                'pending': ['confirmed', 'cancelled'],
                'confirmed': ['checked_in', 'cancelled'],
                'checked_in': ['checked_out'],
                'checked_out': [],
                'cancelled': []
            };

            const originalStatus = $('#status').val();

            $('#status').change(function() {
                const newStatus = $(this).val();
                
                if (!validTransitions[originalStatus].includes(newStatus)) {
                    alert('Invalid status transition. Please follow the correct flow:\nPending → Confirmed → Checked In → Checked Out\n\nBookings can be cancelled from Pending or Confirmed status.');
                    $(this).val(originalStatus);
                    return false;
                }
            });

            // Disable status selection for completed or cancelled bookings
            if (originalStatus === 'checked_out' || originalStatus === 'cancelled') {
                $('#status').prop('disabled', true);
            }
        });
    </script>
</body>
</html> 