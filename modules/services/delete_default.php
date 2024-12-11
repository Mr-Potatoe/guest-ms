<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Service.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $service = new Service($db);
    
    $service->service_id = $_GET['id'];
    
    if ($service->deleteDefault()) {
        $_SESSION['success'] = "Default service deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete default service.";
    }
}

header("Location: index.php");
exit();
?> 