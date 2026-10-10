<?php
class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Railway MySQL variables take precedence; DB_* also works for other hosts.
        $this->host = getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: 'localhost');
        $this->port = getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: '3306');
        $this->db_name = getenv('MYSQLDATABASE') ?: (getenv('DB_DATABASE') ?: 'petcare_db');
        $this->username = getenv('MYSQLUSER') ?: (getenv('DB_USERNAME') ?: 'root');
        $this->password = getenv('MYSQLPASSWORD') ?: (getenv('DB_PASSWORD') ?: '');
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                )
            );
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
