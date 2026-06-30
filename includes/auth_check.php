<?php
/**
 * includes/auth_check.php
 * -----------------------------------------------------------
 * Penjaga akses halaman. WAJIB di-include di baris PALING ATAS
 * setiap file di folder admin/ dan dokter/ (sebelum HTML apapun).
 *
 * Cara pakai di halaman ADMIN, contoh di admin/dashboard.php:
 *   require_once __DIR__ . '/../includes/auth_check.php';
 *   cekLogin('admin');
 *
 * Cara pakai di halaman DOKTER, contoh di dokter/dashboard.php:
 *   require_once __DIR__ . '/../includes/auth_check.php';
 *   cekLogin('dokter');
 * -----------------------------------------------------------
 */

// Mulai session jika belum dimulai di file lain
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mengecek apakah user sudah login DAN rolenya sesuai dengan yang diizinkan.
 * Jika tidak memenuhi syarat, redirect ke halaman login.
 *
 * @param string $role_yang_diizinkan 'admin' atau 'dokter'
 */
function cekLogin($role_yang_diizinkan) {
    // 1. Cek apakah session login ada sama sekali
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $app_dir  = str_replace('\\', '/', dirname(__DIR__));
        $base_url = str_replace($doc_root, '', $app_dir);
        header("Location: " . $base_url . "/auth/login.php?error=belum_login");
        exit;
    }

    // 2. Cek apakah role di session sesuai dengan role yang diizinkan untuk halaman ini
    //    Contoh: dokter mencoba mengakses halaman folder admin -> ditolak
    if ($_SESSION['role'] !== $role_yang_diizinkan) {
        $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
        $app_dir  = str_replace('\\', '/', dirname(__DIR__));
        $base_url = str_replace($doc_root, '', $app_dir);
        header("Location: " . $base_url . "/auth/login.php?error=akses_ditolak");
        exit;
    }
}

/**
 * Helper untuk mengambil data user yang sedang login dari session.
 * Mengembalikan array berisi id_user, role, id_ref, nama (jika sudah di-set saat login)
 */
function userLoginSaatIni() {
    return [
        'id_user' => $_SESSION['user_id']   ?? null,
        'role'    => $_SESSION['role']      ?? null,
        'id_ref'  => $_SESSION['id_ref']    ?? null,   // id_admin ATAU id_dokter
        'nama'    => $_SESSION['nama']      ?? null,
        'username'=> $_SESSION['username']  ?? null,
    ];
}