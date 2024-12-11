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

// Get search term if any
$search = isset($_GET['search']) ? $_GET['search'] : "";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - Lodging House Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Room Management</h2>
            <a href="../../index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Search and Add Room Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <form action="" method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" 
                           placeholder="Search rooms..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>
            <div class="col-md-6 text-end">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    <i class="fas fa-plus"></i> Add New Room
                </button>
            </div>
        </div>

        <!-- Rooms Table -->
        <div class="card">
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Room Number</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Price/Night</th>
                            <th>Capacity</th>
                            <th>Last Updated By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $result = $room->readAll($search);
                        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                            $statusClass = '';
                            switch($row['status']) {
                                case 'vacant':
                                    $statusClass = 'success';
                                    break;
                                case 'occupied':
                                    $statusClass = 'danger';
                                    break;
                                case 'maintenance':
                                    $statusClass = 'warning';
                                    break;
                            }
                            
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['room_number']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['room_type']) . "</td>";
                            echo "<td><span class='badge bg-" . $statusClass . "'>" . 
                                 ucfirst(htmlspecialchars($row['status'])) . "</span></td>";
                            echo "<td>PHP " . htmlspecialchars($row['price_per_night']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['capacity']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['updated_by_name']) . "</td>";
                            echo "<td>
                                    <button class='btn btn-sm btn-primary edit-room' data-id='" . $row['room_id'] . "'>
                                        <i class='fas fa-edit'></i>
                                    </button>
                                    <button class='btn btn-sm btn-info view-bookings' data-id='" . $row['room_id'] . "'>
                                        <i class='fas fa-book'></i>
                                    </button>
                                    <button class='btn btn-sm btn-danger delete-room' data-id='" . $row['room_id'] . "'>
                                        <i class='fas fa-trash'></i>
                                    </button>
                                  </td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="create.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="room_number" class="form-label">Room Number</label>
                            <input type="text" class="form-control" id="room_number" name="room_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="room_type" class="form-label">Room Type</label>
                            <select class="form-control" id="room_type" name="room_type" required>
                                <option value="Standard">Standard</option>
                                <option value="Deluxe">Deluxe</option>
                                <option value="Suite">Suite</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="vacant">Vacant</option>
                                <option value="occupied">Occupied</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="price_per_night" class="form-label">Price per Night</label>
                            <input type="number" step="0.01" class="form-control" id="price_per_night" 
                                   name="price_per_night" required>
                        </div>
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Edit room
        $('.edit-room').click(function() {
            var roomId = $(this).data('id');
            window.location.href = 'edit.php?id=' + roomId;
        });

        // Delete room
        $('.delete-room').click(function() {
            if (confirm('Are you sure you want to delete this room?')) {
                var roomId = $(this).data('id');
                var form = $('<form action="delete.php" method="post">' +
                            '<input type="hidden" name="room_id" value="' + roomId + '">' +
                            '</form>');
                $('body').append(form);
                form.submit();
            }
        });

        // View bookings
        $('.view-bookings').click(function() {
            var roomId = $(this).data('id');
            window.location.href = '../bookings/index.php?room_id=' + roomId;
        });
    </script>
</body>
</html> 