<?php
// Konfigurasi umum aplikasi PetCare
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/petcare/');
}
if (!defined('APP_NAME')) {
    define('APP_NAME', 'PetCare POS & Hotel Management');
}

// Konfigurasi session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Autoload classes
spl_autoload_register(function ($class_name) {
    $directories = [
        __DIR__ . '/../classes/',
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Include database connection
require_once __DIR__ . '/database.php';

// Helper functions staf internal
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

function requireRole($allowedRoles) {
    requireLogin();
    if (!in_array($_SESSION['user_role'], $allowedRoles)) {
        header('Location: ' . BASE_URL . 'unauthorized.php');
        exit();
    }
}

// Helper functions pelanggan (portal mandiri)
function isCustomerLoggedIn() {
    return isset($_SESSION['customer_id']) && isset($_SESSION['customer_nama']);
}

function requireCustomerLogin() {
    if (!isCustomerLoggedIn()) {
        header('Location: ' . BASE_URL . 'portal/index.php');
        exit();
    }
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data ?? '');
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function formatCurrency($amount) {
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function formatTanggalWaktu($datetime) {
    if (!$datetime) return '-';
    $time = strtotime($datetime);
    return date('d/m/Y H:i', $time);
}

function formatTanggal($date) {
    if (!$date) return '-';
    $time = strtotime($date);
    return date('d/m/Y', $time);
}
