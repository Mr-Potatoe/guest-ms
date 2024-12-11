<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Guest.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$guest = new Guest($db);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update guest
    $guest->guest_id = $_POST['guest_id'];
    $guest->name = $_POST['name'];
    $guest->contact_number = $_POST['contact_number'];
    $guest->id_type = $_POST['id_type'];
    $guest->id_number = $_POST['id_number'];
    $guest->email = $_POST['email'];

    if ($guest->update()) {
        $_SESSION['success'] = "Guest updated successfully.";
    } else {
        $_SESSION['error'] = "Unable to update guest.";
    }
    
    header("Location: index.php");
    exit();
}

// Get guest data for form
if (isset($_GET['id'])) {
    $guest->guest_id = $_GET['id'];
    $guest->readOne();
} else {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Guest - Lodging House Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Guest</h4>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="edit.php" method="POST">
                            <input type="hidden" name="guest_id" value="<?php echo htmlspecialchars($guest->guest_id); ?>">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($guest->name); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number" 
                                       value="<?php echo htmlspecialchars($guest->contact_number); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="id_type" class="form-label">ID Type</label>
                                <select class="form-control" id="id_type" name="id_type" required>
                                    <option value="Passport" <?php echo $guest->id_type == 'Passport' ? 'selected' : ''; ?>>Passport</option>
                                    <option value="National ID" <?php echo $guest->id_type == 'National ID' ? 'selected' : ''; ?>>National ID</option>
                                    <option value="Driver's License" <?php echo $guest->id_type == "Driver's License" ? 'selected' : ''; ?>>Driver's License</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="id_number" class="form-label">ID Number</label>
                                <input type="text" class="form-control" id="id_number" name="id_number" 
                                       value="<?php echo htmlspecialchars($guest->id_number); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($guest->email); ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Guest</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 