<?php
/**
 * admin/pendaftaran/hapus.php
 * -----------------------------------------------------------
 * Menghapus data pendaftaran (membatalkan antrean).
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pendaftaran = $_POST['id_pendaftaran'] ?? null;

    if ($id_pendaftaran) {
        // Cek dulu apakah statusnya masih menunggu
        $stmt_cek = $koneksi->prepare("SELECT status_kunjungan FROM pendaftaran WHERE id_pendaftaran = ?");
        $stmt_cek->execute([$id_pendaftaran]);
        $pend = $stmt_cek->fetch();

        if ($pend && $pend['status_kunjungan'] === 'menunggu') {
            try {
                // Hapus data
                $stmt_hapus = $koneksi->prepare("DELETE FROM pendaftaran WHERE id_pendaftaran = ?");
                $stmt_hapus->execute([$id_pendaftaran]);
                
                redirectDenganPesan('index.php', 'Antrean berhasil dibatalkan dan dihapus.', 'success');
            } catch (PDOException $e) {
                redirectDenganPesan('index.php', 'Gagal membatalkan antrean. Detail: ' . $e->getMessage(), 'error');
            }
        } else {
            redirectDenganPesan('index.php', 'Tidak dapat menghapus antrean karena pasien sedang/sudah diperiksa.', 'error');
        }
    } else {
        redirectDenganPesan('index.php', 'ID Pendaftaran tidak valid.', 'error');
    }
} else {
    redirectDenganPesan('index.php', 'Akses tidak diizinkan.', 'error');
}
