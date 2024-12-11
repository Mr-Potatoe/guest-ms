<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Booking.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $database = new Database();
    $db = $database->getConnection();
    $booking = new Booking($db);
    
    $booking->booking_id = $_GET['id'];
    
    // Read current booking data
    if ($booking->readOne()) {
        $booking->created_by = $_SESSION['admin_id'];
        
        if ($booking->updateStatus($_GET['status'])) {
            $_SESSION['success'] = "Booking status updated to " . $_GET['status'];
        } else {
            $_SESSION['error'] = "Invalid status transition. Please follow the correct flow: 
                                Reserved → Checked In → Checked Out";
        }
    } else {
        $_SESSION['error'] = "Booking not found.";
    }
}

header("Location: index.php");
exit(); 