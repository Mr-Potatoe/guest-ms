<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Payment.php";
require_once "../../models/Booking.php";
require_once "../../models/Guest.php";
require_once "../../models/Room.php";
require_once "../../models/Receipt.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// First, check if the payment exists
$payment = new Payment($db);
$payment_id = $_GET['id'];

// Get receipt details with error checking
$query = "SELECT p.payment_id, p.payment_date, p.amount, p.payment_type, p.payment_method, p.booking_id,
                 b.guest_id, b.room_id, b.check_in_date, b.check_out_date,
                 g.name as guest_name, g.contact_number, g.email,
                 rm.room_number, rm.room_type, rm.price_per_night,
                 a.username as processed_by_name,
                 r.receipt_id, r.issued_date
          FROM payments p
          JOIN bookings b ON p.booking_id = b.booking_id
          JOIN guests g ON b.guest_id = g.guest_id
          JOIN rooms rm ON b.room_id = rm.room_id
          LEFT JOIN admins a ON p.processed_by = a.admin_id
          LEFT JOIN receipts r ON p.payment_id = r.payment_id
          WHERE p.payment_id = :payment_id";

$stmt = $db->prepare($query);
$stmt->bindParam(":payment_id", $payment_id);
$stmt->execute();
$receipt_data = $stmt->fetch(PDO::FETCH_ASSOC);

// If no data found, redirect with error
if (!$receipt_data) {
    $_SESSION['error'] = "Receipt not found.";
    header("Location: index.php");
    exit();
}

// Create receipt if it doesn't exist
if (!isset($receipt_data['receipt_id'])) {
    $receipt = new Receipt($db);
    $receipt->payment_id = $payment_id;
    $receipt->issued_date = date('Y-m-d H:i:s');
    $receipt->total_amount = $receipt_data['amount'];
    
    if ($receipt->create()) {
        // Refresh receipt data
        $stmt->execute();
        $receipt_data = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// Add a query to get services for this booking
$services_query = "SELECT s.service_type, s.description, s.price, s.status
                  FROM services s
                  JOIN payments p ON s.booking_id = p.booking_id
                  WHERE p.payment_id = :payment_id";

$services_stmt = $db->prepare($services_query);
$services_stmt->bindParam(":payment_id", $payment_id);
$services_stmt->execute();
$services = $services_stmt->fetchAll(PDO::FETCH_ASSOC);

// Auto-print if print parameter is set
$autoPrint = isset($_GET['print']) && $_GET['print'] === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #<?php echo str_pad($receipt_data['receipt_id'], 8, '0', STR_PAD_LEFT); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            .receipt { 
                border: none !important;
                box-shadow: none !important;
            }
            body { 
                padding: 0;
                margin: 0;
            }
        }
        .receipt {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .receipt-number {
            font-size: 1.2em;
            color: #666;
            margin: 10px 0;
        }
        .table td:first-child {
            font-weight: bold;
            width: 150px;
        }
    </style>
</head>
<body>
    <div class="container receipt">
        <div class="receipt-header">
            <h2>Lodging House Management System</h2>
            <div class="receipt-number">
                Receipt #<?php echo str_pad($receipt_data['receipt_id'] ?? $receipt_data['payment_id'], 8, '0', STR_PAD_LEFT); ?>
            </div>
            <div>Date: <?php echo date('F d, Y h:i A', strtotime($receipt_data['payment_date'])); ?></div>
        </div>

        <!-- Guest Information -->
        <div class="row mb-4">
            <div class="col-6">
                <h5>Guest Information</h5>
                <p>
                    Name: <?php echo htmlspecialchars($receipt_data['guest_name']); ?><br>
                    Contact: <?php echo htmlspecialchars($receipt_data['contact_number']); ?><br>
                    Email: <?php echo htmlspecialchars($receipt_data['email']); ?>
                </p>
            </div>
            <div class="col-6">
                <h5>Room Details</h5>
                <p>
                    Room: <?php echo htmlspecialchars($receipt_data['room_number']); ?><br>
                    Type: <?php echo htmlspecialchars($receipt_data['room_type']); ?><br>
                    Rate: ₱<?php echo number_format($receipt_data['price_per_night'], 2); ?>/night
                </p>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="row mb-4">
            <div class="col-12">
                <h5>Payment Details</h5>
                <table class="table table-bordered">
                    <tr>
                        <td>Payment Method:</td>
                        <td><?php echo ucfirst($receipt_data['payment_method']); ?></td>
                    </tr>
                    <tr>
                        <td>Payment Type:</td>
                        <td><?php echo ucfirst($receipt_data['payment_type']); ?></td>
                    </tr>
                    <tr>
                        <td>Amount Paid:</td>
                        <td>₱<?php echo number_format($receipt_data['amount'], 2); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Add this after the Payment Details section -->
        <?php if ($services_stmt->rowCount() > 0): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h5>Additional Services</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Service Type</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_services = 0;
                        foreach ($services as $service): 
                            $total_services += $service['price'];
                        ?>
                        <tr>
                            <td><?php echo ucwords(str_replace('_', ' ', $service['service_type'])); ?></td>
                            <td><?php echo htmlspecialchars($service['description']); ?></td>
                            <td>₱<?php echo number_format($service['price'], 2); ?></td>
                            <td><?php echo ucfirst($service['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-info">
                            <td colspan="2" class="text-end"><strong>Total Services:</strong></td>
                            <td colspan="2"><strong>₱<?php echo number_format($total_services, 2); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="row mb-4">
            <div class="col-12">
                <table class="table table-bordered">
                    <tr class="table-secondary">
                        <td><strong>Room Charge:</strong></td>
                        <td>₱<?php echo number_format($receipt_data['amount'] - $total_services, 2); ?></td>
                    </tr>
                    <tr class="table-secondary">
                        <td><strong>Services Total:</strong></td>
                        <td>₱<?php echo number_format($total_services, 2); ?></td>
                    </tr>
                    <tr class="table-dark">
                        <td><strong>Total Amount Paid:</strong></td>
                        <td><strong>₱<?php echo number_format($receipt_data['amount'], 2); ?></strong></td>
                    </tr>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="row mt-5">
            <div class="col-6">
                <p>Processed by: <?php echo htmlspecialchars($receipt_data['processed_by_name']); ?></p>
            </div>
            <div class="col-6 text-end">
                <p>_______________________<br>Authorized Signature</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row mt-4 no-print">
            <div class="col-12 text-center">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print Receipt
                </button>
                <a href="index.php?booking_id=<?php echo $receipt_data['booking_id']; ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($autoPrint): ?>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
    <?php endif; ?>
</body>
</html> 