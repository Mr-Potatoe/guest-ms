<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Service.php";
require_once "../../models/Booking.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$service = new Service($db);
$booking = new Booking($db);

$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Lodging House Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <?php include_once __DIR__ . '/../includes/alerts.php'; ?>

        <!-- Service Management Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-concierge-bell"></i> Service Management</h5>
                <div>
                    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addServiceModal">
                        <i class="fas fa-plus"></i> New Service Request
                    </button>
                    <!-- <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addDefaultServiceModal">
                        <i class="fas fa-cog"></i> Add Service Template
                    </button> -->
                    <a href="../../index.php" class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> Dashboard
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <!-- Service Type Management Section -->
                <div class="card mb-4 border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-tags"></i> Service Categories</h6>
                    </div>
                    <div class="card-body">
                        <!-- Add Service Type Form -->
                        <form action="add_service_type.php" method="POST" class="row g-3 align-items-end mb-4">
                            <div class="col-md-4">
                                <label class="form-label">New Service Category</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="new_service_type" required 
                                           placeholder="e.g., room_service" pattern="[a-z_]{3,50}">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </div>
                                <small class="text-muted">Use lowercase letters and underscores only</small>
                            </div>
                        </form>

                        <!-- Service Categories Display -->
                        <div class="row">
                            <?php
                            $service_types = $service->getServiceTypes();
                            $colors = ['primary', 'success', 'warning', 'danger', 'info'];
                            
                            foreach ($service_types as $index => $type) {
                                $color = $colors[$index % count($colors)];
                                echo "<div class='col-md-3 mb-3'>
                                        <div class='card bg-{$color} bg-opacity-25 h-100'>
                                            <div class='card-body'>
                                                <h6 class='card-title text-{$color}'><i class='fas fa-tag'></i> " . 
                                                    ucwords(str_replace('_', ' ', $type)) . 
                                                "</h6>
                                                <p class='small mb-0 text-muted'>Available for bookings</p>
                                            </div>
                                        </div>
                                      </div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Service Templates Section -->
                <!-- <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="fas fa-clipboard-list"></i> Service Templates</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Default Price</th>
                                        <th width="100">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $defaults = $service->readDefaultServices();
                                    while ($row = $defaults->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<tr>";
                                        echo "<td><span class='badge bg-secondary'>" . ucfirst($row['service_type']) . "</span></td>";
                                        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                        echo "<td>₱" . number_format($row['default_price'], 2) . "</td>";
                                        echo "<td>
                                                <button class='btn btn-sm btn-primary edit-default me-1' 
                                                        data-id='{$row['service_id']}' title='Edit'>
                                                    <i class='fas fa-edit'></i>
                                                </button>
                                                <button class='btn btn-sm btn-danger delete-default' 
                                                        data-id='{$row['service_id']}' title='Delete'>
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
                </div> -->

                <!-- Active Services Section -->
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-list"></i> Active Service Requests</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Booking</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Requested</th>
                                        <th width="100">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $result = $booking_id ? 
                                             $service->readByBooking($booking_id) : 
                                             $service->readAll();
                                    
                                    if ($result->rowCount() > 0) {
                                        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                                            $statusClass = match($row['status']) {
                                                'pending' => 'warning',
                                                'in_progress' => 'info',
                                                'completed' => 'success',
                                                'cancelled' => 'danger',
                                                default => 'secondary'
                                            };
                                            
                                            echo "<tr>";
                                            echo "<td>{$row['service_id']}</td>";
                                            echo "<td>Booking #{$row['booking_id']} - " . htmlspecialchars($row['guest_name']) . "</td>";
                                            echo "<td><span class='badge bg-secondary'>" . ucfirst(str_replace('_', ' ', $row['service_type'])) . "</span></td>";
                                            echo "<td>₱" . number_format($row['price'], 2) . "</td>";
                                            echo "<td><span class='badge bg-{$statusClass}'>" . ucfirst(str_replace('_', ' ', $row['status'])) . "</span></td>";
                                            echo "<td>" . date('M d, Y h:i A', strtotime($row['created_at'])) . "</td>";
                                            echo "<td>
                                                    <div class='btn-group'>
                                                        <button class='btn btn-sm btn-primary edit-service' 
                                                                data-id='{$row['service_id']}' title='Edit'>
                                                            <i class='fas fa-edit'></i>
                                                        </button>
                                                        <button class='btn btn-sm btn-success update-status' 
                                                                data-id='{$row['service_id']}' 
                                                                data-status='{$row['status']}' title='Update Status'>
                                                            <i class='fas fa-sync-alt'></i>
                                                        </button>
                                                    </div>
                                                  </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center'>No services found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include modals -->
    <?php 
    include 'modals/add_service_modal.php';
    include 'modals/add_default_service_modal.php';
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // For edit service button
        $('.edit-service').click(function() {
            var id = $(this).data('id');
            window.location.href = 'edit.php?id=' + id;
        });

        // For edit default service button
        $('.edit-default').click(function() {
            var id = $(this).data('id');
            window.location.href = 'edit_default.php?id=' + id;
        });

        // For delete default service button
        $('.delete-default').click(function() {
            if(confirm('Are you sure you want to delete this default service?')) {
                var id = $(this).data('id');
                window.location.href = 'delete_default.php?id=' + id;
            }
        });

        // For update status button
        $('.update-status').click(function() {
            var serviceId = $(this).data('id');
            var currentStatus = $(this).data('status');
            var nextStatus = getNextStatus(currentStatus);
            
            if (confirm('Update service status to ' + nextStatus.replace('_', ' ') + '?')) {
                window.location.href = 'update_status.php?id=' + serviceId + '&status=' + nextStatus;
            }
        });

        function getNextStatus(currentStatus) {
            switch(currentStatus) {
                case 'pending': return 'in_progress';
                case 'in_progress': return 'completed';
                default: return currentStatus;
            }
        }
    });
    </script>
</body>
</html> 