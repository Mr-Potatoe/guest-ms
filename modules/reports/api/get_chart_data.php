<?php
session_start();
require_once "../../../config/database.php";
require_once "../../../models/Booking.php";
require_once "../../../models/Payment.php";
require_once "../../../models/Service.php";

if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$booking = new Booking($db);
$payment = new Payment($db);
$service = new Service($db);

$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get revenue data
$revenue_data = $payment->getRevenueByDate($start_date, $end_date);
$revenue_labels = [];
$revenue_values = [];

while ($row = $revenue_data->fetch(PDO::FETCH_ASSOC)) {
    $revenue_labels[] = date('M d', strtotime($row['date']));
    $revenue_values[] = floatval($row['daily_total']);
}

// Get booking status data
$booking_statuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
$booking_values = [];

foreach ($booking_statuses as $status) {
    $query = "SELECT COUNT(*) as count FROM bookings 
              WHERE status = :status 
              AND DATE(created_at) BETWEEN :start_date AND :end_date";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $booking_values[] = intval($result['count']);
}

// Get service data
$service_types = ['room_service', 'housekeeping', 'laundry', 'foot_job', 'nail_care', 'other'];
$service_values = [];

foreach ($service_types as $type) {
    $query = "SELECT COUNT(*) as count FROM services 
              WHERE service_type = :type 
              AND DATE(created_at) BETWEEN :start_date AND :end_date";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':type', $type);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $service_values[] = intval($result['count']);
}

$response = [
    'revenue' => [
        'labels' => $revenue_labels,
        'values' => $revenue_values
    ],
    'bookings' => [
        'labels' => array_map('ucfirst', $booking_statuses),
        'values' => $booking_values
    ],
    'services' => [
        'labels' => array_map(function($type) {
            return ucfirst(str_replace('_', ' ', $type));
        }, $service_types),
        'values' => $service_values
    ]
];

header('Content-Type: application/json');
echo json_encode($response); 