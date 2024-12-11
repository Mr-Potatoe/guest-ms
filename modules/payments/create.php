<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Payment.php";
require_once "../../models/Booking.php";
require_once "../../models/Receipt.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $payment = new Payment($db);
    $booking = new Booking($db);
    $receipt = new Receipt($db);

    // Set payment properties
    $payment->booking_id = $_POST['booking_id'];
    $payment->payment_date = date('Y-m-d H:i:s');
    $payment->amount = $_POST['amount'];
    $payment->payment_type = $_POST['payment_type'];
    $payment->payment_method = $_POST['payment_method'];
    $payment->processed_by = $_SESSION['admin_id'];

    // Validate payment amount against remaining balance
    $remaining_balance = $payment->getRemainingBalance($_POST['booking_id']);
    
    if ($payment->amount <= 0 || $payment->amount > $remaining_balance) {
        $_SESSION['error'] = "Invalid payment amount. Maximum allowed: ₱" . number_format($remaining_balance, 2);
        header("Location: index.php?booking_id=" . $_POST['booking_id']);
        exit();
    }

    // Create the payment and get the new payment ID
    if ($payment->create()) {
        // Get the last inserted payment ID
        $last_payment_id = $db->lastInsertId();
        
        // Generate receipt
        $receipt->payment_id = $last_payment_id;
        $receipt->issued_date = date('Y-m-d H:i:s');
        $receipt->total_amount = $payment->amount;
        
        if ($receipt->create()) {
            $_SESSION['success'] = "Payment recorded and receipt generated successfully.";
            // Redirect to receipt view
            header("Location: generate_receipt.php?id=" . $last_payment_id);
            exit();
        }
        
        // Check if this payment completes the total amount
        $new_remaining = $payment->getRemainingBalance($_POST['booking_id']);
        
        if ($new_remaining <= 0) {
            // Update booking status to confirmed if it was pending
            $booking->booking_id = $_POST['booking_id'];
            $booking->readOne();
            if ($booking->status == 'pending') {
                $booking->updateStatus('confirmed');
            }
        }
    } else {
        $_SESSION['error'] = "Unable to record payment.";
        header("Location: index.php?booking_id=" . $_POST['booking_id']);
        exit();
    }
}

header("Location: index.php?booking_id=" . $_POST['booking_id']);
exit();
