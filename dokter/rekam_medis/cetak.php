<?php
/**
 * dokter/rekam_medis/cetak.php
 * -----------------------------------------------------------
 * Cetak lembar rekam medis pasien.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('dokter');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$id_pendaftaran = $_GET['id_pendaftaran'] ?? null;
$id_dokter = $_SESSION['id_ref'];

if (!$id_pendaftaran) {
    die("ID Pendaftaran tidak ditemukan.");
}

// Ambil data rekam medis + pendaftaran + pasien + dokter
$sql = "
    SELECT rm.*, p.waktu_daftar, p.tanggal_kunjungan, p.no_antrian, 
           ps.nama_lengkap, ps.nim_nik, ps.jenis_kelamin, ps.tanggal_lahir, ps.status_akademik, ps.alamat, ps.no_telepon,
           d.nama_dokter, d.spesialisasi
    FROM rekam_medis rm
    JOIN pendaftaran p ON rm.id_pendaftaran = p.id_pendaftaran
    JOIN pasien ps ON p.id_pasien = ps.id_pasien
    JOIN dokter d ON rm.id_dokter = d.id_dokter
    WHERE p.id_pendaftaran = ? AND p.id_dokter = ?
";
$stmt = $koneksi->prepare($sql);
$stmt->execute([$id_pendaftaran, $id_dokter]);
$rm = $stmt->fetch();

if (!$rm) {
    die("Data rekam medis tidak ditemukan atau Anda tidak memiliki akses.");
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rekam Medis - <?= clean($rm['nama_lengkap']) ?></title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.5; color: #333; margin: 0; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 22px; }
        .header p { margin: 5px 0 0 0; color: #555; }
        .info-table, .soap-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .info-table td { padding: 5px; vertical-align: top; }
        .info-table td.label { width: 150px; font-weight: bold; }
        .soap-table th, .soap-table td { border: 1px solid #ccc; padding: 10px; text-align: left; vertical-align: top; }
        .soap-table th { background-color: #f9f9f9; width: 120px; }
        .footer { margin-top: 40px; text-align: right; }
        .signature { margin-top: 60px; font-weight: bold; }
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 15px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Sekarang</button>
        <button onclick="window.close()" style="padding: 10px 15px; background: #e5e7eb; color: #374151; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Tutup</button>
    </div>

    <div class="header">
        <h1>KLINIK SISTEM INFORMASI</h1>
        <p>Jl. Tuparev No. 117, Cirebon | Telp: (0231) XXXXXXX</p>
        <h2 style="margin: 15px 0 0 0; font-size: 18px; text-decoration: underline;">LEMBAR REKAM MEDIS</h2>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Nama Pasien</td>
            <td>: <?= clean($rm['nama_lengkap']) ?> (<?= clean($rm['jenis_kelamin'] == 'Laki-laki' ? 'L' : 'P') ?>)</td>
            <td class="label">Waktu Periksa</td>
            <td>: <?= date('d M Y, H:i', strtotime($rm['waktu_periksa'])) ?></td>
        </tr>
        <tr>
            <td class="label">NIM / NIK</td>
            <td>: <?= clean($rm['nim_nik']) ?></td>
            <td class="label">Dokter Pemeriksa</td>
            <td>: dr. <?= clean($rm['nama_dokter']) ?></td>
        </tr>
        <tr>
            <td class="label">Umur</td>
            <td>: <?= hitungUmur($rm['tanggal_lahir']) ?></td>
            <td class="label">Poli / Spesialisasi</td>
            <td>: <?= clean($rm['spesialisasi']) ?></td>
        </tr>
    </table>

    <table class="soap-table">
        <tr>
            <th>Anamnesis (S)</th>
            <td><?= nl2br(clean($rm['anamnesis'] ?: '-')) ?></td>
        </tr>
        <tr>
            <th>Pemeriksaan Fisik (O)</th>
            <td><?= nl2br(clean($rm['pemeriksaan_fisik'] ?: '-')) ?></td>
        </tr>
        <tr>
            <th>Diagnosis (A)</th>
            <td>
                <strong><?= clean($rm['diagnosis'] ?: '-') ?></strong>
                <?php if ($rm['kode_icd10']): ?>
                    <br><small>ICD-10: <?= clean($rm['kode_icd10']) ?></small>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>Tindakan / Plan (P)</th>
            <td><?= nl2br(clean($rm['tindakan'] ?: '-')) ?></td>
        </tr>
        <?php if ($rm['catatan_dokter']): ?>
        <tr>
            <th>Catatan Khusus</th>
            <td><em><?= nl2br(clean($rm['catatan_dokter'])) ?></em></td>
        </tr>
        <?php endif; ?>
    </table>

    <div class="footer">
        <p>Cirebon, <?= date('d M Y', strtotime($rm['waktu_periksa'])) ?></p>
        <p>Dokter Pemeriksa,</p>
        <div class="signature">
            dr. <?= clean($rm['nama_dokter']) ?>
        </div>
    </div>

</body>
</html>
