<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Service.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $service = new Service($db);

    // Set service properties
    $service->booking_id = $_POST['booking_id'];
    $service->service_type = $_POST['service_type'];
    $service->description = $_POST['description'];
    $service->price = $_POST['price'];
    $service->created_by = $_SESSION['admin_id'];

    // Create the service
    if ($service->create()) {
        $_SESSION['success'] = "Service added successfully.";
    } else {
        $_SESSION['error'] = "Unable to add service.";
    }

    // Redirect back to services list
    header("Location: index.php" . 
           (isset($_POST['booking_id']) ? "?booking_id=" . $_POST['booking_id'] : ""));
    exit();
}

header("Location: index.php");
exit(); 