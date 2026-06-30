<?php
/**
 * dokter/rekam_medis/index.php
 * -----------------------------------------------------------
 * Menampilkan daftar antrean pasien (pendaftaran) untuk dokter 
 * yang sedang login. Dokter dapat memilih pasien untuk diperiksa.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('dokter');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'rekam_medis';
$judul_halaman = 'Antrean Pasien & Rekam Medis';

$id_dokter = $_SESSION['id_ref'];
$tanggal_hari_ini = date('Y-m-d');

// Filter (default: hari ini)
$filter = $_GET['filter'] ?? 'hari_ini';

$sql = "
    SELECT p.*, ps.nama_lengkap, ps.nim_nik, ps.jenis_kelamin 
    FROM pendaftaran p
    JOIN pasien ps ON p.id_pasien = ps.id_pasien
    WHERE p.id_dokter = ?
";
$params = [$id_dokter];

if ($filter === 'hari_ini') {
    $sql .= " AND p.tanggal_kunjungan = ?";
    $params[] = $tanggal_hari_ini;
} elseif ($filter === 'menunggu') {
    $sql .= " AND p.status_kunjungan IN ('menunggu', 'diperiksa')";
}

$sql .= " ORDER BY p.tanggal_kunjungan DESC, p.no_antrian ASC";

$stmt = $koneksi->prepare($sql);
$stmt->execute($params);
$daftar_antrean = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<?php tampilkanFlashMessage(); ?>

<!-- Tab Filter -->
<div class="mb-5 flex border-b border-slate-200">
    <a href="?filter=hari_ini" class="px-5 py-3 text-sm font-medium <?= $filter === 'hari_ini' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-500 hover:text-slate-700' ?>">
        Antrean Hari Ini
    </a>
    <a href="?filter=menunggu" class="px-5 py-3 text-sm font-medium <?= $filter === 'menunggu' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-500 hover:text-slate-700' ?>">
        Belum Diperiksa (Semua Waktu)
    </a>
    <a href="?filter=semua" class="px-5 py-3 text-sm font-medium <?= $filter === 'semua' ? 'text-blue-600 border-b-2 border-blue-600' : 'text-slate-500 hover:text-slate-700' ?>">
        Riwayat Seluruh Pasien
    </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3 w-20">No. Antrean</th>
                    <th class="text-left font-medium px-5 py-3">Tanggal / Waktu</th>
                    <th class="text-left font-medium px-5 py-3">Nama Pasien</th>
                    <th class="text-left font-medium px-5 py-3">Keluhan Awal</th>
                    <th class="text-center font-medium px-5 py-3">Status</th>
                    <th class="text-right font-medium px-5 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($daftar_antrean)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-slate-400 py-8">
                            Tidak ada pasien pada kategori ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar_antrean as $antrean): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-700 font-bold">
                                    <?= clean($antrean['no_antrian']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">
                                <?= formatTanggal($antrean['tanggal_kunjungan']) ?><br>
                                <span class="text-xs text-slate-400">Didaftarkan: <?= date('H:i', strtotime($antrean['waktu_daftar'])) ?></span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-700"><?= clean($antrean['nama_lengkap']) ?></div>
                                <div class="text-xs text-slate-500"><?= clean($antrean['nim_nik']) ?> • <?= clean($antrean['jenis_kelamin']) ?></div>
                            </td>
                            <td class="px-5 py-3 text-slate-600 max-w-xs truncate" title="<?= clean($antrean['keluhan_utama']) ?>">
                                <?= clean($antrean['keluhan_utama'] ?: '-') ?>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <?php 
                                    $status = $antrean['status_kunjungan'];
                                    $badgeClass = 'bg-slate-100 text-slate-600';
                                    if ($status == 'menunggu') $badgeClass = 'bg-amber-100 text-amber-700';
                                    else if ($status == 'diperiksa') $badgeClass = 'bg-blue-100 text-blue-700 animate-pulse';
                                    else if ($status == 'selesai') $badgeClass = 'bg-emerald-100 text-emerald-700';
                                    else if ($status == 'batal') $badgeClass = 'bg-red-100 text-red-700';
                                ?>
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium <?= $badgeClass ?> capitalize">
                                    <?= clean($status) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <?php if ($status === 'menunggu' || $status === 'diperiksa'): ?>
                                    <a href="input.php?id_pendaftaran=<?= $antrean['id_pendaftaran'] ?>" 
                                       class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                                        Periksa
                                    </a>
                                <?php elseif ($status === 'selesai'): ?>
                                    <a href="detail.php?id_pendaftaran=<?= $antrean['id_pendaftaran'] ?>" 
                                       class="inline-flex items-center gap-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-medium px-3 py-1.5 rounded-lg border border-emerald-200 transition">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        Lihat RM
                                    </a>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">Dibatalkan</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-sm text-slate-400 mt-3">Menampilkan <?= count($daftar_antrean) ?> pasien</p>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
