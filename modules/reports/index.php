<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Booking.php";
require_once "../../models/Service.php";
require_once "../../models/Payment.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$booking = new Booking($db);
$service = new Service($db);
$payment = new Payment($db);

// Get date range filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Lodging House Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Add ApexCharts -->

    <style>
        .nav-tabs .nav-link {
            color: #495057;
            background-color: transparent;
            border-color: transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            background-color: #fff;
            border-color: #dee2e6 #dee2e6 #fff;
        }
        
        .tab-content {
            padding-top: 20px;
        }
        
        .card-header-tabs {
            margin-right: -1rem;
            margin-left: -1rem;
            margin-bottom: -1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <?php include_once __DIR__ . '/../includes/alerts.php'; ?>

        <!-- Reports Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-line"></i> Reports Dashboard</h2>
            <div>
                <a href="../../index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" 
                               value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" 
                               value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary d-block">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards Row -->
        <div class="row mb-4">
            <!-- Total Bookings -->
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Bookings</h6>
                        <h2 class="mb-0">
                            <?php echo $booking->getBookingCount($start_date, $end_date); ?>
                        </h2>
                    </div>
                </div>
            </div>
            <!-- Total Revenue -->
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Revenue</h6>
                        <h2 class="mb-0">
                            ₱<?php echo number_format($payment->getTotalRevenue($start_date, $end_date), 2); ?>
                        </h2>
                    </div>
                </div>
            </div>
            <!-- Active Services -->
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Active Services</h6>
                        <h2 class="mb-0">
                            <?php echo $service->getActiveServicesCount(); ?>
                        </h2>
                    </div>
                </div>
            </div>
            <!-- Room Occupancy Rate -->
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h6 class="card-title">Room Occupancy Rate</h6>
                        <h2 class="mb-0">
                            <?php echo $booking->getOccupancyRate(); ?>%
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Reports Section -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#bookings">Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#payments">Payments</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Bookings Tab -->
                    <div class="tab-pane fade show active" id="bookings">
                        <?php include 'tabs/bookings_report.php'; ?>
                    </div>
                    <!-- Services Tab -->
                    <div class="tab-pane fade" id="services">
                        <?php include 'tabs/services_report.php'; ?>
                    </div>
                    <!-- Payments Tab -->
                    <div class="tab-pane fade" id="payments">
                        <?php include 'tabs/payments_report.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Initialize chart data
        const revenueData = {
            labels: <?php 
                $query = "SELECT DATE(payment_date) as date, SUM(amount) as daily_total 
                         FROM payments 
                         WHERE payment_date BETWEEN :start_date AND :end_date 
                         GROUP BY DATE(payment_date) 
                         ORDER BY date";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':start_date', $start_date);
                $stmt->bindParam(':end_date', $end_date);
                $stmt->execute();
                
                $labels = [];
                $values = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $labels[] = date('M d', strtotime($row['date']));
                    $values[] = floatval($row['daily_total']);
                }
                echo json_encode($labels);
            ?>,
            values: <?php echo json_encode($values); ?>
        };

        const bookingsData = {
            labels: <?php 
                $booking_statuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
                echo json_encode(array_map('ucfirst', $booking_statuses));
            ?>,
            values: <?php 
                $booking_counts = [];
                foreach ($booking_statuses as $status) {
                    $query = "SELECT COUNT(*) as count FROM bookings 
                             WHERE status = :status 
                             AND created_at BETWEEN :start_date AND :end_date";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':start_date', $start_date);
                    $stmt->bindParam(':end_date', $end_date);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $booking_counts[] = intval($result['count']);
                }
                echo json_encode($booking_counts);
            ?>
        };

        const servicesData = {
            labels: <?php 
                $service_types = ['room_service', 'housekeeping', 'laundry', 'foot_job', 'nail_care', 'other'];
                echo json_encode(array_map(function($type) {
                    return ucfirst(str_replace('_', ' ', $type));
                }, $service_types));
            ?>,
            values: <?php 
                $service_counts = [];
                foreach ($service_types as $type) {
                    $query = "SELECT COUNT(*) as count FROM services 
                             WHERE service_type = :type 
                             AND created_at BETWEEN :start_date AND :end_date";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':type', $type);
                    $stmt->bindParam(':start_date', $start_date);
                    $stmt->bindParam(':end_date', $end_date);
                    $stmt->execute();
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $service_counts[] = intval($result['count']);
                }
                echo json_encode($service_counts);
            ?>
        };
    </script>
    <script>
        // Initialize Bootstrap tabs
        document.addEventListener('DOMContentLoaded', function() {
            var triggerTabList = [].slice.call(document.querySelectorAll('a[data-bs-toggle="tab"]'));
            triggerTabList.forEach(function(triggerEl) {
                new bootstrap.Tab(triggerEl);
            });
        });
    </script>
    <script src="js/reports.js"></script>
</body>
</html> 