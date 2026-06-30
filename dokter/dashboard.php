<?php
/**
 * dokter/dashboard.php
 * -----------------------------------------------------------
 * Halaman utama Dokter setelah login.
 * Menampilkan antrian pasien HARI INI khusus milik dokter ini saja,
 * diambil dari data yang sudah diinput oleh Admin di tabel pendaftaran.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../includes/auth_check.php';
cekLogin('dokter');   // hanya dokter yang boleh akses halaman ini

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$halaman_aktif = 'dashboard';
$judul_halaman = 'Dashboard Dokter';

$user      = userLoginSaatIni();
$id_dokter = $user['id_ref'];   // id_dokter dokter yang sedang login

// ============================================================
// AMBIL DATA STATISTIK RINGKASAN UNTUK DOKTER INI
// ============================================================

// Jumlah pasien yang menunggu hari ini (khusus dokter ini)
$stmt = $koneksi->prepare("
    SELECT COUNT(*) AS total FROM pendaftaran
    WHERE id_dokter = ? AND status_kunjungan = 'menunggu' AND tanggal_kunjungan = CURDATE()
");
$stmt->execute([$id_dokter]);
$total_menunggu = $stmt->fetch()['total'];

// Jumlah pasien yang sudah selesai diperiksa hari ini
$stmt = $koneksi->prepare("
    SELECT COUNT(*) AS total FROM pendaftaran
    WHERE id_dokter = ? AND status_kunjungan = 'selesai' AND tanggal_kunjungan = CURDATE()
");
$stmt->execute([$id_dokter]);
$total_selesai = $stmt->fetch()['total'];

// Total keseluruhan pasien hari ini (menunggu + diperiksa + selesai, tidak termasuk batal)
$stmt = $koneksi->prepare("
    SELECT COUNT(*) AS total FROM pendaftaran
    WHERE id_dokter = ? AND status_kunjungan != 'batal' AND tanggal_kunjungan = CURDATE()
");
$stmt->execute([$id_dokter]);
$total_pasien_hari_ini = $stmt->fetch()['total'];

// Jadwal praktik dokter ini untuk hari ini (jika ada)
$hari_ini = namaHariIni();
$stmt = $koneksi->prepare("
    SELECT * FROM jadwal_dokter
    WHERE id_dokter = ? AND hari = ? AND status = 'aktif'
");
$stmt->execute([$id_dokter, $hari_ini]);
$jadwal_hari_ini = $stmt->fetch();

// ============================================================
// DAFTAR ANTRIAN PASIEN MILIK DOKTER INI, HARI INI
// Diurutkan berdasarkan no_antrian (yang menunggu duluan tampil di atas)
// ============================================================
$stmt = $koneksi->prepare("
    SELECT
        p.id_pendaftaran,
        p.no_antrian,
        p.keluhan_utama,
        p.status_kunjungan,
        p.waktu_daftar,
        ps.id_pasien,
        ps.nama_lengkap AS nama_pasien,
        ps.jenis_kelamin,
        ps.tanggal_lahir,
        ps.nim_nik
    FROM pendaftaran p
    JOIN pasien ps ON ps.id_pasien = p.id_pasien
    WHERE p.id_dokter = ?
    AND p.tanggal_kunjungan = CURDATE()
    ORDER BY
        CASE p.status_kunjungan
            WHEN 'menunggu'  THEN 1
            WHEN 'diperiksa' THEN 2
            WHEN 'selesai'   THEN 3
            WHEN 'batal'     THEN 4
        END,
        p.no_antrian ASC
");
$stmt->execute([$id_dokter]);
$daftar_antrian = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<?php tampilkanFlashMessage(); ?>

<!-- ===================== INFO JADWAL HARI INI ===================== -->
<div class="bg-blue-600 rounded-xl p-5 mb-6 text-white flex items-center justify-between flex-wrap gap-3">
    <div>
        <p class="text-blue-100 text-sm mb-1">Selamat datang, <?= clean($user['nama']) ?></p>
        <?php if ($jadwal_hari_ini): ?>
            <p class="text-lg font-semibold">
                Jadwal Praktik Hari Ini: <?= $hari_ini ?>, <?= formatJam($jadwal_hari_ini['jam_mulai']) ?> &ndash; <?= formatJam($jadwal_hari_ini['jam_selesai']) ?>
            </p>
        <?php else: ?>
            <p class="text-lg font-semibold">Anda tidak memiliki jadwal praktik hari ini (<?= $hari_ini ?>)</p>
        <?php endif; ?>
    </div>
    <div class="text-right">
        <p class="text-3xl font-bold"><?= $total_pasien_hari_ini ?></p>
        <p class="text-blue-100 text-sm">Total Pasien Hari Ini</p>
    </div>
</div>

<!-- ===================== CARD STATISTIK ===================== -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-800"><?= $total_menunggu ?></p>
                <p class="text-sm text-slate-500">Pasien Menunggu</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-5">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
            </div>
            <div>
                <p class="text-2xl font-bold text-slate-800"><?= $total_selesai ?></p>
                <p class="text-sm text-slate-500">Sudah Diperiksa</p>
            </div>
        </div>
    </div>

</div>

<!-- ===================== DAFTAR ANTRIAN PASIEN ===================== -->
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800">Antrian Pasien Hari Ini</h2>
        <span class="text-xs text-slate-400"><?= count($daftar_antrian) ?> pasien</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">No.</th>
                    <th class="text-left font-medium px-5 py-3">Nama Pasien</th>
                    <th class="text-left font-medium px-5 py-3">NIM/NIK</th>
                    <th class="text-left font-medium px-5 py-3">Keluhan</th>
                    <th class="text-left font-medium px-5 py-3">Status</th>
                    <th class="text-left font-medium px-5 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($daftar_antrian)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-slate-400 py-8">Belum ada pasien yang mendaftar hari ini</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar_antrian as $antrian): ?>
                        <?php [$label_status, $kelas_warna] = badgeStatusKunjungan($antrian['status_kunjungan']); ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-700">#<?= $antrian['no_antrian'] ?></td>
                            <td class="px-5 py-3 text-slate-700">
                                <?= clean($antrian['nama_pasien']) ?>
                                <span class="block text-xs text-slate-400"><?= $antrian['jenis_kelamin'] ?> &middot; <?= hitungUmur($antrian['tanggal_lahir']) ?></span>
                            </td>
                            <td class="px-5 py-3 text-slate-500"><?= clean($antrian['nim_nik']) ?></td>
                            <td class="px-5 py-3 text-slate-500 max-w-xs truncate"><?= clean($antrian['keluhan_utama'] ?? '-') ?></td>
                            <td class="px-5 py-3">
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium <?= $kelas_warna ?>">
                                    <?= $label_status ?>
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <?php if ($antrian['status_kunjungan'] === 'menunggu'): ?>
                                    <a href="<?= BASE_URL ?>/dokter/rekam_medis/input.php?id_pendaftaran=<?= $antrian['id_pendaftaran'] ?>"
                                       class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        Periksa
                                    </a>
                                <?php elseif ($antrian['status_kunjungan'] === 'selesai'): ?>
                                    <a href="<?= BASE_URL ?>/dokter/rekam_medis/detail.php?id_pendaftaran=<?= $antrian['id_pendaftaran'] ?>"
                                       class="inline-flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium px-3 py-1.5 rounded-lg transition">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        Lihat
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">&mdash;</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>