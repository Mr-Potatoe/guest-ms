<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Admin.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();
    
    $admin = new Admin($db);
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($admin->login($username, $password)) {
        // Set session variables
        $_SESSION['admin_id'] = $admin->admin_id;
        $_SESSION['username'] = $admin->username;
        $_SESSION['role'] = $admin->role;
        
        // Redirect to dashboard
        header("Location: ../../index.php");
        exit();
    } else {
        $_SESSION['login_error'] = "Invalid username or password";
        header("Location: ../../index.php");
        exit();
    }
}

// If not POST request, redirect to homepage
header("Location: ../../index.php");
exit(); 