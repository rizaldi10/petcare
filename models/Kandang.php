<?php
class Kandang {
    private $conn;
    private $table_name = "kandang";

    public $id_kandang;
    public $nomor_kandang;
    public $ukuran;
    public $tarif_per_malam;
    public $status_tersedia = 1;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . "
                  SET nomor_kandang=:nomor_kandang, ukuran=:ukuran, 
                      tarif_per_malam=:tarif_per_malam, status_tersedia=:status_tersedia";

        $stmt = $this->conn->prepare($query);

        $status = isset($data['status_tersedia']) ? (int)$data['status_tersedia'] : 1;
        $tarif = (float)($data['tarif_per_malam'] ?? 0);

        $stmt->bindParam(':nomor_kandang', $data['nomor_kandang']);
        $stmt->bindParam(':ukuran', $data['ukuran']);
        $stmt->bindParam(':tarif_per_malam', $tarif);
        $stmt->bindParam(':status_tersedia', $status);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function readAll($only_available = false) {
        $query = "SELECT k.*, 
                  (SELECT r.id_inap FROM reservasi_inap r 
                   WHERE r.id_kandang = k.id_kandang AND r.status_inap = 'Check-In' LIMIT 1) as active_inap_id,
                  (SELECT h.nama_hewan FROM reservasi_inap r 
                   JOIN hewan_peliharaan h ON r.id_hewan = h.id_hewan 
                   WHERE r.id_kandang = k.id_kandang AND r.status_inap = 'Check-In' LIMIT 1) as active_guest_name
                  FROM " . $this->table_name . " k ";

        if ($only_available) {
            $query .= " WHERE k.status_tersedia = 1 ";
        }
        $query .= " ORDER BY k.nomor_kandang ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_kandang = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . "
                  SET nomor_kandang=:nomor_kandang, ukuran=:ukuran, 
                      tarif_per_malam=:tarif_per_malam, status_tersedia=:status_tersedia
                  WHERE id_kandang=:id";

        $stmt = $this->conn->prepare($query);
        $status = isset($data['status_tersedia']) ? (int)$data['status_tersedia'] : 1;
        $tarif = (float)($data['tarif_per_malam'] ?? 0);

        $stmt->bindParam(':nomor_kandang', $data['nomor_kandang']);
        $stmt->bindParam(':ukuran', $data['ukuran']);
        $stmt->bindParam(':tarif_per_malam', $tarif);
        $stmt->bindParam(':status_tersedia', $status);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function setStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status_tersedia = :status WHERE id_kandang = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_kandang = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getOccupancyStats() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status_tersedia = 1 THEN 1 ELSE 0 END) as tersedia,
                    SUM(CASE WHEN status_tersedia = 0 THEN 1 ELSE 0 END) as terisi
                  FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
