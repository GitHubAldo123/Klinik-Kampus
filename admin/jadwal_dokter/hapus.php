<?php
/**
 * admin/jadwal_dokter/hapus.php
 * -----------------------------------------------------------
 * Proses menghapus jadwal dokter dari database.
 * Hanya menerima request method POST untuk keamanan.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_jadwal = $_POST['id_jadwal'] ?? null;

    if ($id_jadwal) {
        $stmt_cek = $koneksi->prepare("SELECT j.hari, d.nama_dokter FROM jadwal_dokter j JOIN dokter d ON j.id_dokter = d.id_dokter WHERE j.id_jadwal = ?");
        $stmt_cek->execute([$id_jadwal]);
        $jadwal = $stmt_cek->fetch();

        if ($jadwal) {
            try {
                $stmt_hapus = $koneksi->prepare("DELETE FROM jadwal_dokter WHERE id_jadwal = ?");
                $stmt_hapus->execute([$id_jadwal]);
                
                redirectDenganPesan('index.php', 'Jadwal hari ' . $jadwal['hari'] . ' untuk dr. ' . $jadwal['nama_dokter'] . ' berhasil dihapus.', 'success');
            } catch (PDOException $e) {
                redirectDenganPesan('index.php', 'Gagal menghapus jadwal. Pastikan tidak ada data pendaftaran pasien terkait jadwal ini. Detail: ' . $e->getMessage(), 'error');
            }
        } else {
            redirectDenganPesan('index.php', 'Data jadwal tidak ditemukan.', 'error');
        }
    } else {
        redirectDenganPesan('index.php', 'ID jadwal tidak valid.', 'error');
    }
} else {
    redirectDenganPesan('index.php', 'Akses tidak diizinkan.', 'error');
}
