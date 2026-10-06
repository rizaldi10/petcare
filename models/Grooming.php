<?php
class Grooming {
    private $conn;
    private $table_name = "antrean_grooming";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createQueue($data) {
        $query = "INSERT INTO " . $this->table_name . "
                  SET id_hewan=:id_hewan, id_groomer=:id_groomer, 
                      id_barang_layanan=:id_barang_layanan, status_pengerjaan=:status_pengerjaan,
                      waktu_masuk=NOW(), catatan_kondisi=:catatan_kondisi";

        $stmt = $this->conn->prepare($query);

        $status = $data['status_pengerjaan'] ?? 'Antre';
        $catatan = $data['catatan_kondisi'] ?? null;

        $stmt->bindParam(':id_hewan', $data['id_hewan']);
        $stmt->bindParam(':id_groomer', $data['id_groomer']);
        $stmt->bindParam(':id_barang_layanan', $data['id_barang_layanan']);
        $stmt->bindParam(':status_pengerjaan', $status);
        $stmt->bindParam(':catatan_kondisi', $catatan);

        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function updateStatus($id_grooming, $status, $catatan = null) {
        $validStatuses = ['Antre', 'Mandi', 'Pengeringan', 'Siap_Ambil', 'Selesai'];
        if (!in_array($status, $validStatuses)) return false;

        $query = "UPDATE " . $this->table_name . " 
                  SET status_pengerjaan = :status ";

        if ($status === 'Selesai') {
            $query .= ", waktu_selesai = NOW() ";
        }
        if ($catatan !== null) {
            $query .= ", catatan_kondisi = :catatan ";
        }
        $query .= " WHERE id_grooming = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id_grooming);
        if ($catatan !== null) {
            $stmt->bindParam(':catatan', $catatan);
        }

        return $stmt->execute();
    }

    public function readAll($status = null, $groomer_id = null) {
        $query = "SELECT g.*, h.nama_hewan, h.spesies, h.ras, h.catatan_alergi, h.catatan_kebiasaan,
                         c.id_customer, c.nama_customer, c.telepon as customer_telepon,
                         u.nama as nama_groomer, b.nama_barang as nama_layanan, b.harga_jual as tarif_layanan
                  FROM " . $this->table_name . " g
                  JOIN hewan_peliharaan h ON g.id_hewan = h.id_hewan
                  JOIN customer c ON h.id_customer = c.id_customer
                  JOIN users u ON g.id_groomer = u.id_user
                  JOIN barang b ON g.id_barang_layanan = b.id_barang ";

        $clauses = [];
        if ($status) {
            if ($status === 'aktif') {
                $clauses[] = " g.status_pengerjaan != 'Selesai' ";
            } else {
                $clauses[] = " g.status_pengerjaan = :status ";
            }
        }
        if ($groomer_id) {
            $clauses[] = " g.id_groomer = :groomer_id ";
        }

        if (!empty($clauses)) {
            $query .= " WHERE " . implode(" AND ", $clauses);
        }

        $query .= " ORDER BY CASE g.status_pengerjaan
                                WHEN 'Antre' THEN 1
                                WHEN 'Mandi' THEN 2
                                WHEN 'Pengeringan' THEN 3
                                WHEN 'Siap_Ambil' THEN 4
                                WHEN 'Selesai' THEN 5
                             END ASC, g.id_grooming DESC";

        $stmt = $this->conn->prepare($query);
        if ($status && $status !== 'aktif') {
            $stmt->bindParam(':status', $status);
        }
        if ($groomer_id) {
            $stmt->bindParam(':groomer_id', $groomer_id);
        }
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT g.*, h.nama_hewan, h.spesies, h.ras, h.catatan_alergi, h.catatan_kebiasaan,
                         c.id_customer, c.nama_customer, c.telepon as customer_telepon,
                         u.nama as nama_groomer, b.nama_barang as nama_layanan, b.harga_jual as tarif_layanan
                  FROM " . $this->table_name . " g
                  JOIN hewan_peliharaan h ON g.id_hewan = h.id_hewan
                  JOIN customer c ON h.id_customer = c.id_customer
                  JOIN users u ON g.id_groomer = u.id_user
                  JOIN barang b ON g.id_barang_layanan = b.id_barang
                  WHERE g.id_grooming = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function recordCommission($id_groomer, $id_penjualan, $id_barang_layanan, $harga_satuan, $persen = 20) {
        $nominal = ($harga_satuan * $persen) / 100;
        $query = "INSERT INTO komisi_groomer 
                  (id_user_groomer, id_penjualan, id_barang_layanan, persentase_komisi, nominal_komisi, tgl_transaksi)
                  VALUES (:groomer, :penjualan, :layanan, :persen, :nominal, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':groomer', $id_groomer);
        $stmt->bindParam(':penjualan', $id_penjualan);
        $stmt->bindParam(':layanan', $id_barang_layanan);
        $stmt->bindParam(':persen', $persen);
        $stmt->bindParam(':nominal', $nominal);
        return $stmt->execute();
    }

    public function getCommissionReport($groomer_id = null) {
        $query = "SELECT kg.*, u.nama as nama_groomer, b.nama_barang as nama_layanan, p.no_faktur
                  FROM komisi_groomer kg
                  JOIN users u ON kg.id_user_groomer = u.id_user
                  JOIN barang b ON kg.id_barang_layanan = b.id_barang
                  JOIN penjualan p ON kg.id_penjualan = p.id_penjualan ";

        if ($groomer_id) {
            $query .= " WHERE kg.id_user_groomer = :groomer_id ";
        }
        $query .= " ORDER BY kg.id_komisi DESC";

        $stmt = $this->conn->prepare($query);
        if ($groomer_id) {
            $stmt->bindParam(':groomer_id', $groomer_id);
        }
        $stmt->execute();
        return $stmt;
    }

    public function getTrackByCustomer($id_customer) {
        $query = "SELECT g.*, h.nama_hewan, h.spesies, u.nama as nama_groomer, b.nama_barang as nama_layanan
                  FROM " . $this->table_name . " g
                  JOIN hewan_peliharaan h ON g.id_hewan = h.id_hewan
                  JOIN users u ON g.id_groomer = u.id_user
                  JOIN barang b ON g.id_barang_layanan = b.id_barang
                  WHERE h.id_customer = :id_customer
                  ORDER BY g.id_grooming DESC LIMIT 10";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_customer', $id_customer);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
