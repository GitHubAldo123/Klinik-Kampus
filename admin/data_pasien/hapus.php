<?php
/**
 * admin/data_pasien/hapus.php
 * -----------------------------------------------------------
 * Proses menghapus data pasien dari database.
 * Hanya menerima request method POST untuk keamanan.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pasien = $_POST['id_pasien'] ?? null;

    if ($id_pasien) {
        // Cek apakah data pasien ada
        $stmt_cek = $koneksi->prepare("SELECT nama_lengkap FROM pasien WHERE id_pasien = ?");
        $stmt_cek->execute([$id_pasien]);
        $pasien = $stmt_cek->fetch();

        if ($pasien) {
            // Lakukan penghapusan
            // Catatan: Jika ada relasi rekam_medis dll, pastikan foreign key menggunakan ON DELETE CASCADE
            // atau hapus data terkait secara manual sebelum menghapus pasien (tergantung schema).
            try {
                $stmt_hapus = $koneksi->prepare("DELETE FROM pasien WHERE id_pasien = ?");
                $stmt_hapus->execute([$id_pasien]);
                
                redirectDenganPesan('index.php', 'Data pasien "' . $pasien['nama_lengkap'] . '" berhasil dihapus.', 'success');
            } catch (PDOException $e) {
                // Tangani error jika gagal menghapus (misal: foreign key constraint fail)
                redirectDenganPesan('index.php', 'Gagal menghapus pasien. Pastikan pasien ini tidak memiliki data rekam medis atau tangani error tersebut. Detail: ' . $e->getMessage(), 'error');
            }
        } else {
            redirectDenganPesan('index.php', 'Data pasien tidak ditemukan.', 'error');
        }
    } else {
        redirectDenganPesan('index.php', 'ID pasien tidak valid.', 'error');
    }
} else {
    // Jika diakses langsung via URL (GET), kembalikan ke index
    redirectDenganPesan('index.php', 'Akses tidak diizinkan.', 'error');
}
