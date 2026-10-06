<?php
class Hewan {
    private $conn;
    private $table_name = "hewan_peliharaan";

    public $id_hewan;
    public $id_customer;
    public $nama_hewan;
    public $spesies;
    public $ras;
    public $tanggal_lahir;
    public $berat_badan;
    public $catatan_alergi;
    public $catatan_kebiasaan;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . "
                  SET id_customer=:id_customer, nama_hewan=:nama_hewan, spesies=:spesies,
                      ras=:ras, tanggal_lahir=:tanggal_lahir, berat_badan=:berat_badan,
                      catatan_alergi=:catatan_alergi, catatan_kebiasaan=:catatan_kebiasaan";

        $stmt = $this->conn->prepare($query);

        $tgl = !empty($data['tanggal_lahir']) ? $data['tanggal_lahir'] : null;
        $berat = !empty($data['berat_badan']) ? (float)$data['berat_badan'] : null;
        $ras = !empty($data['ras']) ? $data['ras'] : null;
        $alergi = !empty($data['catatan_alergi']) ? $data['catatan_alergi'] : null;
        $kebiasaan = !empty($data['catatan_kebiasaan']) ? $data['catatan_kebiasaan'] : null;

        $stmt->bindParam(':id_customer', $data['id_customer']);
        $stmt->bindParam(':nama_hewan', $data['nama_hewan']);
        $stmt->bindParam(':spesies', $data['spesies']);
        $stmt->bindParam(':ras', $ras);
        $stmt->bindParam(':tanggal_lahir', $tgl);
        $stmt->bindParam(':berat_badan', $berat);
        $stmt->bindParam(':catatan_alergi', $alergi);
        $stmt->bindParam(':catatan_kebiasaan', $kebiasaan);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function readAll($id_customer = null) {
        $query = "SELECT h.*, c.nama_customer, c.telepon as customer_telepon 
                  FROM " . $this->table_name . " h
                  JOIN customer c ON h.id_customer = c.id_customer ";

        if ($id_customer) {
            $query .= " WHERE h.id_customer = :id_customer ";
        }
        $query .= " ORDER BY h.id_hewan DESC";

        $stmt = $this->conn->prepare($query);
        if ($id_customer) {
            $stmt->bindParam(':id_customer', $id_customer);
        }
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT h.*, c.nama_customer, c.telepon as customer_telepon, c.alamat as customer_alamat
                  FROM " . $this->table_name . " h
                  JOIN customer c ON h.id_customer = c.id_customer
                  WHERE h.id_hewan = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . "
                  SET nama_hewan=:nama_hewan, spesies=:spesies, ras=:ras,
                      tanggal_lahir=:tanggal_lahir, berat_badan=:berat_badan,
                      catatan_alergi=:catatan_alergi, catatan_kebiasaan=:catatan_kebiasaan
                  WHERE id_hewan=:id";

        $stmt = $this->conn->prepare($query);

        $tgl = !empty($data['tanggal_lahir']) ? $data['tanggal_lahir'] : null;
        $berat = !empty($data['berat_badan']) ? (float)$data['berat_badan'] : null;
        $ras = !empty($data['ras']) ? $data['ras'] : null;
        $alergi = !empty($data['catatan_alergi']) ? $data['catatan_alergi'] : null;
        $kebiasaan = !empty($data['catatan_kebiasaan']) ? $data['catatan_kebiasaan'] : null;

        $stmt->bindParam(':nama_hewan', $data['nama_hewan']);
        $stmt->bindParam(':spesies', $data['spesies']);
        $stmt->bindParam(':ras', $ras);
        $stmt->bindParam(':tanggal_lahir', $tgl);
        $stmt->bindParam(':berat_badan', $berat);
        $stmt->bindParam(':catatan_alergi', $alergi);
        $stmt->bindParam(':catatan_kebiasaan', $kebiasaan);
        $stmt->bindParam(':id', $id);

        return $stmt->execute();
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_hewan = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
