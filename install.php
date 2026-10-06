<?php
// Script instalasi database untuk PetCare POS & Hotel Management System
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Instalasi Database - PetCare</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; color: #1e293b; padding: 40px 20px; }
        .card { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        h1 { color: #0284c7; margin-top: 0; font-size: 24px; }
        .log-item { padding: 8px 12px; margin-bottom: 8px; border-radius: 6px; font-size: 14px; }
        .success { background: #dcfce7; color: #166534; }
        .warning { background: #fef9c3; color: #854d0e; }
        .error { background: #fee2e2; color: #991b1b; }
        .btn { display: inline-block; background: #0284c7; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 15px; }
        .btn:hover { background: #0369a1; }
        ul { padding-left: 20px; font-size: 14px; }
    </style>
</head>
<body>
<div class='card'>";

echo "<h1>🐾 Instalasi Database - PetCare System</h1>";

// Konfigurasi database
$host = 'localhost';
$db_name = 'petcare_db';
$username = 'root';
$password = '';

try {
    // Koneksi tanpa database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='log-item success'>✅ Koneksi ke MySQL Server berhasil!</div>";
    
    // Buat database jika belum ada
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='log-item success'>✅ Database '$db_name' siap digunakan!</div>";
    
    // Pilih database
    $pdo->exec("USE `$db_name`");
    
    // Baca dan eksekusi schema SQL
    $schema_file = __DIR__ . '/database/petcare_schema.sql';
    if (!file_exists($schema_file)) {
        throw new Exception("File schema tidak ditemukan di: $schema_file");
    }
    
    $schema = file_get_contents($schema_file);
    
    // Split per statement dengan regex semi-colon di akhir baris
    $statements = array_filter(array_map('trim', explode(";\n", $schema)));
    
    foreach ($statements as $statement) {
        $stmt = trim($statement);
        if (!empty($stmt)) {
            try {
                $pdo->exec($stmt);
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<div class='log-item warning'>⚠️ Warning: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        }
    }
    
    echo "<div class='log-item success'>✅ Seluruh tabel dan data awal PetCare berhasil diimpor!</div>";
    
    // Validasi instalasi
    $test_pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $test_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $userCount = $test_pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $cageCount = $test_pdo->query("SELECT COUNT(*) FROM kandang")->fetchColumn();
    $itemCount = $test_pdo->query("SELECT COUNT(*) FROM barang")->fetchColumn();
    
    echo "<div class='log-item success'>
            📊 <strong>Data Terverifikasi:</strong><br>
            • User Staff: $userCount akun<br>
            • Fasilitas Kandang: $cageCount unit<br>
            • Produk / Jasa: $itemCount item
          </div>";
    
    echo "<h3>🎉 Instalasi Sukses!</h3>";
    echo "<p><strong>Akun Staf Internal (Password: <code>password</code>):</strong></p>";
    echo "<ul>
            <li><strong>Admin:</strong> <code>admin</code></li>
            <li><strong>Kasir:</strong> <code>kasir1</code></li>
            <li><strong>Groomer:</strong> <code>groomer1</code></li>
          </ul>";
    echo "<p><strong>Akun Portal Mandiri Pelanggan (Password: <code>password</code>):</strong></p>";
    echo "<ul>
            <li><strong>Pelanggan 1:</strong> <code>081234567890</code> (Ahmad Fauzi)</li>
            <li><strong>Pelanggan 2:</strong> <code>085678901234</code> (Jessica Tan)</li>
          </ul>";
          
    echo "<div style='display: flex; gap: 10px; margin-top: 20px;'>
            <a href='login.php' class='btn'>Masuk ke PetCare POS (Staf)</a>
            <a href='portal/index.php' class='btn' style='background: #10b981;'>Buka Portal Pelanggan</a>
          </div>";
    
} catch (Exception $e) {
    echo "<div class='log-item error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><strong>Troubleshooting:</strong></p>";
    echo "<ul>
            <li>Pastikan Laragon MySQL service sudah dijalankan (Status: Started)</li>
            <li>Periksa user dan password database di Laragon (default: root / tanpa password)</li>
          </ul>";
}

echo "</div></body></html>";
?>
