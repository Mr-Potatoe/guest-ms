<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Service.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['new_service_type'])) {
    $database = new Database();
    $db = $database->getConnection();
    $service = new Service($db);
    
    // Sanitize and validate the new service type
    $new_type = preg_replace('/[^a-z_]/', '', strtolower($_POST['new_service_type']));
    
    if ($service->addServiceType($new_type)) {
        $_SESSION['success'] = "New service type added successfully.";
    } else {
        $_SESSION['error'] = "Failed to add service type or type already exists.";
    }
}

header("Location: index.php");
exit();