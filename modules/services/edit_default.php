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

if (isset($_GET['id'])) {
    $service->service_id = $_GET['id'];
    $service->readDefaultOne();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service->service_id = $_POST['service_id'];
    $service->service_type = $_POST['service_type'];
    $service->description = $_POST['description'];
    $service->default_price = $_POST['default_price'];
    
    if ($service->updateDefault()) {
        $_SESSION['success'] = "Default service updated successfully.";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update default service.";
    }
}

$service_types = $service->getServiceTypes();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Default Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Edit Default Service</h4>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                    <div class="card-body">
                        <form action="edit_default.php" method="POST">
                            <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($service->service_id); ?>">
                            
                            <div class="mb-3">
                                <label>Service Type</label>
                                <select class="form-control" name="service_type" required>
                                    <?php foreach ($service_types as $type): ?>
                                        <option value="<?php echo $type; ?>" 
                                            <?php echo ($service->service_type === $type) ? 'selected' : ''; ?>>
                                            <?php echo ucwords(str_replace('_', ' ', $type)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label>Description</label>
                                <textarea class="form-control" name="description" required><?php echo htmlspecialchars($service->description); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label>Default Price</label>
                                <input type="number" step="0.01" class="form-control" name="default_price" 
                                       value="<?php echo htmlspecialchars($service->default_price); ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Default Service</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 