<?php
/**
 * config/database.php
 * -----------------------------------------------------------
 * File koneksi database menggunakan PDO.
 * File ini akan di-include di hampir semua file PHP lain
 * yang membutuhkan akses ke database.
 * -----------------------------------------------------------
 */

// --- Konfigurasi Database ---
// Sesuaikan jika username/password MySQL Anda berbeda dari default XAMPP
define('DB_HOST', 'localhost');
define('DB_NAME', 'klinik_kampus');
define('DB_USER', 'root');
define('DB_PASS', '');        // default XAMPP: password kosong
define('DB_CHARSET', 'utf8mb4');

// Base URL (otomatis menyesuaikan dengan nama folder project)
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$app_dir  = str_replace('\\', '/', dirname(__DIR__));
$base_url = str_replace($doc_root, '', $app_dir);
define('BASE_URL', $base_url);

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,      // lempar exception jika ada error
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,            // hasil query berupa array asosiatif
        PDO::ATTR_EMULATE_PREPARES   => false,                       // pakai native prepared statement (lebih aman)
    ];

    $koneksi = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // Jika koneksi gagal, hentikan eksekusi dan tampilkan pesan yang jelas
    die("Koneksi database gagal: " . $e->getMessage());
}