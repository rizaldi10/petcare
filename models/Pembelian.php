<?php
class Pembelian {
    private $conn;
    private $table_name = "pembelian";

    public $id_pembelian;
    public $id; // alias
    public $id_vendor;
    public $vendor_id; // alias
    public $no_faktur_pembelian;
    public $no_faktur; // alias
    public $tgl_pembelian;
    public $tanggal_pembelian; // alias
    public $total;
    public $total_harga; // alias
    public $status = 'completed';

    public function __construct($db) {
        $this->conn = $db;
    }

    public function generateNoFaktur() {
        $prefix = 'PBL-' . date('Ymd') . '-';
        $query = "SELECT no_faktur_pembelian FROM " . $this->table_name . " 
                  WHERE no_faktur_pembelian LIKE :prefix 
                  ORDER BY id_pembelian DESC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $searchPrefix = $prefix . '%';
        $stmt->bindParam(':prefix', $searchPrefix);
        $stmt->execute();

        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($last) {
            $num = (int)substr($last['no_faktur_pembelian'], -4) + 1;
        } else {
            $num = 1;
        }
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    public function createPurchase($id_vendor, $no_faktur, $tgl_pembelian, $items) {
        try {
            $this->conn->beginTransaction();

            $total = 0;
            foreach ($items as $item) {
                $total += (float)$item['harga_beli'] * (int)$item['jumlah'];
            }

            // 1. Simpan Header Pembelian
            $queryHeader = "INSERT INTO " . $this->table_name . " 
                (id_vendor, no_faktur_pembelian, tgl_pembelian, total, created_at)
                VALUES (:vendor, :faktur, :tgl, :total, NOW())";

            $stmtHeader = $this->conn->prepare($queryHeader);
            $stmtHeader->bindParam(':vendor', $id_vendor);
            $stmtHeader->bindParam(':faktur', $no_faktur);
            $stmtHeader->bindParam(':tgl', $tgl_pembelian);
            $stmtHeader->bindParam(':total', $total);
            $stmtHeader->execute();

            $id_pembelian = $this->conn->lastInsertId();

            // 2. Simpan Detail Item Pembelian & Tambah Stok
            $queryDetail = "INSERT INTO detail_pembelian 
                (id_pembelian, id_barang, jumlah, harga_beli)
                VALUES (:pembelian, :barang, :qty, :harga)";
            $stmtDetail = $this->conn->prepare($queryDetail);

            $queryStok = "UPDATE barang SET stok = stok + :qty, harga_beli = :harga WHERE id_barang = :barang";
            $stmtStok = $this->conn->prepare($queryStok);

            foreach ($items as $item) {
                $id_barang = (int)$item['id_barang'];
                $qty = (int)$item['jumlah'];
                $harga = (float)$item['harga_beli'];

                $stmtDetail->bindParam(':pembelian', $id_pembelian);
                $stmtDetail->bindParam(':barang', $id_barang);
                $stmtDetail->bindParam(':qty', $qty);
                $stmtDetail->bindParam(':harga', $harga);
                $stmtDetail->execute();

                $stmtStok->bindParam(':qty', $qty);
                $stmtStok->bindParam(':harga', $harga);
                $stmtStok->bindParam(':barang', $id_barang);
                $stmtStok->execute();
            }

            $this->conn->commit();
            return [
                'success' => true,
                'id_pembelian' => $id_pembelian
            ];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function readAll() {
        $query = "SELECT p.*, p.id_pembelian as id, p.no_faktur_pembelian as no_faktur, 
                         p.tgl_pembelian as tanggal_pembelian, p.total as total_harga,
                         v.nama_vendor, v.kontak as vendor_kontak
                  FROM " . $this->table_name . " p
                  JOIN vendor v ON p.id_vendor = v.id_vendor
                  ORDER BY p.id_pembelian DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT p.*, p.id_pembelian as id, p.no_faktur_pembelian as no_faktur, 
                         p.tgl_pembelian as tanggal_pembelian, p.total as total_harga,
                         v.nama_vendor, v.kontak as vendor_kontak
                  FROM " . $this->table_name . " p
                  JOIN vendor v ON p.id_vendor = v.id_vendor
                  WHERE p.id_pembelian = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDetailPembelian($pembelian_id) {
        $query = "SELECT dp.*, dp.id_barang as barang_id, b.nama_barang, b.kode_barang, (dp.jumlah * dp.harga_beli) as subtotal
                  FROM detail_pembelian dp
                  JOIN barang b ON dp.id_barang = b.id_barang
                  WHERE dp.id_pembelian = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $pembelian_id);
        $stmt->execute();
        return $stmt;
    }

    public function getTotalPembelianBulan() {
        $query = "SELECT COALESCE(SUM(total), 0) as total FROM " . $this->table_name . " 
                  WHERE MONTH(tgl_pembelian) = MONTH(CURDATE()) AND YEAR(tgl_pembelian) = YEAR(CURDATE())";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getLaporanPembelian($start_date, $end_date) {
        $query = "SELECT p.*, p.id_pembelian as id, p.no_faktur_pembelian as no_faktur, 
                         p.tgl_pembelian as tanggal_pembelian, p.total as total_harga,
                         v.nama_vendor
                  FROM " . $this->table_name . " p
                  JOIN vendor v ON p.id_vendor = v.id_vendor
                  WHERE p.tgl_pembelian BETWEEN :start_date AND :end_date
                  ORDER BY p.id_pembelian DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        return $stmt;
    }
}
