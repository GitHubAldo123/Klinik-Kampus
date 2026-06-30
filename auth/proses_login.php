<?php
/**
 * auth/proses_login.php
 * -----------------------------------------------------------
 * Menerima data dari form login.php, memverifikasi ke tabel users,
 * lalu menyimpan data ke session dan redirect sesuai role.
 * -----------------------------------------------------------
 */
session_start();
require_once __DIR__ . '/../config/database.php';

// Hanya proses jika request method adalah POST (mencegah akses langsung lewat URL)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

// Ambil & bersihkan input dari form
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// Validasi dasar: pastikan tidak ada field yang kosong
if (empty($username) || empty($password)) {
    header("Location: login.php?error=salah");
    exit;
}

try {
    // Cari user berdasarkan username
    $stmt = $koneksi->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    // Jika username tidak ditemukan
    if (!$user) {
        header("Location: login.php?error=salah");
        exit;
    }

    // Cek apakah akun masih aktif
    if ($user['status_aktif'] !== 'aktif') {
        header("Location: login.php?error=nonaktif");
        exit;
    }

    // Verifikasi password menggunakan password_verify() (cocok dengan password_hash())
    if (!password_verify($password, $user['password'])) {
        // Cek apakah password di database masih berupa plaintext (belum di-hash)
        if ($password === $user['password']) {
            // Update password di database menjadi format hash (migrasi on-the-fly)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_hash = $koneksi->prepare("UPDATE users SET password = ? WHERE id_user = ?");
            $stmt_hash->execute([$hashed_password, $user['id_user']]);
        } else {
            header("Location: login.php?error=salah");
            exit;
        }
    }

    // ============================================================
    // LOGIN BERHASIL — simpan data ke session
    // ============================================================

    $_SESSION['user_id']  = $user['id_user'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role']     = $user['role'];      // 'admin' atau 'dokter'
    $_SESSION['id_ref']   = $user['id_ref'];    // id_admin ATAU id_dokter

    // Ambil nama lengkap sesuai role, untuk ditampilkan di header dashboard nanti
    if ($user['role'] === 'admin') {
        $stmt_nama = $koneksi->prepare("SELECT nama_admin AS nama FROM admin WHERE id_admin = ?");
    } else {
        $stmt_nama = $koneksi->prepare("SELECT nama_dokter AS nama FROM dokter WHERE id_dokter = ?");
    }
    $stmt_nama->execute([$user['id_ref']]);
    $data_nama = $stmt_nama->fetch();
    $_SESSION['nama'] = $data_nama['nama'] ?? $user['username'];

    // Update kolom last_login di tabel users
    $stmt_update = $koneksi->prepare("UPDATE users SET last_login = NOW() WHERE id_user = ?");
    $stmt_update->execute([$user['id_user']]);

    // Catat ke log_aktivitas (opsional, untuk audit trail)
    $stmt_log = $koneksi->prepare("INSERT INTO log_aktivitas (id_user, aktivitas) VALUES (?, ?)");
    $stmt_log->execute([$user['id_user'], 'Login ke sistem']);

    // Redirect sesuai role
    if ($user['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../dokter/dashboard.php");
    }
    exit;

} catch (PDOException $e) {
    // Jika terjadi error database, jangan tampilkan detail error ke user (alasan keamanan)
    error_log("Login error: " . $e->getMessage());
    header("Location: login.php?error=salah");
    exit;
}