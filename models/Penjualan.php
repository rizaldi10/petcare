<?php
class Penjualan {
    private $conn;
    private $table_name = "penjualan";

    public $id_penjualan;
    public $id; // alias
    public $id_customer;
    public $customer_id; // alias
    public $id_user;
    public $user_id; // alias
    public $no_faktur;
    public $no_transaksi; // alias
    public $tgl_penjualan;
    public $total_bayar;
    public $total_harga; // alias

    public function __construct($db) {
        $this->conn = $db;
    }

    public function generateNoFaktur() {
        $prefix = 'INV-' . date('Ymd') . '-';
        $query = "SELECT no_faktur FROM " . $this->table_name . " 
                  WHERE no_faktur LIKE :prefix 
                  ORDER BY id_penjualan DESC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $searchPrefix = $prefix . '%';
        $stmt->bindParam(':prefix', $searchPrefix);
        $stmt->execute();

        $last = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($last) {
            $num = (int)substr($last['no_faktur'], -4) + 1;
        } else {
            $num = 1;
        }
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    public function processCheckout($id_customer, $id_user, $items, $groomer_id = null, $hewan_id = null, $catatan_grooming = '') {
        try {
            $this->conn->beginTransaction();

            $no_faktur = $this->generateNoFaktur();
            $tgl = date('Y-m-d');
            $total_bayar = 0;

            // Hitung total bayar
            foreach ($items as $item) {
                $subtotal = (float)$item['harga_satuan'] * (int)$item['jumlah'];
                $total_bayar += $subtotal;
            }

            // 1. Simpan Header Penjualan
            $stmtHeader = $this->conn->prepare("INSERT INTO penjualan 
                (id_customer, id_user, no_faktur, tgl_penjualan, total_bayar, created_at)
                VALUES (:cust, :user, :faktur, :tgl, :total, NOW())");

            $stmtHeader->bindParam(':cust', $id_customer);
            $stmtHeader->bindParam(':user', $id_user);
            $stmtHeader->bindParam(':faktur', $no_faktur);
            $stmtHeader->bindParam(':tgl', $tgl);
            $stmtHeader->bindParam(':total', $total_bayar);
            $stmtHeader->execute();

            $id_penjualan = $this->conn->lastInsertId();

            // Ambil persentase komisi dari pengaturan
            $persenKomisi = 20;
            $stmtSet = $this->conn->query("SELECT nilai FROM pengaturan WHERE kunci = 'persentase_komisi_groomer' LIMIT 1");
            if ($rowSet = $stmtSet->fetch(PDO::FETCH_ASSOC)) {
                $persenKomisi = (float)$rowSet['nilai'];
            }

            // 2. Simpan Detail Item Penjualan
            $stmtDetail = $this->conn->prepare("INSERT INTO detail_penjualan 
                (id_penjualan, id_barang, jumlah, harga_satuan, subtotal)
                VALUES (:penjualan, :barang, :qty, :harga, :sub)");

            $stmtStok = $this->conn->prepare("UPDATE barang SET stok = stok - :qty WHERE id_barang = :id");

            foreach ($items as $item) {
                $id_barang = (int)$item['id_barang'];
                $qty = (int)$item['jumlah'];
                $harga = (float)$item['harga_satuan'];
                $sub = $qty * $harga;

                $stmtDetail->bindParam(':penjualan', $id_penjualan);
                $stmtDetail->bindParam(':barang', $id_barang);
                $stmtDetail->bindParam(':qty', $qty);
                $stmtDetail->bindParam(':harga', $harga);
                $stmtDetail->bindParam(':sub', $sub);
                $stmtDetail->execute();

                // Ambil info tipe_item barang
                $stmtInfo = $this->conn->prepare("SELECT tipe_item FROM barang WHERE id_barang = :id");
                $stmtInfo->bindParam(':id', $id_barang);
                $stmtInfo->execute();
                $bInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

                if ($bInfo && in_array($bInfo['tipe_item'], ['retail', 'repack'])) {
                    // Potong stok produk fisik
                    $stmtStok->bindParam(':qty', $qty);
                    $stmtStok->bindParam(':id', $id_barang);
                    $stmtStok->execute();
                } elseif ($bInfo && $bInfo['tipe_item'] === 'jasa') {
                    // Jika ada layanan grooming & groomer dipilih, catat komisi & antrean
                    if (!empty($groomer_id)) {
                        $nominalKomisi = ($sub * $persenKomisi) / 100;
                        $stmtKomisi = $this->conn->prepare("INSERT INTO komisi_groomer 
                            (id_user_groomer, id_penjualan, id_barang_layanan, persentase_komisi, nominal_komisi, tgl_transaksi)
                            VALUES (:groomer, :penjualan, :layanan, :persen, :nominal, NOW())");
                        $stmtKomisi->bindParam(':groomer', $groomer_id);
                        $stmtKomisi->bindParam(':penjualan', $id_penjualan);
                        $stmtKomisi->bindParam(':layanan', $id_barang);
                        $stmtKomisi->bindParam(':persen', $persenKomisi);
                        $stmtKomisi->bindParam(':nominal', $nominalKomisi);
                        $stmtKomisi->execute();

                        // Jika ada hewan yang dipilih, masukkan langsung ke antrean grooming
                        if (!empty($hewan_id)) {
                            $stmtAntre = $this->conn->prepare("INSERT INTO antrean_grooming 
                                (id_hewan, id_groomer, id_barang_layanan, status_pengerjaan, waktu_masuk, catatan_kondisi)
                                VALUES (:hewan, :groomer, :layanan, 'Antre', NOW(), :catatan)");
                            $stmtAntre->bindParam(':hewan', $hewan_id);
                            $stmtAntre->bindParam(':groomer', $groomer_id);
                            $stmtAntre->bindParam(':layanan', $id_barang);
                            $stmtAntre->bindParam(':catatan', $catatan_grooming);
                            $stmtAntre->execute();
                        }
                    }
                }
            }

            $this->conn->commit();
            return [
                'success' => true,
                'id_penjualan' => $id_penjualan,
                'no_faktur' => $no_faktur,
                'total_bayar' => $total_bayar
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
        $query = "SELECT p.*, p.id_penjualan as id, p.no_faktur as no_transaksi, p.total_bayar as total_harga,
                         u.nama as kasir, u.nama as nama_lengkap, c.nama_customer, c.telepon as customer_telepon
                  FROM " . $this->table_name . " p
                  JOIN users u ON p.id_user = u.id_user
                  JOIN customer c ON p.id_customer = c.id_customer
                  ORDER BY p.id_penjualan DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readRecent($limit = 10) {
        $query = "SELECT p.*, p.id_penjualan as id, p.no_faktur as no_transaksi, p.total_bayar as total_harga,
                         u.nama as kasir, c.nama_customer
                  FROM " . $this->table_name . " p
                  JOIN users u ON p.id_user = u.id_user
                  JOIN customer c ON p.id_customer = c.id_customer
                  ORDER BY p.id_penjualan DESC LIMIT :lim";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id) {
        $query = "SELECT p.*, p.id_penjualan as id, p.no_faktur as no_transaksi, p.total_bayar as total_harga,
                         u.nama as kasir, c.id_customer, c.nama_customer, c.telepon as customer_telepon, c.alamat as customer_alamat
                  FROM " . $this->table_name . " p
                  JOIN users u ON p.id_user = u.id_user
                  JOIN customer c ON p.id_customer = c.id_customer
                  WHERE p.id_penjualan = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id_penjualan = $row['id_penjualan'];
            $this->id = $row['id_penjualan'];
            $this->no_faktur = $row['no_faktur'];
            $this->no_transaksi = $row['no_faktur'];
            $this->id_user = $row['id_user'];
            $this->id_customer = $row['id_customer'];
            $this->tgl_penjualan = $row['tgl_penjualan'];
            $this->total_bayar = $row['total_bayar'];
            $this->total_harga = $row['total_bayar'];
            return $row;
        }
        return false;
    }

    public function getDetailPenjualan($penjualan_id) {
        $query = "SELECT dp.*, dp.id_barang as barang_id, b.nama_barang, b.kode_barang, b.tipe_item
                  FROM detail_penjualan dp
                  JOIN barang b ON dp.id_barang = b.id_barang
                  WHERE dp.id_penjualan = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $penjualan_id);
        $stmt->execute();
        return $stmt;
    }

    public function getTotalPenjualanHari() {
        $query = "SELECT COALESCE(SUM(total_bayar), 0) as total FROM " . $this->table_name . " 
                  WHERE tgl_penjualan = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getTotalPenjualanBulan() {
        $query = "SELECT COALESCE(SUM(total_bayar), 0) as total FROM " . $this->table_name . " 
                  WHERE MONTH(tgl_penjualan) = MONTH(CURDATE()) AND YEAR(tgl_penjualan) = YEAR(CURDATE())";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getTotalTransaksiHari() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE tgl_penjualan = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getLaporanPenjualan($start_date, $end_date) {
        $query = "SELECT p.*, p.id_penjualan as id, p.no_faktur as no_transaksi, p.total_bayar as total_harga,
                         u.nama as kasir, c.nama_customer
                  FROM " . $this->table_name . " p
                  JOIN users u ON p.id_user = u.id_user
                  JOIN customer c ON p.id_customer = c.id_customer
                  WHERE p.tgl_penjualan BETWEEN :start_date AND :end_date
                  ORDER BY p.id_penjualan DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':start_date', $start_date);
        $stmt->bindParam(':end_date', $end_date);
        $stmt->execute();
        return $stmt;
    }

    public function getCustomerHistory($id_customer) {
        $query = "SELECT p.*, u.nama as kasir
                  FROM " . $this->table_name . " p
                  JOIN users u ON p.id_user = u.id_user
                  WHERE p.id_customer = :id_customer
                  ORDER BY p.id_penjualan DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_customer', $id_customer);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
