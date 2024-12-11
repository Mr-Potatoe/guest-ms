<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Service.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $service = new Service($db);

    // Set service properties
    $service->service_type = $_POST['service_type'];
    $service->description = $_POST['description'];
    $service->price = $_POST['default_price'];
    $service->default_price = $_POST['default_price'];
    $service->created_by = $_SESSION['admin_id'];
    $service->is_default = true;
    $service->booking_id = null;

    // Create the default service
    if ($service->createDefault()) {
        $_SESSION['success'] = "Default service added successfully.";
    } else {
        $_SESSION['error'] = "Unable to add default service.";
    }
}

header("Location: index.php");
exit();
 