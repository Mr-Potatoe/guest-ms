<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Booking.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    $booking = new Booking($db);

    // Set booking properties
    $booking->guest_id = $_POST['guest_id'];
    $booking->room_id = $_POST['room_id'];
    $booking->check_in_date = $_POST['check_in_date'];
    $booking->check_out_date = $_POST['check_out_date'];
    $booking->status = $_POST['status']; // Always start with reserved status
    $booking->created_by = $_SESSION['admin_id'];
    $booking->handled_by = $_SESSION['admin_id'];

    // Calculate total price
    $booking->total_price = $booking->calculateTotalPrice();

    // Create the booking
    if ($booking->create()) {
        $_SESSION['success'] = "Booking created successfully.";
    } else {
        $_SESSION['error'] = "Unable to create booking. Room might not be available for selected dates.";
    }
}

header("Location: index.php");
exit(); 