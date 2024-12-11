<?php


class Receipt {
    private $conn;
    public $receipt_id;
    public $payment_id;
    public $issued_date;
    public $total_amount;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create receipt after payment
    public function create() {
        $query = "INSERT INTO receipts (payment_id, issued_date, total_amount) 
                 VALUES (:payment_id, NOW(), :total_amount)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":payment_id", $this->payment_id);
        $stmt->bindParam(":total_amount", $this->total_amount);
        
        return $stmt->execute();
    }

    // Generate receipt PDF or HTML
    public function generateReceipt() {
        // Add receipt generation logic here
    }
}
