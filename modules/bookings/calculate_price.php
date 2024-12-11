<?php
require_once "../../config/database.php";
require_once "../../models/Booking.php";

if (isset($_GET['room_id']) && isset($_GET['check_in']) && isset($_GET['check_out'])) {
    $database = new Database();
    $db = $database->getConnection();
    $booking = new Booking($db);
    
    $booking->room_id = $_GET['room_id'];
    $booking->check_in_date = $_GET['check_in'];
    $booking->check_out_date = $_GET['check_out'];
    
    echo $booking->calculateTotalPrice();
} 