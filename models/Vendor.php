<?php
class Vendor {
    private $conn;
    private $table_name = "vendor";

    public $id_vendor;
    public $id; // alias
    public $nama_vendor;
    public $kontak;
    public $telepon; // alias
    public $email; // alias
    public $alamat;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET nama_vendor=:nama_vendor, kontak=:kontak";

        $stmt = $this->conn->prepare($query);

        $name = htmlspecialchars(strip_tags($this->nama_vendor));
        $contact = !empty($this->kontak) ? $this->kontak : (!empty($this->telepon) ? $this->telepon : $this->email);

        $stmt->bindParam(':nama_vendor', $name);
        $stmt->bindParam(':kontak', $contact);

        if ($stmt->execute()) {
            $this->id_vendor = $this->conn->lastInsertId();
            $this->id = $this->id_vendor;
            return true;
        }
        return false;
    }

    public function readAll() {
        $query = "SELECT id_vendor, id_vendor as id, nama_vendor, kontak, kontak as telepon, created_at 
                  FROM " . $this->table_name . " ORDER BY nama_vendor ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id = null) {
        $targetId = $id ?? $this->id_vendor ?? $this->id;
        $query = "SELECT id_vendor, id_vendor as id, nama_vendor, kontak, kontak as telepon 
                  FROM " . $this->table_name . " WHERE id_vendor = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $targetId);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id_vendor = $row['id_vendor'];
            $this->id = $row['id_vendor'];
            $this->nama_vendor = $row['nama_vendor'];
            $this->kontak = $row['kontak'];
            $this->telepon = $row['kontak'];
            return $row;
        }
        return false;
    }

    public function update($id = null) {
        $targetId = $id ?? $this->id_vendor ?? $this->id;
        $query = "UPDATE " . $this->table_name . "
                  SET nama_vendor=:nama_vendor, kontak=:kontak
                  WHERE id_vendor=:id";

        $stmt = $this->conn->prepare($query);
        $name = htmlspecialchars(strip_tags($this->nama_vendor));
        $contact = !empty($this->kontak) ? $this->kontak : $this->telepon;

        $stmt->bindParam(':nama_vendor', $name);
        $stmt->bindParam(':kontak', $contact);
        $stmt->bindParam(':id', $targetId);

        return $stmt->execute();
    }

    public function delete($id = null) {
        $targetId = $id ?? $this->id_vendor ?? $this->id;
        $query = "DELETE FROM " . $this->table_name . " WHERE id_vendor = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $targetId);
        return $stmt->execute();
    }
}
