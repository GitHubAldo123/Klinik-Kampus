<?php
/**
 * admin/dashboard.php
 * -----------------------------------------------------------
 * Halaman utama Admin setelah login.
 * Menampilkan ringkasan statistik & antrian terbaru hari ini.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth_check.php';
cekLogin('admin');   // hanya admin yang boleh akses halaman ini

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$halaman_aktif = 'dashboard';
$judul_halaman = 'Dashboard';

// ============================================================
// AMBIL DATA STATISTIK UNTUK CARD RINGKASAN
// ============================================================

// Total pasien terdaftar
$stmt = $koneksi->query("SELECT COUNT(*) AS total FROM pasien");
$total_pasien = $stmt->fetch()['total'];

// Total dokter aktif
$stmt = $koneksi->query("SELECT COUNT(*) AS total FROM dokter WHERE status_aktif = 'aktif'");
$total_dokter = $stmt->fetch()['total'];

// Jadwal praktik hari ini (berdasarkan nama hari sekarang)
$hari_ini = namaHariIni();
$stmt = $koneksi->prepare("SELECT COUNT(*) AS total FROM jadwal_dokter WHERE hari = ? AND status = 'aktif'");
$stmt->execute([$hari_ini]);
$total_jadwal_hari_ini = $stmt->fetch()['total'];

// Total pendaftaran / kunjungan hari ini
$stmt = $koneksi->query("SELECT COUNT(*) AS total FROM pendaftaran WHERE tanggal_kunjungan = CURDATE()");
$total_kunjungan_hari_ini = $stmt->fetch()['total'];

// Stok obat yang sudah menipis (di bawah stok_minimum)
$stmt = $koneksi->query("SELECT COUNT(*) AS total FROM obat WHERE stok <= stok_minimum");
$total_obat_menipis = $stmt->fetch()['total'];

// ============================================================
// DAFTAR ANTRIAN HARI INI (terbaru di atas)
// ============================================================
$stmt = $koneksi->query("
    SELECT
        p.id_pendaftaran,
        p.no_antrian,
        p.keluhan_utama,
        p.status_kunjungan,
        p.waktu_daftar,
        ps.nama_lengkap AS nama_pasien,
        d.nama_dokter
    FROM pendaftaran p
    JOIN pasien ps ON ps.id_pasien = p.id_pasien
    JOIN dokter d  ON d.id_dokter  = p.id_dokter
    WHERE p.tanggal_kunjungan = CURDATE()
    ORDER BY p.waktu_daftar DESC
    LIMIT 8
");
$daftar_antrian = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<?php tampilkanFlashMessage(); ?>

<!-- ===================== CARD STATISTIK ===================== -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <!-- Card: Total Pasien -->
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <svg class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" /></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-slate-800"><?= number_format($total_pasien) ?></p>
        <p class="text-sm text-slate-500">Total Pasien</p>
    </div>

    <!-- Card: Total Dokter -->
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7h-3a2 2 0 01-2-2V2M9 2v3a2 2 0 01-2 2H4m0 0v14a2 2 0 002 2h12a2 2 0 002-2V7" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h6v6H9V9z" /></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-slate-800"><?= number_format($total_dokter) ?></p>
        <p class="text-sm text-slate-500">Dokter Aktif</p>
    </div>

    <!-- Card: Jadwal Hari Ini -->
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-slate-800"><?= number_format($total_jadwal_hari_ini) ?></p>
        <p class="text-sm text-slate-500">Jadwal Praktik Hari Ini</p>
    </div>

    <!-- Card: Kunjungan Hari Ini -->
    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-3">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <svg class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
            </div>
        </div>
        <p class="text-2xl font-bold text-slate-800"><?= number_format($total_kunjungan_hari_ini) ?></p>
        <p class="text-sm text-slate-500">Kunjungan Hari Ini</p>
    </div>

</div>

<?php if ($total_obat_menipis > 0): ?>
<!-- ===================== PERINGATAN STOK OBAT MENIPIS ===================== -->
<div class="bg-amber-50 border-l-4 border-amber-400 text-amber-800 p-4 rounded mb-6 flex items-center gap-3">
    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
    <p class="text-sm">
        Ada <strong><?= $total_obat_menipis ?> jenis obat</strong> dengan stok menipis.
        <a href="<?= BASE_URL ?>/admin/data_obat/index.php" class="underline font-medium">Cek sekarang &rarr;</a>
    </p>
</div>
<?php endif; ?>

<!-- ===================== TABEL ANTRIAN HARI INI ===================== -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800">Antrian Hari Ini</h2>
        <span class="text-xs text-slate-400">Menampilkan 8 antrian terbaru</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">No. Antrian</th>
                    <th class="text-left font-medium px-5 py-3">Pasien</th>
                    <th class="text-left font-medium px-5 py-3">Dokter</th>
                    <th class="text-left font-medium px-5 py-3">Keluhan</th>
                    <th class="text-left font-medium px-5 py-3">Waktu</th>
                    <th class="text-left font-medium px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($daftar_antrian)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-slate-400 py-8">Belum ada antrian hari ini</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar_antrian as $antrian): ?>
                        <?php [$label_status, $kelas_warna] = badgeStatusKunjungan($antrian['status_kunjungan']); ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-700">#<?= $antrian['no_antrian'] ?></td>
                            <td class="px-5 py-3 text-slate-700"><?= clean($antrian['nama_pasien']) ?></td>
                            <td class="px-5 py-3 text-slate-600"><?= clean($antrian['nama_dokter']) ?></td>
                            <td class="px-5 py-3 text-slate-500 max-w-xs truncate"><?= clean($antrian['keluhan_utama'] ?? '-') ?></td>
                            <td class="px-5 py-3 text-slate-500"><?= formatJam($antrian['waktu_daftar']) ?></td>
                            <td class="px-5 py-3">
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium <?= $kelas_warna ?>">
                                    <?= $label_status ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>