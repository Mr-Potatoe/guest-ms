<?php
require_once __DIR__ . '/Service.php';

class Payment {
    private $conn;
    private $table_name = "payments";

    // Properties
    public $payment_id;
    public $booking_id;
    public $payment_date;
    public $amount;
    public $payment_type;
    public $payment_method;
    public $processed_by;
    public $processed_by_name;
    public $booking_total;
    public $remaining_balance;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create payment
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (booking_id, payment_date, amount, payment_type, 
                 payment_method, processed_by)
                VALUES
                (:booking_id, :payment_date, :amount, :payment_type,
                 :payment_method, :processed_by)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->booking_id = htmlspecialchars(strip_tags($this->booking_id));
        $this->amount = htmlspecialchars(strip_tags($this->amount));
        $this->payment_type = htmlspecialchars(strip_tags($this->payment_type));
        $this->payment_method = htmlspecialchars(strip_tags($this->payment_method));

        // Bind values
        $stmt->bindParam(":booking_id", $this->booking_id);
        $stmt->bindParam(":payment_date", $this->payment_date);
        $stmt->bindParam(":amount", $this->amount);
        $stmt->bindParam(":payment_type", $this->payment_type);
        $stmt->bindParam(":payment_method", $this->payment_method);
        $stmt->bindParam(":processed_by", $this->processed_by);

        return $stmt->execute();
    }

    // Read all payments for a booking
    public function readByBooking($booking_id) {
        $query = "SELECT p.*, a.username as processed_by_name
                FROM " . $this->table_name . " p
                LEFT JOIN Admins a ON p.processed_by = a.admin_id
                WHERE p.booking_id = :booking_id
                ORDER BY p.payment_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();

        return $stmt;
    }

    // Get total paid amount for a booking
    public function getTotalPaidAmount($booking_id) {
        $query = "SELECT SUM(amount) as total_paid
                FROM " . $this->table_name . "
                WHERE booking_id = :booking_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total_paid'] ?? 0;
    }

    // Calculate remaining balance
    public function getRemainingBalance($booking_id) {
        // Get booking total
        $query = "SELECT total_price FROM Bookings WHERE booking_id = :booking_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();
        $booking_total = $stmt->fetchColumn();

        // Get service charges
        $service = new Service($this->conn);
        $service_total = $service->getTotalServiceCharges($booking_id);

        // Get total payments made
        $query = "SELECT COALESCE(SUM(amount), 0) FROM Payments WHERE booking_id = :booking_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();
        $paid_amount = $stmt->fetchColumn();

        return ($booking_total + $service_total) - $paid_amount;
    }

    // Add these methods to your existing Payment class
    public function getPaymentsReport($start_date, $end_date) {
        $query = "SELECT p.*, a.username as admin_name, 
                        b.booking_id, b.total_price as booking_total,
                        g.name as guest_name, r.room_number
                 FROM " . $this->table_name . " p
                 LEFT JOIN admins a ON p.processed_by = a.admin_id
                 LEFT JOIN bookings b ON p.booking_id = b.booking_id
                 LEFT JOIN guests g ON b.guest_id = g.guest_id
                 LEFT JOIN rooms r ON b.room_id = r.room_id
                 WHERE DATE(p.payment_date) BETWEEN :start_date AND :end_date
                 ORDER BY p.payment_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();

        return $stmt;
    }

    public function getTotalPayments($start_date, $end_date) {
        $query = "SELECT COALESCE(SUM(amount), 0) as total 
                  FROM " . $this->table_name . "
                  WHERE DATE(payment_date) BETWEEN :start_date AND :end_date
                  AND payment_type IN ('partial', 'full')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function getRevenueByDate($start_date, $end_date) {
        $query = "SELECT DATE(payment_date) as date, SUM(amount) as daily_total 
                 FROM " . $this->table_name . " 
                 WHERE payment_date BETWEEN :start_date AND :end_date 
                 GROUP BY DATE(payment_date) 
                 ORDER BY date";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();

        return $stmt;
    }

    public function getTotalRevenue($start_date, $end_date) {
        $query = "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM " . $this->table_name . " 
                 WHERE DATE(payment_date) BETWEEN :start_date AND :end_date";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return floatval($row['total']);
    }

    // Add new method to get payment statistics
    public function getPaymentStatistics($start_date, $end_date) {
        $query = "SELECT 
                    payment_method,
                    COUNT(*) as count,
                    SUM(amount) as total_amount,
                    AVG(amount) as average_amount,
                    MIN(amount) as min_amount,
                    MAX(amount) as max_amount
                  FROM " . $this->table_name . "
                  WHERE DATE(payment_date) BETWEEN :start_date AND :end_date
                  GROUP BY payment_method";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        return $stmt;
    }
}
