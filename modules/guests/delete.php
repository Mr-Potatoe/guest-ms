<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Guest.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['guest_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $guest = new Guest($db);
    
    $guest->guest_id = $_POST['guest_id'];
    
    if ($guest->delete()) {
        $_SESSION['success'] = "Guest deleted successfully.";
    } else {
        $_SESSION['error'] = "Unable to delete guest.";
    }
}

header("Location: index.php");
exit(); 