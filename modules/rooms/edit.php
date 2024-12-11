<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Room.php";

// Check if user is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$room = new Room($db);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Update room
    $room->room_id = $_POST['room_id'];
    $room->room_number = $_POST['room_number'];
    $room->room_type = $_POST['room_type'];
    $room->status = $_POST['status'];
    $room->price_per_night = $_POST['price_per_night'];
    $room->capacity = $_POST['capacity'];
    $room->updated_by = $_SESSION['admin_id'];

    // Check if room number exists (excluding current room)
    if ($room->isRoomNumberExists()) {
        $_SESSION['error'] = "A room with this number already exists.";
        header("Location: edit.php?id=" . $room->room_id);
        exit();
    }

    if ($room->update()) {
        $_SESSION['success'] = "Room updated successfully.";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Unable to update room.";
    }
}

// Get room data for form
if (isset($_GET['id'])) {
    $room->room_id = $_GET['id'];
    $room->readOne();
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
    <title>Edit Room - Lodging House Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Room</h4>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="edit.php" method="POST">
                            <input type="hidden" name="room_id" value="<?php echo htmlspecialchars($room->room_id); ?>">
                            
                            <div class="mb-3">
                                <label for="room_number" class="form-label">Room Number</label>
                                <input type="text" class="form-control" id="room_number" name="room_number" 
                                       value="<?php echo htmlspecialchars($room->room_number); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="room_type" class="form-label">Room Type</label>
                                <select class="form-control" id="room_type" name="room_type" required>
                                    <option value="Standard" <?php echo $room->room_type == 'Standard' ? 'selected' : ''; ?>>Standard</option>
                                    <option value="Deluxe" <?php echo $room->room_type == 'Deluxe' ? 'selected' : ''; ?>>Deluxe</option>
                                    <option value="Suite" <?php echo $room->room_type == 'Suite' ? 'selected' : ''; ?>>Suite</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="vacant" <?php echo $room->status == 'vacant' ? 'selected' : ''; ?>>Vacant</option>
                                    <option value="occupied" <?php echo $room->status == 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                                    <option value="maintenance" <?php echo $room->status == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price_per_night" class="form-label">Price per Night</label>
                                <input type="number" step="0.01" class="form-control" id="price_per_night" 
                                       name="price_per_night" value="<?php echo htmlspecialchars($room->price_per_night); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="capacity" class="form-label">Capacity</label>
                                <input type="number" class="form-control" id="capacity" name="capacity" 
                                       value="<?php echo htmlspecialchars($room->capacity); ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Room</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 