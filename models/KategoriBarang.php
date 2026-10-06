<?php
class KategoriBarang {
    private $conn;
    private $table_name = "kategori_barang";

    public $id_kategori;
    public $id; // alias
    public $nama_kategori;
    public $deskripsi;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " SET nama_kategori=:nama_kategori";
        $stmt = $this->conn->prepare($query);

        $name = htmlspecialchars(strip_tags($this->nama_kategori));
        $stmt->bindParam(':nama_kategori', $name);

        if ($stmt->execute()) {
            $this->id_kategori = $this->conn->lastInsertId();
            $this->id = $this->id_kategori;
            return true;
        }
        return false;
    }

    public function readAll() {
        $query = "SELECT id_kategori, id_kategori as id, nama_kategori,
                  (SELECT COUNT(*) FROM barang b WHERE b.id_kategori = k.id_kategori) as total_barang
                  FROM " . $this->table_name . " k ORDER BY nama_kategori ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id = null) {
        $targetId = $id ?? $this->id_kategori ?? $this->id;
        $query = "SELECT id_kategori, id_kategori as id, nama_kategori FROM " . $this->table_name . " WHERE id_kategori = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $targetId);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id_kategori = $row['id_kategori'];
            $this->id = $row['id_kategori'];
            $this->nama_kategori = $row['nama_kategori'];
            return $row;
        }
        return false;
    }

    public function update() {
        $targetId = $this->id_kategori ?? $this->id;
        $query = "UPDATE " . $this->table_name . " SET nama_kategori=:nama_kategori WHERE id_kategori=:id";

        $stmt = $this->conn->prepare($query);
        $name = htmlspecialchars(strip_tags($this->nama_kategori));
        $stmt->bindParam(':nama_kategori', $name);
        $stmt->bindParam(':id', $targetId);

        return $stmt->execute();
    }

    public function delete($id = null) {
        $targetId = $id ?? $this->id_kategori ?? $this->id;
        $query = "DELETE FROM " . $this->table_name . " WHERE id_kategori = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $targetId);
        return $stmt->execute();
    }
}
