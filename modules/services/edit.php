<?php
session_start();
require_once "../../config/database.php";
require_once "../../models/Service.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../index.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$service = new Service($db);

$is_default = isset($_GET['type']) && $_GET['type'] === 'default';

if (isset($_GET['id'])) {
    $service->service_id = $_GET['id'];
    $service->readOne();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service->service_id = $_POST['service_id'];
    $service->service_type = $_POST['service_type'];
    $service->description = $_POST['description'];
    
    if ($is_default) {
        $service->default_price = $_POST['price'];
        if ($service->updateDefault()) {
            $_SESSION['success'] = "Default service updated successfully.";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to update default service.";
        }
    } else {
        $service->price = $_POST['price'];
        $service->status = $_POST['status'];
        if ($service->update()) {
            $_SESSION['success'] = "Service updated successfully.";
            header("Location: index.php");
            exit();
        } else {
            $_SESSION['error'] = "Failed to update service.";
        }
    }
}

$service_types = $service->getServiceTypes();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo $is_default ? 'Default ' : ''; ?>Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Edit <?php echo $is_default ? 'Default ' : ''; ?>Service</h4>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            <div class="card-body">
                <form action="edit.php<?php echo $is_default ? '?type=default' : ''; ?>" method="POST">
                    <input type="hidden" name="service_id" value="<?php echo $service->service_id; ?>">
                    
                    <div class="mb-3">
                        <label>Service Type</label>
                        <select class="form-control" name="service_type" required>
                            <?php
                            foreach ($service_types as $type) {
                                $selected = ($service->service_type === $type) ? 'selected' : '';
                                echo "<option value='{$type}' {$selected}>" . 
                                     ucwords(str_replace('_', ' ', $type)) . 
                                     "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label><?php echo $is_default ? 'Default ' : ''; ?>Price</label>
                        <input type="number" step="0.01" class="form-control" name="price" 
                               value="<?php echo $is_default ? $service->default_price : $service->price; ?>" required>
                    </div>

                    <?php if (!$is_default): ?>
                    <div class="mb-3">
                        <label>Status</label>
                        <select class="form-control" name="status" required>
                            <option value="pending" <?php echo $service->status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $service->status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $service->status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $service->status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">Update Service</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 