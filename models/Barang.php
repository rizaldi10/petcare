<?php
class Barang {
    private $conn;
    private $table_name = "barang";

    public $id_barang;
    public $id; // alias
    public $id_kategori;
    public $kategori_id; // alias
    public $kode_barang;
    public $nama_barang;
    public $tipe_item = 'retail'; // 'retail', 'repack', 'jasa', 'kandang'
    public $stok = 0;
    public $berat_gram = 0;
    public $harga_beli = 0.00;
    public $harga_jual = 0.00;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data = null) {
        if ($data) {
            $this->id_kategori = $data['id_kategori'] ?? $data['kategori_id'] ?? 1;
            $this->kode_barang = $data['kode_barang'] ?? '';
            $this->nama_barang = $data['nama_barang'] ?? '';
            $this->tipe_item = $data['tipe_item'] ?? 'retail';
            $this->stok = (int)($data['stok'] ?? 0);
            $this->berat_gram = (int)($data['berat_gram'] ?? 0);
            $this->harga_beli = (float)($data['harga_beli'] ?? 0);
            $this->harga_jual = (float)($data['harga_jual'] ?? 0);
        }

        $query = "INSERT INTO " . $this->table_name . "
                  SET id_kategori=:id_kategori, kode_barang=:kode_barang, 
                      nama_barang=:nama_barang, tipe_item=:tipe_item, stok=:stok, 
                      berat_gram=:berat_gram, harga_beli=:harga_beli, harga_jual=:harga_jual";

        $stmt = $this->conn->prepare($query);

        $katId = $this->id_kategori ?? $this->kategori_id ?? 1;

        $stmt->bindParam(':id_kategori', $katId);
        $stmt->bindParam(':kode_barang', $this->kode_barang);
        $stmt->bindParam(':nama_barang', $this->nama_barang);
        $stmt->bindParam(':tipe_item', $this->tipe_item);
        $stmt->bindParam(':stok', $this->stok);
        $stmt->bindParam(':berat_gram', $this->berat_gram);
        $stmt->bindParam(':harga_beli', $this->harga_beli);
        $stmt->bindParam(':harga_jual', $this->harga_jual);

        if ($stmt->execute()) {
            $this->id_barang = $this->conn->lastInsertId();
            $this->id = $this->id_barang;
            return $this->id_barang;
        }
        return false;
    }

    public function readAll($tipe_item = null) {
        $query = "SELECT b.*, b.id_barang as id, b.id_kategori as kategori_id, k.nama_kategori 
                  FROM " . $this->table_name . " b
                  LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori ";

        if (!empty($tipe_item)) {
            $query .= " WHERE b.tipe_item = :tipe_item ";
        }
        $query .= " ORDER BY b.id_barang DESC";

        $stmt = $this->conn->prepare($query);
        if (!empty($tipe_item)) {
            $stmt->bindParam(':tipe_item', $tipe_item);
        }
        $stmt->execute();
        return $stmt;
    }

    public function getServices() {
        return $this->readAll('jasa');
    }

    public function getRetailAndRepack() {
        $query = "SELECT b.*, b.id_barang as id, b.id_kategori as kategori_id, k.nama_kategori 
                  FROM " . $this->table_name . " b
                  LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori 
                  WHERE b.tipe_item IN ('retail', 'repack')
                  ORDER BY b.nama_barang ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getRepackSources() {
        // Karung besar (berat >= 1000g, tipe retail)
        $query = "SELECT b.*, b.id_barang as id, k.nama_kategori 
                  FROM " . $this->table_name . " b
                  LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori 
                  WHERE b.tipe_item = 'retail' AND b.berat_gram >= 1000 AND b.stok > 0
                  ORDER BY b.nama_barang ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRepackTargets() {
        // Produk kemasan eceran hasil repack
        $query = "SELECT b.*, b.id_barang as id, k.nama_kategori 
                  FROM " . $this->table_name . " b
                  LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori 
                  WHERE b.tipe_item = 'repack'
                  ORDER BY b.nama_barang ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function readOne($id = null) {
        $targetId = $id ?? $this->id_barang ?? $this->id;
        $query = "SELECT b.*, b.id_barang as id, b.id_kategori as kategori_id, k.nama_kategori 
                  FROM " . $this->table_name . " b
                  LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori 
                  WHERE b.id_barang = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $targetId);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id_barang = $row['id_barang'];
            $this->id = $row['id_barang'];
            $this->id_kategori = $row['id_kategori'];
            $this->kategori_id = $row['id_kategori'];
            $this->kode_barang = $row['kode_barang'];
            $this->nama_barang = $row['nama_barang'];
            $this->tipe_item = $row['tipe_item'];
            $this->stok = $row['stok'];
            $this->berat_gram = $row['berat_gram'];
            $this->harga_beli = $row['harga_beli'];
            $this->harga_jual = $row['harga_jual'];
            return $row;
        }
        return false;
    }

    public function update($id = null, $data = null) {
        $targetId = $id ?? $this->id_barang ?? $this->id;
        if ($data) {
            $this->id_kategori = $data['id_kategori'] ?? $data['kategori_id'] ?? $this->id_kategori;
            $this->kode_barang = $data['kode_barang'] ?? $this->kode_barang;
            $this->nama_barang = $data['nama_barang'] ?? $this->nama_barang;
            $this->tipe_item = $data['tipe_item'] ?? $this->tipe_item;
            $this->stok = isset($data['stok']) ? (int)$data['stok'] : $this->stok;
            $this->berat_gram = isset($data['berat_gram']) ? (int)$data['berat_gram'] : $this->berat_gram;
            $this->harga_beli = isset($data['harga_beli']) ? (float)$data['harga_beli'] : $this->harga_beli;
            $this->harga_jual = isset($data['harga_jual']) ? (float)$data['harga_jual'] : $this->harga_jual;
        }

        $query = "UPDATE " . $this->table_name . "
                  SET id_kategori=:id_kategori, kode_barang=:kode_barang, 
                      nama_barang=:nama_barang, tipe_item=:tipe_item, stok=:stok, 
                      berat_gram=:berat_gram, harga_beli=:harga_beli, harga_jual=:harga_jual
                  WHERE id_barang=:id";

        $stmt = $this->conn->prepare($query);

        $katId = $this->id_kategori ?? $this->kategori_id ?? 1;

        $stmt->bindParam(':id_kategori', $katId);
        $stmt->bindParam(':kode_barang', $this->kode_barang);
        $stmt->bindParam(':nama_barang', $this->nama_barang);
        $stmt->bindParam(':tipe_item', $this->tipe_item);
        $stmt->bindParam(':stok', $this->stok);
        $stmt->bindParam(':berat_gram', $this->berat_gram);
        $stmt->bindParam(':harga_beli', $this->harga_beli);
        $stmt->bindParam(':harga_jual', $this->harga_jual);
        $stmt->bindParam(':id', $targetId);

        return $stmt->execute();
    }

    public function delete($id = null) {
        $targetId = $id ?? $this->id_barang ?? $this->id;
        $query = "DELETE FROM " . $this->table_name . " WHERE id_barang = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $targetId);
        return $stmt->execute();
    }

    public function updateStok($barang_id, $jumlah) {
        $query = "UPDATE " . $this->table_name . " SET stok = stok + :jumlah WHERE id_barang = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':jumlah', $jumlah);
        $stmt->bindParam(':id', $barang_id);
        return $stmt->execute();
    }

    public function getTotalBarang() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE tipe_item IN ('retail', 'repack')";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getStokMenipis($threshold = 5) {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE tipe_item IN ('retail', 'repack') AND stok <= :threshold";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':threshold', $threshold);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function search($keyword, $tipe_item = null) {
        $query = "SELECT b.*, b.id_barang as id, b.id_kategori as kategori_id, k.nama_kategori 
                  FROM " . $this->table_name . " b
                  LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori 
                  WHERE (b.nama_barang LIKE :keyword OR b.kode_barang LIKE :keyword) ";

        if ($tipe_item) {
            $query .= " AND b.tipe_item = :tipe_item ";
        }
        $query .= " ORDER BY b.nama_barang ASC";

        $stmt = $this->conn->prepare($query);
        $term = "%{$keyword}%";
        $stmt->bindParam(':keyword', $term);
        if ($tipe_item) {
            $stmt->bindParam(':tipe_item', $tipe_item);
        }
        $stmt->execute();
        return $stmt;
    }
}
