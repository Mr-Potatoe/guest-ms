<?php
class Guest {
    private $conn;
    private $table_name = "Guests";

    public $guest_id;
    public $name;
    public $contact_number;
    public $id_type;
    public $id_number;
    public $email;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new guest
    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (name, contact_number, id_type, id_number, email)
                VALUES
                (:name, :contact_number, :id_type, :id_number, :email)";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->contact_number = htmlspecialchars(strip_tags($this->contact_number));
        $this->id_type = htmlspecialchars(strip_tags($this->id_type));
        $this->id_number = htmlspecialchars(strip_tags($this->id_number));
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":contact_number", $this->contact_number);
        $stmt->bindParam(":id_type", $this->id_type);
        $stmt->bindParam(":id_number", $this->id_number);
        $stmt->bindParam(":email", $this->email);

        return $stmt->execute();
    }

    // Read all guests
    public function readAll($search = "") {
        $query = "SELECT * FROM " . $this->table_name;
        
        if (!empty($search)) {
            $query .= " WHERE name LIKE :search 
                       OR contact_number LIKE :search 
                       OR email LIKE :search 
                       OR id_number LIKE :search";
        }
        
        $query .= " ORDER BY name ASC";

        $stmt = $this->conn->prepare($query);

        if (!empty($search)) {
            $searchTerm = "%{$search}%";
            $stmt->bindParam(":search", $searchTerm);
        }

        $stmt->execute();
        return $stmt;
    }

    // Read single guest
    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE guest_id = :guest_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":guest_id", $this->guest_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->name = $row['name'];
            $this->contact_number = $row['contact_number'];
            $this->id_type = $row['id_type'];
            $this->id_number = $row['id_number'];
            $this->email = $row['email'];
            return true;
        }
        return false;
    }

    // Update guest
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                SET
                    name = :name,
                    contact_number = :contact_number,
                    id_type = :id_type,
                    id_number = :id_number,
                    email = :email
                WHERE
                    guest_id = :guest_id";

        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->contact_number = htmlspecialchars(strip_tags($this->contact_number));
        $this->id_type = htmlspecialchars(strip_tags($this->id_type));
        $this->id_number = htmlspecialchars(strip_tags($this->id_number));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->guest_id = htmlspecialchars(strip_tags($this->guest_id));

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":contact_number", $this->contact_number);
        $stmt->bindParam(":id_type", $this->id_type);
        $stmt->bindParam(":id_number", $this->id_number);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":guest_id", $this->guest_id);

        return $stmt->execute();
    }

    // Delete guest
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE guest_id = :guest_id";
        $stmt = $this->conn->prepare($query);
        
        $this->guest_id = htmlspecialchars(strip_tags($this->guest_id));
        $stmt->bindParam(":guest_id", $this->guest_id);

        return $stmt->execute();
    }

    // Check if guest exists
    public function exists() {
        $query = "SELECT COUNT(*) FROM " . $this->table_name . " 
                 WHERE id_number = :id_number AND id_type = :id_type";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_number", $this->id_number);
        $stmt->bindParam(":id_type", $this->id_type);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }

    // Get guest bookings
    public function getBookings() {
        $query = "SELECT b.*, r.room_number 
                 FROM Bookings b 
                 JOIN Rooms r ON b.room_id = r.room_id 
                 WHERE b.guest_id = :guest_id 
                 ORDER BY b.check_in_date DESC";
                 
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":guest_id", $this->guest_id);
        $stmt->execute();
        
        return $stmt;
    }
}
