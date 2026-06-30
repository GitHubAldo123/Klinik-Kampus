<?php
/**
 * admin/laporan/index.php
 * -----------------------------------------------------------
 * Menampilkan ringkasan data dan laporan kunjungan pasien.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'laporan';
$judul_halaman = 'Laporan Klinik';

// --- Ambil Summary Data ---
$summary = [];

// 1. Total Pasien
$stmt_pasien = $koneksi->query("SELECT COUNT(id_pasien) as total FROM pasien");
$summary['total_pasien'] = $stmt_pasien->fetch()['total'] ?? 0;

// 2. Total Dokter Aktif
$stmt_dokter = $koneksi->query("SELECT COUNT(id_dokter) as total FROM dokter WHERE status_aktif = 'aktif'");
$summary['total_dokter'] = $stmt_dokter->fetch()['total'] ?? 0;

// 3. Kunjungan Bulan Ini
$stmt_kunjungan = $koneksi->query("SELECT COUNT(id_pendaftaran) as total FROM pendaftaran WHERE MONTH(tanggal_kunjungan) = MONTH(CURRENT_DATE()) AND YEAR(tanggal_kunjungan) = YEAR(CURRENT_DATE())");
$summary['kunjungan_bulan_ini'] = $stmt_kunjungan->fetch()['total'] ?? 0;

// 4. Total Jenis Obat
$stmt_obat = $koneksi->query("SELECT COUNT(id_obat) as total FROM obat");
$summary['total_obat'] = $stmt_obat->fetch()['total'] ?? 0;

// --- Filter Laporan Kunjungan ---
$filter_bulan = $_GET['bulan'] ?? date('m');
$filter_tahun = $_GET['tahun'] ?? date('Y');

$sql_laporan = "
    SELECT p.waktu_daftar, p.tanggal_kunjungan, ps.nama_lengkap as nama_pasien, ps.status_akademik, d.nama_dokter, p.keluhan_utama, p.status_kunjungan
    FROM pendaftaran p
    JOIN pasien ps ON p.id_pasien = ps.id_pasien
    JOIN dokter d ON p.id_dokter = d.id_dokter
    WHERE MONTH(p.tanggal_kunjungan) = ? AND YEAR(p.tanggal_kunjungan) = ?
    ORDER BY p.tanggal_kunjungan DESC, p.no_antrian ASC
";
$stmt_laporan = $koneksi->prepare($sql_laporan);
$stmt_laporan->execute([$filter_bulan, $filter_tahun]);
$laporan_kunjungan = $stmt_laporan->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<!-- SUMMARY CARDS -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    <!-- Card Total Pasien -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
        <div class="h-12 w-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500 mb-0.5">Total Pasien Terdaftar</p>
            <p class="text-2xl font-bold text-slate-800"><?= number_format($summary['total_pasien']) ?></p>
        </div>
    </div>

    <!-- Card Kunjungan Bulan Ini -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
        <div class="h-12 w-12 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500 mb-0.5">Kunjungan Bulan Ini</p>
            <p class="text-2xl font-bold text-slate-800"><?= number_format($summary['kunjungan_bulan_ini']) ?></p>
        </div>
    </div>

    <!-- Card Total Dokter Aktif -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
        <div class="h-12 w-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500 mb-0.5">Dokter Aktif</p>
            <p class="text-2xl font-bold text-slate-800"><?= number_format($summary['total_dokter']) ?></p>
        </div>
    </div>

    <!-- Card Total Obat -->
    <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center gap-4">
        <div class="h-12 w-12 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-slate-500 mb-0.5">Jenis Obat</p>
            <p class="text-2xl font-bold text-slate-800"><?= number_format($summary['total_obat']) ?></p>
        </div>
    </div>
</div>

<!-- LAPORAN KUNJUNGAN PASIEN -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h3 class="font-semibold text-slate-800">Laporan Kunjungan Pasien</h3>
        
        <form method="GET" class="flex items-center gap-2">
            <select name="bulan" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500">
                <?php
                $nama_bulan = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
                for($i=1; $i<=12; $i++) {
                    $selected = ($filter_bulan == $i) ? 'selected' : '';
                    echo "<option value=\"$i\" $selected>{$nama_bulan[$i]}</option>";
                }
                ?>
            </select>
            <select name="tahun" class="px-3 py-1.5 border border-slate-300 rounded-lg text-sm outline-none focus:ring-2 focus:ring-blue-500">
                <?php
                $tahun_sekarang = date('Y');
                for($i=$tahun_sekarang-2; $i<=$tahun_sekarang; $i++) {
                    $selected = ($filter_tahun == $i) ? 'selected' : '';
                    echo "<option value=\"$i\" $selected>$i</option>";
                }
                ?>
            </select>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-3 py-1.5 rounded-lg transition">Tampilkan</button>
            <button type="button" onclick="window.print()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm px-3 py-1.5 rounded-lg transition" title="Cetak Laporan">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            </button>
        </form>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">Tanggal & Waktu</th>
                    <th class="text-left font-medium px-5 py-3">Pasien</th>
                    <th class="text-left font-medium px-5 py-3">Dokter Pilihan</th>
                    <th class="text-left font-medium px-5 py-3">Keluhan</th>
                    <th class="text-center font-medium px-5 py-3">Status Kunjungan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($laporan_kunjungan)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-slate-400 py-8">
                            Tidak ada data kunjungan pada periode ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($laporan_kunjungan as $kunjungan): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">
                                <?= formatTanggal($kunjungan['tanggal_kunjungan']) ?><br>
                                <span class="text-xs text-slate-400">Daftar: <?= date('H:i', strtotime($kunjungan['waktu_daftar'])) ?></span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-700"><?= clean($kunjungan['nama_pasien']) ?></div>
                                <div class="text-xs text-slate-500"><?= clean($kunjungan['status_akademik']) ?></div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">dr. <?= clean($kunjungan['nama_dokter']) ?></td>
                            <td class="px-5 py-3 text-slate-600 max-w-xs truncate" title="<?= clean($kunjungan['keluhan_utama']) ?>">
                                <?= clean($kunjungan['keluhan_utama'] ?: '-') ?>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <?php 
                                    $status = $kunjungan['status_kunjungan'];
                                    $badgeClass = 'bg-slate-100 text-slate-600';
                                    if ($status == 'menunggu') $badgeClass = 'bg-amber-100 text-amber-700';
                                    else if ($status == 'diperiksa') $badgeClass = 'bg-blue-100 text-blue-700';
                                    else if ($status == 'selesai') $badgeClass = 'bg-emerald-100 text-emerald-700';
                                    else if ($status == 'batal') $badgeClass = 'bg-red-100 text-red-700';
                                ?>
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium <?= $badgeClass ?> capitalize">
                                    <?= clean($status) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
/* CSS khusus untuk print halaman (hilangkan sidebar, header, dll) */
@media print {
    body * {
        visibility: hidden;
    }
    .bg-white.rounded-xl.border.border-slate-200.overflow-hidden, 
    .bg-white.rounded-xl.border.border-slate-200.overflow-hidden * {
        visibility: visible;
    }
    .bg-white.rounded-xl.border.border-slate-200.overflow-hidden {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none;
        box-shadow: none;
    }
    form, button {
        display: none !important;
    }
}
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
