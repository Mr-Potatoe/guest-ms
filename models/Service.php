<?php
class Service {
    private $conn;
    private $table_name = "Services";

    // Properties
    public $service_id;
    public $booking_id;
    public $service_type;
    public $description;
    public $price;
    public $status;
    public $created_by;
    public $created_at;
    public $updated_at;
    public $default_price;
    public $is_default;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create service
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (booking_id, service_type, description, price, status, created_by)
                VALUES
                (:booking_id, :service_type, :description, :price, 'pending', :created_by)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->booking_id = htmlspecialchars(strip_tags($this->booking_id));
        $this->service_type = htmlspecialchars(strip_tags($this->service_type));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->price = htmlspecialchars(strip_tags($this->price));
        $this->created_by = htmlspecialchars(strip_tags($this->created_by));

        // Bind values
        $stmt->bindParam(":booking_id", $this->booking_id);
        $stmt->bindParam(":service_type", $this->service_type);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":created_by", $this->created_by);

        return $stmt->execute();
    }

    // Read all services
    public function readAll() {
        $query = "SELECT s.*, b.guest_id, g.name as guest_name, a.username as created_by_name
                FROM " . $this->table_name . " s
                JOIN Bookings b ON s.booking_id = b.booking_id
                JOIN Guests g ON b.guest_id = g.guest_id
                LEFT JOIN Admins a ON s.created_by = a.admin_id
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Read services by booking
    public function readByBooking($booking_id) {
        $query = "SELECT s.*, b.guest_id, g.name as guest_name, a.username as created_by_name
                FROM " . $this->table_name . " s
                JOIN bookings b ON s.booking_id = b.booking_id
                JOIN guests g ON b.guest_id = g.guest_id
                LEFT JOIN admins a ON s.created_by = a.admin_id
                WHERE s.booking_id = :booking_id
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();

        return $stmt;
    }

    // Read one service
    public function readOne() {
        $query = "SELECT s.*, b.guest_id, g.name as guest_name, a.username as created_by_name
                FROM " . $this->table_name . " s
                JOIN Bookings b ON s.booking_id = b.booking_id
                JOIN Guests g ON b.guest_id = g.guest_id
                LEFT JOIN Admins a ON s.created_by = a.admin_id
                WHERE s.service_id = :service_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":service_id", $this->service_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->booking_id = $row['booking_id'];
            $this->service_type = $row['service_type'];
            $this->description = $row['description'];
            $this->price = $row['price'];
            $this->status = $row['status'];
            $this->created_by = $row['created_by'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        return false;
    }

    // Update service
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET service_type = :service_type,
                    description = :description,
                    price = :price,
                    updated_at = CURRENT_TIMESTAMP
                WHERE service_id = :service_id";

        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(":service_type", $this->service_type);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":service_id", $this->service_id);

        return $stmt->execute();
    }

    // Update service status
    public function updateStatus($status) {
        $query = "UPDATE " . $this->table_name . "
                SET status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE service_id = :service_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":service_id", $this->service_id);

        return $stmt->execute();
    }

    public function getTotalServiceCharges($booking_id) {
        $query = "SELECT SUM(price) as total 
                  FROM " . $this->table_name . " 
                  WHERE booking_id = :booking_id 
                  AND status = 'completed'";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    // Read default services
    public function readDefaultServices() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE is_default = 1 
                  ORDER BY service_type";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Create default service
    public function createDefault() {
        $query = "INSERT INTO " . $this->table_name . "
                  (service_type, description, price, default_price, is_default, created_by, booking_id)
                  VALUES
                  (:service_type, :description, :default_price, :default_price, TRUE, :created_by, NULL)";
                  
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->service_type = htmlspecialchars(strip_tags($this->service_type));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->default_price = htmlspecialchars(strip_tags($this->default_price));
        
        // Bind values
        $stmt->bindParam(":service_type", $this->service_type);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":default_price", $this->default_price);
        $stmt->bindParam(":created_by", $this->created_by);
        
        return $stmt->execute();
    }

    public function getDefaultServices() {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE is_default = TRUE 
                  ORDER BY service_type";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function createFromDefault($default_id, $booking_id) {
        // Get default service details
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE service_id = :default_id AND is_default = TRUE";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":default_id", $default_id);
        $stmt->execute();
        $default = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($default) {
            $this->booking_id = $booking_id;
            $this->service_type = $default['service_type'];
            $this->description = $default['description'];
            $this->price = $default['default_price'];
            return $this->create();
        }
        return false;
    }

 

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE service_id = :service_id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":service_id", $this->service_id);
        
        return $stmt->execute();
    }

    public function getServiceTypes() {
        // Get all unique service types from the services table
        $query = "SELECT DISTINCT service_type FROM " . $this->table_name;
        
        // If no service types exist, return default types
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $types = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $types[] = $row['service_type'];
        }
        
        // If no types exist, provide default types
        if (empty($types)) {
            $types = [
                'room_service',
                'housekeeping',
                'laundry',
                'maintenance',
                'other'
            ];
        }
        
        // Sort alphabetically
        sort($types);
        return $types;
    }

    public function addServiceType($type) {
        // First check if type already exists
        $query = "SELECT service_type FROM " . $this->table_name . " 
                  WHERE service_type = :type AND is_default = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":type", $type);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return false; // Type already exists
        }
        
        // Add a default entry for the new service type
        $query = "INSERT INTO " . $this->table_name . " 
                  (service_type, description, price, is_default, default_price) 
                  VALUES (:type, 'Default " . ucwords(str_replace('_', ' ', $type)) . "', 0, 1, 0)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":type", $type);
        
        return $stmt->execute();
    }

    public function getServicesReport($start_date, $end_date) {
        $query = "SELECT s.*, b.guest_id, g.name as guest_name, a.username as admin_name
                  FROM " . $this->table_name . " s
                  LEFT JOIN Bookings b ON s.booking_id = b.booking_id
                  LEFT JOIN Guests g ON b.guest_id = g.guest_id
                  LEFT JOIN Admins a ON s.created_by = a.admin_id
                  WHERE DATE(s.created_at) BETWEEN :start_date AND :end_date
                  ORDER BY s.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        return $stmt;
    }

    public function getServiceRevenueByType($start_date, $end_date) {
        $query = "SELECT service_type, 
                  COUNT(*) as count,
                  SUM(price) as total_revenue
                  FROM " . $this->table_name . "
                  WHERE DATE(created_at) BETWEEN :start_date AND :end_date
                  AND status = 'completed'
                  GROUP BY service_type";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        return $stmt;
    }

    public function getServiceStatusCounts($start_date, $end_date) {
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

    public function getTotalRevenue($start_date, $end_date) {
        $query = "SELECT COALESCE(SUM(price), 0) as total 
                  FROM " . $this->table_name . "
                  WHERE DATE(created_at) BETWEEN :start_date AND :end_date
                  AND status = 'completed'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function getActiveServicesCount() {
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table_name . "
                  WHERE status IN ('pending', 'in_progress')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    public function readDefaultOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE service_id = :service_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":service_id", $this->service_id);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->service_type = $row['service_type'];
            $this->description = $row['description'];
            $this->default_price = $row['default_price'];
            return true;
        }
        return false;
    }

    public function updateDefault() {
        $query = "UPDATE " . $this->table_name . "
                  SET service_type = :service_type,
                      description = :description,
                      default_price = :default_price,
                      price = :default_price
                  WHERE service_id = :service_id AND is_default = TRUE";
                  
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":service_type", $this->service_type);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":default_price", $this->default_price);
        $stmt->bindParam(":service_id", $this->service_id);
        
        return $stmt->execute();
    }

    public function deleteDefault() {
        $query = "DELETE FROM " . $this->table_name . "
                  WHERE service_id = :service_id AND is_default = TRUE";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":service_id", $this->service_id);
        
        return $stmt->execute();
    }
}
