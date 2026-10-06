<?php
class Customer {
    private $conn;
    private $table_name = "customer";

    public $id_customer;
    public $id; // alias
    public $nama_customer;
    public $telepon;
    public $alamat;
    public $password;
    public $is_active = 1;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data = null) {
        if ($data) {
            $this->nama_customer = $data['nama_customer'] ?? '';
            $this->telepon = $data['telepon'] ?? '';
            $this->alamat = $data['alamat'] ?? '';
            $this->password = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
            $this->is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        }

        $query = "INSERT INTO " . $this->table_name . "
                  SET nama_customer=:nama_customer, telepon=:telepon, alamat=:alamat, 
                      password=:password, is_active=:is_active";

        $stmt = $this->conn->prepare($query);

        $name = htmlspecialchars(strip_tags($this->nama_customer));
        $phone = htmlspecialchars(strip_tags($this->telepon));
        $addr = htmlspecialchars(strip_tags($this->alamat));
        $pwd = !empty($this->password) ? $this->password : password_hash('password', PASSWORD_DEFAULT);
        $active = (int)$this->is_active;

        $stmt->bindParam(':nama_customer', $name);
        $stmt->bindParam(':telepon', $phone);
        $stmt->bindParam(':alamat', $addr);
        $stmt->bindParam(':password', $pwd);
        $stmt->bindParam(':is_active', $active);

        if ($stmt->execute()) {
            $this->id_customer = $this->conn->lastInsertId();
            $this->id = $this->id_customer;
            return $this->id_customer;
        }
        return false;
    }

    public function readAll() {
        $query = "SELECT c.*, c.id_customer as id, 
                  (SELECT COUNT(*) FROM hewan_peliharaan h WHERE h.id_customer = c.id_customer) as total_hewan
                  FROM " . $this->table_name . " c
                  ORDER BY c.nama_customer ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id = null) {
        $targetId = $id ?? $this->id_customer ?? $this->id;
        $query = "SELECT *, id_customer as id FROM " . $this->table_name . " WHERE id_customer = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $targetId);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id_customer = $row['id_customer'];
            $this->id = $row['id_customer'];
            $this->nama_customer = $row['nama_customer'];
            $this->telepon = $row['telepon'];
            $this->alamat = $row['alamat'];
            $this->is_active = $row['is_active'];
            return $row;
        }
        return false;
    }

    public function update($id = null, $data = null) {
        $targetId = $id ?? $this->id_customer ?? $this->id;
        if ($data) {
            $this->nama_customer = $data['nama_customer'] ?? $this->nama_customer;
            $this->telepon = $data['telepon'] ?? $this->telepon;
            $this->alamat = $data['alamat'] ?? $this->alamat;
            if (!empty($data['password'])) {
                $this->password = password_hash($data['password'], PASSWORD_DEFAULT);
            }
        }

        $query = "UPDATE " . $this->table_name . "
                  SET nama_customer=:nama_customer, telepon=:telepon, alamat=:alamat";

        if (!empty($this->password)) {
            $query .= ", password=:password";
        }
        $query .= " WHERE id_customer=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nama_customer', $this->nama_customer);
        $stmt->bindParam(':telepon', $this->telepon);
        $stmt->bindParam(':alamat', $this->alamat);
        $stmt->bindParam(':id', $targetId);

        if (!empty($this->password)) {
            $stmt->bindParam(':password', $this->password);
        }

        return $stmt->execute();
    }

    public function delete($id = null) {
        $targetId = $id ?? $this->id_customer ?? $this->id;
        $query = "DELETE FROM " . $this->table_name . " WHERE id_customer = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $targetId);
        return $stmt->execute();
    }

    public function loginPortal($telepon, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE telepon = :telepon AND is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':telepon', $telepon);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($row['password']) && password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    public function getPets($id_customer = null) {
        $targetId = $id_customer ?? $this->id_customer ?? $this->id;
        $query = "SELECT * FROM hewan_peliharaan WHERE id_customer = :id_customer ORDER BY nama_hewan ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_customer', $targetId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
