<?php
class Inap {
    private $conn;
    private $table_name = "reservasi_inap";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createBooking($data) {
        $query = "INSERT INTO " . $this->table_name . "
                  SET id_hewan=:id_hewan, id_kandang=:id_kandang, tgl_masuk=:tgl_masuk,
                      tgl_estimasi_keluar=:tgl_estimasi_keluar, opsi_pakan=:opsi_pakan,
                      uang_muka_dp=:uang_muka_dp, status_inap=:status_inap";

        $stmt = $this->conn->prepare($query);

        $status = $data['status_inap'] ?? 'Booking';
        $dp = (float)($data['uang_muka_dp'] ?? 0);

        $stmt->bindParam(':id_hewan', $data['id_hewan']);
        $stmt->bindParam(':id_kandang', $data['id_kandang']);
        $stmt->bindParam(':tgl_masuk', $data['tgl_masuk']);
        $stmt->bindParam(':tgl_estimasi_keluar', $data['tgl_estimasi_keluar']);
        $stmt->bindParam(':opsi_pakan', $data['opsi_pakan']);
        $stmt->bindParam(':uang_muka_dp', $dp);
        $stmt->bindParam(':status_inap', $status);

        if ($stmt->execute()) {
            $id = $this->conn->lastInsertId();
            if ($status === 'Check-In') {
                // Update status kandang menjadi tidak tersedia
                $upd = $this->conn->prepare("UPDATE kandang SET status_tersedia = 0 WHERE id_kandang = :id_kandang");
                $upd->bindParam(':id_kandang', $data['id_kandang']);
                $upd->execute();
            }
            return $id;
        }
        return false;
    }

    public function checkIn($id_inap) {
        $row = $this->readOne($id_inap);
        if (!$row) return false;

        $query = "UPDATE " . $this->table_name . " 
                  SET status_inap = 'Check-In', tgl_masuk = NOW() 
                  WHERE id_inap = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_inap);

        if ($stmt->execute()) {
            $upd = $this->conn->prepare("UPDATE kandang SET status_tersedia = 0 WHERE id_kandang = :id_kandang");
            $upd->bindParam(':id_kandang', $row['id_kandang']);
            $upd->execute();
            return true;
        }
        return false;
    }

    public function calculateBill($id_inap, $hourly_rate = 15000) {
        $row = $this->readOne($id_inap);
        if (!$row) return null;

        $tgl_masuk = strtotime($row['tgl_masuk']);
        $tgl_estimasi = strtotime($row['tgl_estimasi_keluar']);
        $now = time();

        // Hitung estimasi hari inap (minimal 1 hari)
        $diff_sec = max(3600, $tgl_estimasi - $tgl_masuk);
        $durasi_hari = max(1, ceil($diff_sec / 86400));
        $biaya_kamar = $durasi_hari * (float)$row['tarif_per_malam'];

        // Tambahan pakan jika toko
        $biaya_pakan = ($row['opsi_pakan'] === 'Disediakan_Toko') ? ($durasi_hari * 20000) : 0;

        // Hitung denda overstay jika melebihi estimasi keluar
        $denda_overstay = 0;
        $jam_overstay = 0;
        if ($now > $tgl_estimasi) {
            $selisih = $now - $tgl_estimasi;
            $jam_overstay = ceil($selisih / 3600);
            $denda_overstay = $jam_overstay * (float)$hourly_rate;
        }

        $total_kotor = $biaya_kamar + $biaya_pakan + $denda_overstay;
        $dp = (float)$row['uang_muka_dp'];
        $sisa_bayar = max(0, $total_kotor - $dp);

        return [
            'durasi_hari' => $durasi_hari,
            'tarif_per_malam' => (float)$row['tarif_per_malam'],
            'biaya_kamar' => $biaya_kamar,
            'biaya_pakan' => $biaya_pakan,
            'jam_overstay' => $jam_overstay,
            'denda_overstay' => $denda_overstay,
            'uang_muka_dp' => $dp,
            'total_biaya_akhir' => $total_kotor,
            'sisa_bayar' => $sisa_bayar
        ];
    }

    public function checkOut($id_inap, $final_total = null, $denda = null) {
        $row = $this->readOne($id_inap);
        if (!$row) return false;

        if ($final_total === null) {
            $bill = $this->calculateBill($id_inap);
            $final_total = $bill['total_biaya_akhir'];
            $denda = $bill['denda_overstay'];
        }

        $query = "UPDATE " . $this->table_name . " 
                  SET status_inap = 'Selesai', 
                      tgl_aktual_keluar = NOW(), 
                      biaya_denda_overstay = :denda, 
                      total_biaya_akhir = :total 
                  WHERE id_inap = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':denda', $denda);
        $stmt->bindParam(':total', $final_total);
        $stmt->bindParam(':id', $id_inap);

        if ($stmt->execute()) {
            // Lepas kandang agar siap digunakan kembali
            $upd = $this->conn->prepare("UPDATE kandang SET status_tersedia = 1 WHERE id_kandang = :id_kandang");
            $upd->bindParam(':id_kandang', $row['id_kandang']);
            $upd->execute();
            return true;
        }
        return false;
    }

    public function cancelBooking($id_inap) {
        $row = $this->readOne($id_inap);
        if (!$row) return false;

        $query = "UPDATE " . $this->table_name . " SET status_inap = 'Batal' WHERE id_inap = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id_inap);

        if ($stmt->execute()) {
            $upd = $this->conn->prepare("UPDATE kandang SET status_tersedia = 1 WHERE id_kandang = :id_kandang");
            $upd->bindParam(':id_kandang', $row['id_kandang']);
            $upd->execute();
            return true;
        }
        return false;
    }

    public function readAll($status = null) {
        $query = "SELECT r.*, h.nama_hewan, h.spesies, h.ras, c.nama_customer, c.telepon as customer_telepon,
                         k.nomor_kandang, k.ukuran, k.tarif_per_malam
                  FROM " . $this->table_name . " r
                  JOIN hewan_peliharaan h ON r.id_hewan = h.id_hewan
                  JOIN customer c ON h.id_customer = c.id_customer
                  JOIN kandang k ON r.id_kandang = k.id_kandang ";

        if ($status) {
            $query .= " WHERE r.status_inap = :status ";
        }
        $query .= " ORDER BY r.id_inap DESC";

        $stmt = $this->conn->prepare($query);
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT r.*, h.nama_hewan, h.spesies, h.ras, h.catatan_alergi, h.catatan_kebiasaan,
                         c.id_customer, c.nama_customer, c.telepon as customer_telepon,
                         k.nomor_kandang, k.ukuran, k.tarif_per_malam
                  FROM " . $this->table_name . " r
                  JOIN hewan_peliharaan h ON r.id_hewan = h.id_hewan
                  JOIN customer c ON h.id_customer = c.id_customer
                  JOIN kandang k ON r.id_kandang = k.id_kandang
                  WHERE r.id_inap = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getCustomerBookings($id_customer) {
        $query = "SELECT r.*, h.nama_hewan, k.nomor_kandang, k.ukuran
                  FROM " . $this->table_name . " r
                  JOIN hewan_peliharaan h ON r.id_hewan = h.id_hewan
                  JOIN kandang k ON r.id_kandang = k.id_kandang
                  WHERE h.id_customer = :id_customer
                  ORDER BY r.id_inap DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_customer', $id_customer);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
