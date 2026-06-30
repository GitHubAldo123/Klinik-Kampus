<?php
/**
 * auth/logout.php
 * -----------------------------------------------------------
 * Menghancurkan session login, lalu redirect ke halaman login.
 * Dipanggil dari tombol "Logout" di header dashboard.
 * -----------------------------------------------------------
 */
session_start();

// Catat log aktivitas logout sebelum session dihapus (jika ada koneksi DB & user login)
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    try {
        $stmt = $koneksi->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], 'Logout dari sistem']);
    } catch (PDOException $e) {
        // Jika gagal catat log, tetap lanjutkan proses logout (tidak menghalangi)
        error_log("Gagal mencatat log logout: " . $e->getMessage());
    }
}

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session di browser (praktik keamanan yang baik)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session sepenuhnya
session_destroy();

// Redirect ke halaman login
header("Location: login.php?error=logout_sukses");
exit;