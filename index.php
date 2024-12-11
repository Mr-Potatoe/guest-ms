<?php
require_once "config/database.php";
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['admin_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lodging House Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Add Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }
        .content {
            flex: 1;
        }
        footer {
            background-color: #343a40;
            color: white;
            text-align: center;
            padding: 1rem 0;
        }
        .dashboard-card {
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            border-left: 4px solid;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Lodging House Management System</a>
            <?php if ($isLoggedIn): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="modules/admin/profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="modules/admin/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container mt-4 content">
        <?php if (!$isLoggedIn): ?>
            <!-- Login Form -->
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">Login</h4>
                        </div>
                        <div class="card-body">
                            <form action="modules/admin/login.php" method="POST">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Login</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Dashboard -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card border-primary">
                        <div class="card-body">
                            <h5 class="card-title">Available Rooms</h5>
                            <h2 class="card-text">
                                <?php
                                    $db = (new Database())->getConnection();
                                    $stmt = $db->query("SELECT COUNT(*) FROM Rooms WHERE status = 'vacant'");
                                    echo $stmt->fetchColumn();
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-success">
                        <div class="card-body">
                            <h5 class="card-title">Current Guests</h5>
                            <h2 class="card-text">
                                <?php
                                    $stmt = $db->query("SELECT COUNT(*) FROM Bookings WHERE status = 'checked_in'");
                                    echo $stmt->fetchColumn();
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-warning">
                        <div class="card-body">
                            <h5 class="card-title">Today's Check-ins</h5>
                            <h2 class="card-text">
                                <?php
                                    $stmt = $db->query("SELECT COUNT(*) FROM Bookings WHERE DATE(check_in_date) = CURDATE()");
                                    echo $stmt->fetchColumn();
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-info">
                        <div class="card-body">
                            <h5 class="card-title">Today's Check-outs</h5>
                            <h2 class="card-text">
                                <?php
                                    $stmt = $db->query("SELECT COUNT(*) FROM Bookings WHERE DATE(check_out_date) = CURDATE()");
                                    echo $stmt->fetchColumn();
                                ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Access Cards -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-3x mb-3 text-primary"></i>
                            <h5 class="card-title">Guest Management</h5>
                            <p class="card-text">Manage guest information and reservations</p>
                            <a href="modules/guests/index.php" class="btn btn-primary">Access</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-bed fa-3x mb-3 text-success"></i>
                            <h5 class="card-title">Room Management</h5>
                            <p class="card-text">View and manage room status and details</p>
                            <a href="modules/rooms/index.php" class="btn btn-success">Access</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-check fa-3x mb-3 text-warning"></i>
                            <h5 class="card-title">Bookings</h5>
                            <p class="card-text">Manage reservations and check-ins</p>
                            <a href="modules/bookings/index.php" class="btn btn-warning">Access</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-credit-card fa-3x mb-3 text-info"></i>
                            <h5 class="card-title">Payments</h5>
                            <p class="card-text">Process payments and view transactions</p>
                            <a href="modules/payments/index.php" class="btn btn-info">Access</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-concierge-bell fa-3x mb-3 text-secondary"></i>
                            <h5 class="card-title">Services</h5>
                            <p class="card-text">Manage additional services and requests</p>
                            <a href="modules/services/index.php" class="btn btn-secondary">Access</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card dashboard-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-file-invoice fa-3x mb-3 text-danger"></i>
                            <h5 class="card-title">Reports</h5>
                            <p class="card-text">Generate and view system reports</p>
                            <a href="modules/reports/index.php" class="btn btn-danger">Access</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p class="mb-0">© 2024 Lodging House Management System. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
