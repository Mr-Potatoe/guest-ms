<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Guest.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    $guest = new Guest($db);

    // Set guest properties
    $guest->name = $_POST['name'];
    $guest->contact_number = $_POST['contact_number'];
    $guest->id_type = $_POST['id_type'];
    $guest->id_number = $_POST['id_number'];
    $guest->email = $_POST['email'];

    // Check if guest already exists
    if ($guest->exists()) {
        $_SESSION['error'] = "A guest with this ID already exists.";
    } else {
        // Create the guest
        if ($guest->create()) {
            $_SESSION['success'] = "Guest created successfully.";
        } else {
            $_SESSION['error'] = "Unable to create guest.";
        }
    }
}

header("Location: index.php");
exit(); 