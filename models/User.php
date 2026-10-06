<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id_user;
    public $id; // alias for id_user
    public $username;
    public $password;
    public $nama;
    public $nama_lengkap; // alias for nama
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT id_user, username, password, nama, role 
                  FROM " . $this->table_name . " 
                  WHERE username = :username LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password'])) {
                $this->id_user = $row['id_user'];
                $this->id = $row['id_user'];
                $this->username = $row['username'];
                $this->nama = $row['nama'];
                $this->nama_lengkap = $row['nama'];
                $this->role = $row['role'];
                return true;
            }
        }
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET username=:username, password=:password, nama=:nama, role=:role";

        $stmt = $this->conn->prepare($query);

        $name = !empty($this->nama) ? $this->nama : $this->nama_lengkap;
        $hashed = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':password', $hashed);
        $stmt->bindParam(':nama', $name);
        $stmt->bindParam(':role', $this->role);

        if ($stmt->execute()) {
            $this->id_user = $this->conn->lastInsertId();
            $this->id = $this->id_user;
            return true;
        }
        return false;
    }

    public function readAll() {
        $query = "SELECT id_user, id_user as id, username, nama, nama as nama_lengkap, role, created_at 
                  FROM " . $this->table_name . " 
                  ORDER BY nama ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    public function getGroomers() {
        $query = "SELECT id_user, id_user as id, nama, username 
                  FROM " . $this->table_name . " 
                  WHERE role = 'groomer' 
                  ORDER BY nama ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne($id = null) {
        $targetId = $id ?? $this->id_user ?? $this->id;
        $query = "SELECT id_user, id_user as id, username, nama, nama as nama_lengkap, role, created_at 
                  FROM " . $this->table_name . " 
                  WHERE id_user = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $targetId);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id_user = $row['id_user'];
            $this->id = $row['id_user'];
            $this->username = $row['username'];
            $this->nama = $row['nama'];
            $this->nama_lengkap = $row['nama'];
            $this->role = $row['role'];
            return $row;
        }
        return false;
    }

    public function update() {
        $targetId = $this->id_user ?? $this->id;
        $name = !empty($this->nama) ? $this->nama : $this->nama_lengkap;

        $query = "UPDATE " . $this->table_name . "
                  SET username=:username, nama=:nama, role=:role
                  WHERE id_user=:id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':nama', $name);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':id', $targetId);

        return $stmt->execute();
    }

    public function delete($id = null) {
        $targetId = $id ?? $this->id_user ?? $this->id;
        $query = "DELETE FROM " . $this->table_name . " WHERE id_user = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $targetId);
        return $stmt->execute();
    }

    public function changePassword($new_password, $id = null) {
        $targetId = $id ?? $this->id_user ?? $this->id;
        $query = "UPDATE " . $this->table_name . " SET password=:password WHERE id_user=:id";

        $stmt = $this->conn->prepare($query);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':id', $targetId);

        return $stmt->execute();
    }
}
