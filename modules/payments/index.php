<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Payment.php";
require_once "../../models/Booking.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$payment = new Payment($db);
$booking = new Booking($db);

// Get booking ID from URL
$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : null;

// Get booking details if booking_id is provided
if ($booking_id) {
    $booking->booking_id = $booking_id;
    $booking->readOne();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments - Lodging House Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <?php include_once __DIR__ . '/../includes/alerts.php'; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">
                    <?php echo $booking_id ? "Payments for Booking #$booking_id" : "All Payments"; ?>
                </h4>
                <div>
                    <?php if ($booking_id): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPaymentModal">
                            <i class="fas fa-plus"></i> Add Payment
                        </button>
                    <?php endif; ?>
                    <a href="../bookings/index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($booking_id): ?>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Total Amount</h5>
                                    <h3>₱<?php echo number_format($booking->total_price, 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Paid Amount</h5>
                                    <h3>₱<?php echo number_format($payment->getTotalPaidAmount($booking_id), 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Remaining Balance</h5>
                                    <h3>₱<?php echo number_format($payment->getRemainingBalance($booking_id), 2); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Payment Date</th>
                                <th>Amount</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Processed By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $result = $payment->readByBooking($booking_id);
                            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                echo "<tr>";
                                echo "<td>" . date('M d, Y', strtotime($row['payment_date'])) . "</td>";
                                echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
                                echo "<td>" . ucfirst($row['payment_type']) . "</td>";
                                echo "<td>" . ucfirst($row['payment_method']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['processed_by_name']) . "</td>";
                                echo "<td>";
                                echo "<a href='generate_receipt.php?id=" . $row['payment_id'] . "' class='btn btn-sm btn-info me-1'>";
                                echo "<i class='fas fa-receipt'></i> View Receipt</a>";
                                echo "<a href='generate_receipt.php?id=" . $row['payment_id'] . "&print=true' class='btn btn-sm btn-secondary'>";
                                echo "<i class='fas fa-print'></i> Print</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">
                        Please select a booking to view its payments.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <?php if ($booking_id): ?>
        <div class="modal fade" id="addPaymentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="create.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Amount</label>
                                <input type="number" step="0.01" class="form-control" name="amount" required
                                       max="<?php echo $payment->getRemainingBalance($booking_id); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Payment Type</label>
                                <select class="form-control" name="payment_type" required>
                                    <option value="partial">Partial Payment</option>
                                    <option value="full">Full Payment</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select class="form-control" name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="debit_card">Debit Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Add Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 