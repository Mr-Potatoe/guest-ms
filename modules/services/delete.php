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
    $is_default = isset($_GET['type']) && $_GET['type'] === 'default';
    
    if ($is_default) {
        if ($service->deleteDefault()) {
            $_SESSION['success'] = "Default service deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete default service.";
        }
    } else {
        if ($service->delete()) {
            $_SESSION['success'] = "Service deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete service.";
        }
    }
}

header("Location: index.php");
exit();
?> 