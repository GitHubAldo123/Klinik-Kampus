<?php
/**
 * admin/data_obat/hapus.php
 * -----------------------------------------------------------
 * Proses menghapus data obat dari database.
 * Hanya menerima request method POST untuk keamanan.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_obat = $_POST['id_obat'] ?? null;

    if ($id_obat) {
        $stmt_cek = $koneksi->prepare("SELECT nama_obat FROM obat WHERE id_obat = ?");
        $stmt_cek->execute([$id_obat]);
        $obat = $stmt_cek->fetch();

        if ($obat) {
            try {
                $stmt_hapus = $koneksi->prepare("DELETE FROM obat WHERE id_obat = ?");
                $stmt_hapus->execute([$id_obat]);
                
                redirectDenganPesan('index.php', 'Data obat "' . $obat['nama_obat'] . '" berhasil dihapus.', 'success');
            } catch (PDOException $e) {
                redirectDenganPesan('index.php', 'Gagal menghapus obat. Obat mungkin sedang digunakan dalam data resep. Detail: ' . $e->getMessage(), 'error');
            }
        } else {
            redirectDenganPesan('index.php', 'Data obat tidak ditemukan.', 'error');
        }
    } else {
        redirectDenganPesan('index.php', 'ID obat tidak valid.', 'error');
    }
} else {
    redirectDenganPesan('index.php', 'Akses tidak diizinkan.', 'error');
}
