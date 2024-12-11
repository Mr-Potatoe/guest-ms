<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Service.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if (isset($_GET['id']) && isset($_GET['status'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $service = new Service($db);
    $service->service_id = $_GET['id'];
    
    // Validate status
    $valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($_GET['status'], $valid_statuses)) {
        $_SESSION['error'] = "Invalid status.";
        header("Location: index.php");
        exit();
    }

    // Update status
    if ($service->updateStatus($_GET['status'])) {
        $_SESSION['success'] = "Service status updated successfully.";
    } else {
        $_SESSION['error'] = "Unable to update service status.";
    }
}

header("Location: index.php");
exit(); 