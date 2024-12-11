<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Admin.php";

if (isset($_SESSION['admin_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    
    $admin = new Admin($db);
    $admin->admin_id = $_SESSION['admin_id'];

    // Destroy session
    session_destroy();
}

// Redirect to homepage
header("Location: ../../index.php");
exit();