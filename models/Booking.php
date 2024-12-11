<?php
class Booking {
    private $conn;
    private $table_name = "Bookings";

    public $booking_id;
    public $guest_id;
    public $room_id;
    public $check_in_date;
    public $check_out_date;
    public $status; // pending, confirmed, checked_in, checked_out, cancelled
    public $total_price;
    public $created_by;
    public $created_by_name;
    public $handled_by;
    public $created_at;
    public $updated_at;

    private $validTransitions = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['checked_in', 'cancelled'],
        'checked_in' => ['checked_out'],
        'checked_out' => [],
        'cancelled' => []
    ];

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new booking
    public function create() {
        // First, check if room is available for the dates
        if (!$this->isRoomAvailable()) {
            return false;
        }

        $query = "INSERT INTO " . $this->table_name . "
                (guest_id, room_id, check_in_date, check_out_date, 
                 status, total_price, created_by, handled_by)
                VALUES
                (:guest_id, :room_id, :check_in_date, :check_out_date, 
                 :status, :total_price, :created_by, :handled_by)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->guest_id = htmlspecialchars(strip_tags($this->guest_id));
        $this->room_id = htmlspecialchars(strip_tags($this->room_id));
        $this->check_in_date = htmlspecialchars(strip_tags($this->check_in_date));
        $this->check_out_date = htmlspecialchars(strip_tags($this->check_out_date));
        // $this->status = 'pending'; // Always start with pending
        $this->total_price = floatval($this->total_price);
        $this->created_by = htmlspecialchars(strip_tags($this->created_by));
        $this->handled_by = htmlspecialchars(strip_tags($this->handled_by));

        // Bind values
        $stmt->bindParam(":guest_id", $this->guest_id);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":check_in_date", $this->check_in_date);
        $stmt->bindParam(":check_out_date", $this->check_out_date);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":total_price", $this->total_price);
        $stmt->bindParam(":created_by", $this->created_by);
        $stmt->bindParam(":handled_by", $this->handled_by);

        if($stmt->execute()) {
            // Update room status if booking is confirmed
            if($this->status == 'confirmed' || $this->status == 'checked_in') {
                $this->updateRoomStatus('occupied');
            }
            return true;
        }
        return false;
    }

    // Read all bookings
    public function readAll($search = "", $filter = []) {
        $query = "SELECT b.*, g.name as guest_name, r.room_number, r.room_type,
                         a.username as created_by_name
                 FROM " . $this->table_name . " b
                 JOIN Guests g ON b.guest_id = g.guest_id
                 JOIN Rooms r ON b.room_id = r.room_id
                 LEFT JOIN Admins a ON b.created_by = a.admin_id
                 WHERE 1=1";

        // Add search condition
        if (!empty($search)) {
            $query .= " AND (g.name LIKE :search OR r.room_number LIKE :search OR a.username LIKE :search)";
        }

        // Add filter conditions
        if (!empty($filter['status'])) {
            $query .= " AND b.status = :status";
        }
        if (!empty($filter['date_from'])) {
            $query .= " AND b.check_in_date >= :date_from";
        }
        if (!empty($filter['date_to'])) {
            $query .= " AND b.check_in_date <= :date_to";
        }
        if (!empty($filter['guest_id'])) {
            $query .= " AND b.guest_id = :guest_id";
        }
        if (!empty($filter['room_id'])) {
            $query .= " AND b.room_id = :room_id";
        }

        $query .= " ORDER BY b.check_in_date DESC";

        $stmt = $this->conn->prepare($query);

        // Bind search parameter
        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $stmt->bindParam(":search", $searchTerm);
        }

        // Bind filter parameters
        if (!empty($filter['status'])) {
            $stmt->bindParam(":status", $filter['status']);
        }
        if (!empty($filter['date_from'])) {
            $stmt->bindParam(":date_from", $filter['date_from']);
        }
        if (!empty($filter['date_to'])) {
            $stmt->bindParam(":date_to", $filter['date_to']);
        }
        if (!empty($filter['guest_id'])) {
            $stmt->bindParam(":guest_id", $filter['guest_id']);
        }
        if (!empty($filter['room_id'])) {
            $stmt->bindParam(":room_id", $filter['room_id']);
        }

        $stmt->execute();
        return $stmt;
    }

    // Read single booking
    public function readOne() {
        $query = "SELECT b.*, g.name as guest_name, r.room_number, r.room_type,
                         a.username as created_by_name
                 FROM " . $this->table_name . " b
                 JOIN Guests g ON b.guest_id = g.guest_id
                 JOIN Rooms r ON b.room_id = r.room_id
                 LEFT JOIN Admins a ON b.created_by = a.admin_id
                 WHERE b.booking_id = :booking_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $this->booking_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->guest_id = $row['guest_id'];
            $this->room_id = $row['room_id'];
            $this->check_in_date = $row['check_in_date'];
            $this->check_out_date = $row['check_out_date'];
            $this->status = $row['status'];
            $this->total_price = $row['total_price'];
            $this->created_by = $row['created_by'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Update booking
    public function update() {
        // Check if dates are being changed and if room is available
        if (!$this->isRoomAvailable(true)) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                SET
                    guest_id = :guest_id,
                    room_id = :room_id,
                    check_in_date = :check_in_date,
                    check_out_date = :check_out_date,
                    status = :status,
                    total_price = :total_price,
                    updated_at = CURRENT_TIMESTAMP
                WHERE
                    booking_id = :booking_id";

        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(":guest_id", $this->guest_id);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":check_in_date", $this->check_in_date);
        $stmt->bindParam(":check_out_date", $this->check_out_date);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":total_price", $this->total_price);
        $stmt->bindParam(":booking_id", $this->booking_id);

        if($stmt->execute()) {
            // Update room status based on booking status
            if($this->status == 'confirmed' || $this->status == 'checked_in') {
                $this->updateRoomStatus('occupied');
            } elseif($this->status == 'checked_out' || $this->status == 'cancelled') {
                $this->updateRoomStatus('vacant');
            }
            return true;
        }
        return false;
    }

    // Check if room is available for the dates
    private function isRoomAvailable($isUpdate = false) {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . "
                WHERE room_id = :room_id
                AND status IN ('confirmed', 'checked_in')
                AND (
                    (check_in_date BETWEEN :check_in_date AND :check_out_date)
                    OR (check_out_date BETWEEN :check_in_date AND :check_out_date)
                    OR (:check_in_date BETWEEN check_in_date AND check_out_date)
                )";

        if ($isUpdate) {
            $query .= " AND booking_id != :booking_id";
        }

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":check_in_date", $this->check_in_date);
        $stmt->bindParam(":check_out_date", $this->check_out_date);
        
        if ($isUpdate) {
            $stmt->bindParam(":booking_id", $this->booking_id);
        }

        $stmt->execute();
        return $stmt->fetchColumn() == 0;
    }

    // Update room status
    private function updateRoomStatus($status) {
        $query = "UPDATE Rooms 
                  SET status = :status,
                      updated_by = :admin_id 
                  WHERE room_id = :room_id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->bindParam(":admin_id", $this->created_by);
        return $stmt->execute();
    }

    // Calculate total price
    public function calculateTotalPrice() {
        $query = "SELECT price_per_night FROM Rooms WHERE room_id = :room_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->execute();
        
        $price_per_night = $stmt->fetchColumn();
        $check_in = new DateTime($this->check_in_date);
        $check_out = new DateTime($this->check_out_date);
        $nights = $check_out->diff($check_in)->days;
        
        return $price_per_night * $nights;
    }

    // Update booking status
    public function updateStatus($newStatus) {
        // Validate that the new status is one of the allowed enum values
        $validStatuses = ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
        
        if (!in_array($newStatus, $validStatuses)) {
            return false;
        }

        if (!$this->isValidStatusTransition($newStatus)) {
            return false;
        }

        $query = "UPDATE " . $this->table_name . "
                SET status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE booking_id = :booking_id";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":status", $newStatus);
        $stmt->bindParam(":booking_id", $this->booking_id);

        if($stmt->execute()) {
            // Update room status based on booking status
            if($newStatus == 'checked_in') {
                $this->updateRoomStatus('occupied');
            } elseif($newStatus == 'checked_out' || $newStatus == 'cancelled') {
                $this->updateRoomStatus('vacant');
            }
            return true;
        }
        return false;
    }

    // Add this new method to validate status transitions
    private function isValidStatusTransition($newStatus) {
        // Get current status from database
        $query = "SELECT status FROM " . $this->table_name . " WHERE booking_id = :booking_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $this->booking_id);
        $stmt->execute();
        
        $currentStatus = $stmt->fetchColumn();
        
        // Check if the transition is allowed
        if (!isset($this->validTransitions[$currentStatus])) {
            return false;
        }
        
        return in_array($newStatus, $this->validTransitions[$currentStatus]);
    }

    public function readActiveBookings() {
        $query = "SELECT b.booking_id, g.name as guest_name 
                  FROM " . $this->table_name . " b
                  JOIN Guests g ON b.guest_id = g.guest_id
                  WHERE b.status IN ('confirmed', 'checked_in')
                  ORDER BY b.booking_id DESC";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getBookingsReport($start_date, $end_date) {
        $query = "SELECT b.booking_id, b.check_in_date, b.check_out_date, 
                         b.status, b.total_price, g.name as guest_name, 
                         r.room_number
                  FROM " . $this->table_name . " b
                  JOIN guests g ON b.guest_id = g.guest_id
                  JOIN rooms r ON b.room_id = r.room_id
                  WHERE DATE(b.created_at) BETWEEN :start_date AND :end_date
                  ORDER BY b.booking_id DESC";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":start_date", $start_date);
            $stmt->bindParam(":end_date", $end_date);
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            error_log("Error in getBookingsReport: " . $e->getMessage());
            throw $e;
        }
    }

    public function getBookingCount($start_date, $end_date) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                  WHERE DATE(created_at) BETWEEN :start_date AND :end_date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    public function getTotalRevenue($start_date, $end_date) {
        $query = "SELECT COALESCE(SUM(total_price), 0) as total 
                  FROM " . $this->table_name . "
                  WHERE DATE(created_at) BETWEEN :start_date AND :end_date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function getOccupancyRate() {
        $query = "SELECT 
                  (COUNT(CASE WHEN status = 'checked_in' THEN 1 END) * 100.0 / 
                   COUNT(*)) as occupancy_rate
                  FROM rooms 
                  WHERE status != 'maintenance'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return round($row['occupancy_rate'], 1);
    }

    public function getBookingStatusCounts($start_date, $end_date) {
        $query = "SELECT status, COUNT(*) as count
                  FROM " . $this->table_name . "
                  WHERE DATE(created_at) BETWEEN :start_date AND :end_date
                  GROUP BY status";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        return $stmt;
    }

    public function getBookingRevenueByDate($start_date, $end_date) {
        $query = "SELECT DATE(created_at) as booking_date,
                  COUNT(*) as count,
                  SUM(total_price) as total_revenue
                  FROM " . $this->table_name . "
                  WHERE DATE(created_at) BETWEEN :start_date AND :end_date
                  AND status != 'cancelled'
                  GROUP BY DATE(created_at)
                  ORDER BY booking_date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        return $stmt;
    }

    public function getStatusCount($status, $start_date, $end_date) {
        $query = "SELECT COUNT(*) as count FROM bookings 
                  WHERE status = :status 
                  AND DATE(created_at) BETWEEN :start_date AND :end_date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }
}
