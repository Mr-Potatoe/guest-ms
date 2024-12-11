<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Room.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    $room = new Room($db);

    // Set room properties
    $room->room_number = $_POST['room_number'];
    $room->room_type = $_POST['room_type'];
    $room->status = $_POST['status'];
    $room->price_per_night = $_POST['price_per_night'];
    $room->capacity = $_POST['capacity'];
    $room->updated_by = $_SESSION['admin_id'];

    // Check if room number already exists
    if ($room->isRoomNumberExists()) {
        $_SESSION['error'] = "A room with this number already exists.";
    } else {
        // Create the room
        if ($room->create()) {
            $_SESSION['success'] = "Room created successfully.";
        } else {
            $_SESSION['error'] = "Unable to create room.";
        }
    }
}

header("Location: index.php");
exit(); 