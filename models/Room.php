<?php
class Room {
    private $conn;
    private $table_name = "Rooms";

    public $room_id;
    public $room_number;
    public $room_type;
    public $status;
    public $price_per_night;
    public $capacity;
    public $updated_by;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new room
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (room_number, room_type, status, price_per_night, capacity, updated_by)
                VALUES
                (:room_number, :room_type, :status, :price_per_night, :capacity, :updated_by)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->room_number = htmlspecialchars(strip_tags($this->room_number));
        $this->room_type = htmlspecialchars(strip_tags($this->room_type));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->price_per_night = htmlspecialchars(strip_tags($this->price_per_night));
        $this->capacity = htmlspecialchars(strip_tags($this->capacity));

        // Bind values
        $stmt->bindParam(":room_number", $this->room_number);
        $stmt->bindParam(":room_type", $this->room_type);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":price_per_night", $this->price_per_night);
        $stmt->bindParam(":capacity", $this->capacity);
        $stmt->bindParam(":updated_by", $this->updated_by);

        return $stmt->execute();
    }

    // Read all rooms
    public function readAll($search = "") {
        // Join with bookings table to check current occupancy
        $query = "SELECT r.*, a.username as updated_by_name,
                  CASE 
                    WHEN b.status = 'checked_in' THEN 'occupied'
                    WHEN r.status = 'maintenance' THEN 'maintenance'
                    ELSE 'vacant'
                  END as status
                  FROM " . $this->table_name . " r
                  LEFT JOIN Admins a ON r.updated_by = a.admin_id
                  LEFT JOIN bookings b ON r.room_id = b.room_id 
                    AND b.status = 'checked_in'
                    AND CURRENT_DATE BETWEEN b.check_in_date AND b.check_out_date
                  WHERE r.room_number LIKE :search 
                     OR r.room_type LIKE :search
                  GROUP BY r.room_id";
        
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%{$search}%";
        $stmt->bindParam(":search", $searchTerm);
        $stmt->execute();
        
        return $stmt;
    }

    // Read single room
    public function readOne() {
        $query = "SELECT r.*, a.username as updated_by_name 
                 FROM " . $this->table_name . " r 
                 LEFT JOIN Admins a ON r.updated_by = a.admin_id 
                 WHERE r.room_id = :room_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->room_number = $row['room_number'];
            $this->room_type = $row['room_type'];
            $this->status = $row['status'];
            $this->price_per_night = $row['price_per_night'];
            $this->capacity = $row['capacity'];
            $this->updated_by = $row['updated_by'];
            return true;
        }
        return false;
    }

    // Update room
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    room_number = :room_number,
                    room_type = :room_type,
                    status = :status,
                    price_per_night = :price_per_night,
                    capacity = :capacity,
                    updated_by = :updated_by
                WHERE
                    room_id = :room_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->room_number = htmlspecialchars(strip_tags($this->room_number));
        $this->room_type = htmlspecialchars(strip_tags($this->room_type));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->price_per_night = htmlspecialchars(strip_tags($this->price_per_night));
        $this->capacity = htmlspecialchars(strip_tags($this->capacity));

        // Bind values
        $stmt->bindParam(":room_number", $this->room_number);
        $stmt->bindParam(":room_type", $this->room_type);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":price_per_night", $this->price_per_night);
        $stmt->bindParam(":capacity", $this->capacity);
        $stmt->bindParam(":updated_by", $this->updated_by);
        $stmt->bindParam(":room_id", $this->room_id);

        return $stmt->execute();
    }

    // Delete room
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE room_id = :room_id";
        $stmt = $this->conn->prepare($query);
        
        $this->room_id = htmlspecialchars(strip_tags($this->room_id));
        $stmt->bindParam(":room_id", $this->room_id);

        return $stmt->execute();
    }

    // Check if room number exists
    public function isRoomNumberExists() {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " 
                 WHERE room_number = :room_number AND room_id != :room_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":room_number", $this->room_number);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }

    // Update room status
    public function updateStatus($status) {
        $query = "UPDATE " . $this->table_name . "
                SET status = :status, updated_by = :updated_by
                WHERE room_id = :room_id";

        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":updated_by", $this->updated_by);
        $stmt->bindParam(":room_id", $this->room_id);

        return $stmt->execute();
    }

    // Get room bookings
    public function getBookings() {
        $query = "SELECT b.*, g.name as guest_name 
                 FROM Bookings b 
                 JOIN Guests g ON b.guest_id = g.guest_id 
                 WHERE b.room_id = :room_id 
                 ORDER BY b.check_in_date DESC";
                 
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":room_id", $this->room_id);
        $stmt->execute();
        
        return $stmt;
    }
}
