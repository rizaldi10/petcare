<?php
class Repack {
    private $conn;
    private $table_name = "log_repack";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function executeRepack($id_karung, $id_eceran, $jumlah_karung, $hasil_pack, $id_admin, $catatan_susut = null) {
        try {
            $this->conn->beginTransaction();

            // 1. Ambil info karung asal
            $stmtKarung = $this->conn->prepare("SELECT * FROM barang WHERE id_barang = :id FOR UPDATE");
            $stmtKarung->bindParam(':id', $id_karung);
            $stmtKarung->execute();
            $karung = $stmtKarung->fetch(PDO::FETCH_ASSOC);

            if (!$karung || $karung['stok'] < $jumlah_karung) {
                throw new Exception("Stok karung asal tidak mencukupi (Tersedia: " . ($karung['stok'] ?? 0) . ")");
            }

            // 2. Ambil info kemasan eceran target
            $stmtEceran = $this->conn->prepare("SELECT * FROM barang WHERE id_barang = :id FOR UPDATE");
            $stmtEceran->bindParam(':id', $id_eceran);
            $stmtEceran->execute();
            $eceran = $stmtEceran->fetch(PDO::FETCH_ASSOC);

            if (!$eceran) {
                throw new Exception("Produk eceran target tidak ditemukan.");
            }

            // 3. Hitung susut (shrinkage)
            $total_gram_asal = (float)$karung['berat_gram'] * (int)$jumlah_karung;
            $total_gram_hasil = (float)$eceran['berat_gram'] * (int)$hasil_pack;
            $selisih_susut = max(0, $total_gram_asal - $total_gram_hasil);

            // 4. Update stok karung asal (kurangi)
            $stmtMin = $this->conn->prepare("UPDATE barang SET stok = stok - :qty WHERE id_barang = :id");
            $stmtMin->bindParam(':qty', $jumlah_karung);
            $stmtMin->bindParam(':id', $id_karung);
            $stmtMin->execute();

            // 5. Update stok kemasan eceran target (tambah)
            $stmtPlus = $this->conn->prepare("UPDATE barang SET stok = stok + :qty WHERE id_barang = :id");
            $stmtPlus->bindParam(':qty', $hasil_pack);
            $stmtPlus->bindParam(':id', $id_eceran);
            $stmtPlus->execute();

            // 6. Catat riwayat log_repack
            $stmtLog = $this->conn->prepare("INSERT INTO log_repack 
                (id_barang_karung_asal, id_barang_eceran_target, jumlah_karung_asal, 
                 total_hasil_eceran_pack, selisih_susut_gram, tgl_eksekusi, id_admin)
                VALUES 
                (:karung, :eceran, :jml_karung, :hasil_pack, :susut, NOW(), :admin)");

            $stmtLog->bindParam(':karung', $id_karung);
            $stmtLog->bindParam(':eceran', $id_eceran);
            $stmtLog->bindParam(':jml_karung', $jumlah_karung);
            $stmtLog->bindParam(':hasil_pack', $hasil_pack);
            $stmtLog->bindParam(':susut', $selisih_susut);
            $stmtLog->bindParam(':admin', $id_admin);
            $stmtLog->execute();

            $logId = $this->conn->lastInsertId();

            $this->conn->commit();
            return [
                'success' => true,
                'id_repack' => $logId,
                'total_gram_asal' => $total_gram_asal,
                'total_gram_hasil' => $total_gram_hasil,
                'susut_gram' => $selisih_susut
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

    public function readAllLogs() {
        $query = "SELECT lr.*, 
                         bk.nama_barang as nama_karung, bk.berat_gram as gram_karung,
                         be.nama_barang as nama_eceran, be.berat_gram as gram_eceran,
                         u.nama as nama_admin
                  FROM log_repack lr
                  JOIN barang bk ON lr.id_barang_karung_asal = bk.id_barang
                  JOIN barang be ON lr.id_barang_eceran_target = be.id_barang
                  JOIN users u ON lr.id_admin = u.id_user
                  ORDER BY lr.id_repack DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
