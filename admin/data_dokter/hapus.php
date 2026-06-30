<?php
/**
 * admin/data_dokter/hapus.php
 * -----------------------------------------------------------
 * Proses menghapus data dokter dari database.
 * Hanya menerima request method POST untuk keamanan.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_dokter = $_POST['id_dokter'] ?? null;

    if ($id_dokter) {
        $stmt_cek = $koneksi->prepare("SELECT nama_dokter FROM dokter WHERE id_dokter = ?");
        $stmt_cek->execute([$id_dokter]);
        $dokter = $stmt_cek->fetch();

        if ($dokter) {
            try {
                $koneksi->beginTransaction();
                
                // Hapus akun login (jika ada) di tabel users
                $stmt_user = $koneksi->prepare("DELETE FROM users WHERE role = 'dokter' AND id_ref = ?");
                $stmt_user->execute([$id_dokter]);

                // Hapus data dokter
                $stmt_hapus = $koneksi->prepare("DELETE FROM dokter WHERE id_dokter = ?");
                $stmt_hapus->execute([$id_dokter]);
                
                $koneksi->commit();
                redirectDenganPesan('index.php', 'Data dokter "' . $dokter['nama_dokter'] . '" berhasil dihapus.', 'success');
            } catch (PDOException $e) {
                $koneksi->rollBack();
                redirectDenganPesan('index.php', 'Gagal menghapus dokter. Pastikan dokter ini tidak memiliki data pendaftaran atau rekam medis terkait. Detail: ' . $e->getMessage(), 'error');
            }
        } else {
            redirectDenganPesan('index.php', 'Data dokter tidak ditemukan.', 'error');
        }
    } else {
        redirectDenganPesan('index.php', 'ID dokter tidak valid.', 'error');
    }
} else {
    redirectDenganPesan('index.php', 'Akses tidak diizinkan.', 'error');
}
