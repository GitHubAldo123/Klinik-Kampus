<?php
require_once __DIR__ . '/config/database.php';
$sql = "
    SELECT p.*, ps.nama_lengkap as nama_pasien, ps.nim_nik, d.nama_dokter, j.jam_mulai, j.jam_selesai
    FROM pendaftaran p
    JOIN pasien ps ON p.id_pasien = ps.id_pasien
    JOIN dokter d ON p.id_dokter = d.id_dokter
    JOIN jadwal_dokter j ON p.id_jadwal = j.id_jadwal
";
try {
    $stmt = $koneksi->prepare($sql);
    echo "Prepare success!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
