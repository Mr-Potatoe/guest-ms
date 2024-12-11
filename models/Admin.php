<?php
class Admin {
    private $conn;
    private $table_name = "Admins";

    public $admin_id;
    public $username;
    public $password_hash;
    public $email;
    public $role;
    public $created_at;
    public $last_login;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(password_verify($password, $row['password_hash'])) {
                // Update last login
                $this->updateLastLogin($row['admin_id']);
                
                // Set properties
                $this->admin_id = $row['admin_id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->role = $row['role'];
                return true;
            }
        }
        return false;
    }

    private function updateLastLogin($admin_id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET last_login = CURRENT_TIMESTAMP 
                 WHERE admin_id = :admin_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":admin_id", $admin_id);
        return $stmt->execute();
    }

    public function create($username, $password, $email, $role = 'staff') {
        $query = "INSERT INTO " . $this->table_name . "
                (username, password_hash, email, role)
                VALUES (:username, :password_hash, :email, :role)";

        $stmt = $this->conn->prepare($query);
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Bind values
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":role", $role);

        return $stmt->execute();
    }

    public function logAction($action) {
        $query = "INSERT INTO AdminLogs (admin_id, action) 
                 VALUES (:admin_id, :action)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":admin_id", $this->admin_id);
        $stmt->bindParam(":action", $action);
        return $stmt->execute();
    }

    public function updateProfile($email, $new_password) {
        $query = "UPDATE " . $this->table_name . " 
                  SET email = :email, password_hash = :password_hash 
                  WHERE admin_id = :admin_id";
        $stmt = $this->conn->prepare($query);

        // Hash the new password
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password_hash", $password_hash);
        $stmt->bindParam(":admin_id", $this->admin_id);

        return $stmt->execute();
    }

    public function readOne() {
        $query = "SELECT username, email, role, last_login 
                  FROM " . $this->table_name . " 
                  WHERE admin_id = :admin_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":admin_id", $this->admin_id);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->last_login = $row['last_login'];
            return true;
        }
        return false;
    }

    public function verifyPassword($password) {
        $query = "SELECT password_hash FROM " . $this->table_name . " WHERE admin_id = :admin_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":admin_id", $this->admin_id);
        $stmt->execute();
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            return password_verify($password, $row['password_hash']);
        }
        return false;
    }
}
