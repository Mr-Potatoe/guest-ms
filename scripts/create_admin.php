<?php
require_once "../config/database.php";
require_once "../models/Admin.php";

// Initial admin credentials
$admin_username = "admin";
$admin_password = "password"; // You should change this immediately after first login
$admin_email = "admin@hotel.com";
$admin_role = "super_admin";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if admin already exists
    $query = "SELECT COUNT(*) FROM Admins WHERE username = :username OR email = :email";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $admin_username);
    $stmt->bindParam(":email", $admin_email);
    $stmt->execute();
    
    if ($stmt->fetchColumn() > 0) {
        die("Admin already exists! Script terminated.\n");
    }
    
    // Create new admin
    $admin = new Admin($db);
    if ($admin->create($admin_username, $admin_password, $admin_email, $admin_role)) {
        echo "Super Admin created successfully!\n";
        echo "Username: " . $admin_username . "\n";
        echo "Password: " . $admin_password . "\n";
        echo "Email: " . $admin_email . "\n";
        echo "\nPlease change these credentials after first login!\n";
    } else {
        echo "Failed to create admin.\n";
    }
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}
?> 