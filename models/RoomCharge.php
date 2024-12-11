class RoomCharge {
    private $conn;
    public $charge_id;
    public $booking_id;
    public $charge_description;
    public $amount;
    public $charge_date;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Add new room charge
    public function create() {
        $query = "INSERT INTO roomcharges (booking_id, charge_description, amount, charge_date) 
                 VALUES (:booking_id, :charge_description, :amount, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":booking_id", $this->booking_id);
        $stmt->bindParam(":charge_description", $this->charge_description);
        $stmt->bindParam(":amount", $this->amount);
        
        return $stmt->execute();
    }

    // Get all charges for a booking
    public function getBookingCharges($booking_id) {
        $query = "SELECT * FROM roomcharges WHERE booking_id = :booking_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();
        
        return $stmt;
    }
} 