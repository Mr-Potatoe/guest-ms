<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Room.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['room_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $room = new Room($db);
    
    $room->room_id = $_POST['room_id'];
    
    // Check if room has any bookings before deleting
    $bookings = $room->getBookings();
    if ($bookings->rowCount() > 0) {
        $_SESSION['error'] = "Cannot delete room. It has existing bookings.";
    } else {
        if ($room->delete()) {
            $_SESSION['success'] = "Room deleted successfully.";
        } else {
            $_SESSION['error'] = "Unable to delete room.";
        }
    }
}

header("Location: index.php");
exit(); 