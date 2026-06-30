<?php
/**
 * dokter/resep/cetak.php
 * -----------------------------------------------------------
 * Cetak lembar resep obat untuk pasien.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('dokter');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$id_resep = $_GET['id_resep'] ?? null;
$id_dokter = $_SESSION['id_ref'];

if (!$id_resep) {
    die("ID Resep tidak ditemukan.");
}

// Ambil data resep, rekam medis, pendaftaran, pasien, dan dokter
$sql = "
    SELECT r.*, rm.diagnosis, p.waktu_daftar, p.tanggal_kunjungan, 
           ps.nama_lengkap, ps.nim_nik, ps.jenis_kelamin, ps.tanggal_lahir, ps.alamat,
           d.nama_dokter
    FROM resep r
    JOIN rekam_medis rm ON r.id_rekam_medis = rm.id_rekam_medis
    JOIN pendaftaran p ON rm.id_pendaftaran = p.id_pendaftaran
    JOIN pasien ps ON p.id_pasien = ps.id_pasien
    JOIN dokter d ON rm.id_dokter = d.id_dokter
    WHERE r.id_resep = ? AND rm.id_dokter = ?
";
$stmt = $koneksi->prepare($sql);
$stmt->execute([$id_resep, $id_dokter]);
$resep = $stmt->fetch();

if (!$resep) {
    die("Data resep tidak ditemukan atau Anda tidak memiliki akses.");
}

// Ambil detail obat dalam resep ini
$stmt_detail = $koneksi->prepare("
    SELECT dr.*, o.nama_obat, o.satuan, o.jenis_obat
    FROM detail_resep dr
    JOIN obat o ON dr.id_obat = o.id_obat
    WHERE dr.id_resep = ?
");
$stmt_detail->execute([$id_resep]);
$detail_resep = $stmt_detail->fetchAll();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Resep - <?= clean($resep['nama_lengkap']) ?></title>
    <style>
        body { font-family: 'Times New Roman', serif; font-size: 14px; line-height: 1.5; color: #000; margin: 0; padding: 20px; }
        .receipt-box { max-width: 500px; margin: 0 auto; border: 1px solid #000; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header h1 { margin: 0; font-size: 20px; text-transform: uppercase; }
        .header p { margin: 3px 0 0 0; font-size: 13px; }
        
        .doctor-info { text-align: right; margin-bottom: 20px; font-style: italic; }
        
        .patient-info { border: 1px solid #000; padding: 10px; margin-bottom: 20px; }
        .patient-info table { width: 100%; border-collapse: collapse; }
        .patient-info td { vertical-align: top; font-size: 13px; padding: 2px 0; }
        .patient-info td:first-child { width: 100px; }
        
        .rx-symbol { font-size: 32px; font-weight: bold; font-family: Arial, sans-serif; margin-bottom: 15px; }
        
        .medication-list { margin-bottom: 30px; min-height: 200px; }
        .med-item { margin-bottom: 15px; }
        .med-name { font-weight: bold; font-size: 15px; }
        .med-qty { float: right; font-weight: bold; }
        .med-rule { margin-left: 20px; font-style: italic; font-size: 13px; }
        .med-note { margin-left: 20px; font-size: 12px; color: #555; }
        
        .footer { display: flex; justify-content: space-between; align-items: flex-end; margin-top: 40px; }
        .footer-left { font-size: 12px; font-style: italic; }
        .signature-box { text-align: center; }
        .signature-line { margin-top: 60px; border-bottom: 1px solid #000; width: 150px; }
        
        @media print {
            body { padding: 0; background: none; }
            .no-print { display: none; }
            .receipt-box { border: none; padding: 0; margin: 0; width: 100%; max-width: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: center; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 15px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;">Print Resep</button>
        <button onclick="window.close()" style="padding: 10px 15px; background: #e5e7eb; color: #374151; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Tutup</button>
    </div>

    <div class="receipt-box">
        <div class="header">
            <h1>Klinik Sistem Informasi</h1>
            <p>Jl. Tuparev No. 117, Cirebon - Telp: (0231) 123456</p>
        </div>
        
        <div class="doctor-info">
            Cirebon, <?= date('d F Y', strtotime($resep['tanggal_resep'])) ?><br>
            Dokter: <strong>dr. <?= clean($resep['nama_dokter']) ?></strong>
        </div>

        <div class="patient-info">
            <table>
                <tr>
                    <td>Pro (Nama)</td>
                    <td>: <?= clean($resep['nama_lengkap']) ?></td>
                </tr>
                <tr>
                    <td>Umur</td>
                    <td>: <?= hitungUmur($resep['tanggal_lahir']) ?></td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>: <?= clean($resep['alamat'] ?: '-') ?></td>
                </tr>
            </table>
        </div>

        <div class="rx-symbol">R/</div>

        <div class="medication-list">
            <?php if (empty($detail_resep)): ?>
                <p style="font-style: italic; color: #777;">(Belum ada obat yang ditambahkan ke dalam resep ini)</p>
            <?php else: ?>
                <?php foreach ($detail_resep as $dr): ?>
                    <div class="med-item">
                        <div class="med-name">
                            <?= clean($dr['nama_obat']) ?> 
                            <span class="med-qty">No. <?= clean($dr['jumlah']) ?></span>
                        </div>
                        <div class="med-rule">
                            S. <?= clean($dr['aturan_pakai'] ?: '___ x ___ sehari') ?>
                        </div>
                        <?php if ($dr['catatan_pakai']): ?>
                            <div class="med-note">Catatan: <?= clean($dr['catatan_pakai']) ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="footer">
            <div class="footer-left">
                * Harap tebus resep ini di Apotek<br>
                * Resep tidak boleh diulang tanpa persetujuan dokter
            </div>
            <div class="signature-box">
                Paraf Dokter
                <div class="signature-line"></div>
            </div>
        </div>
    </div>

</body>
</html>
