<?php
/**
 * admin/pendaftaran/index.php
 * -----------------------------------------------------------
 * Menampilkan daftar antrean (pendaftaran) pasien.
 * Admin mendaftarkan pasien ke jadwal dokter yang aktif.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'pendaftaran';
$judul_halaman = 'Pendaftaran & Antrean Pasien';

$cari = trim($_GET['cari'] ?? '');
$filter = $_GET['filter'] ?? 'hari_ini';

$sql = "
    SELECT p.*, ps.nama_lengkap as nama_pasien, ps.nim_nik, d.nama_dokter, j.jam_mulai, j.jam_selesai
    FROM pendaftaran p
    JOIN pasien ps ON p.id_pasien = ps.id_pasien
    JOIN dokter d ON p.id_dokter = d.id_dokter
    JOIN jadwal_dokter j ON p.id_jadwal = j.id_jadwal
";
$params = [];

if ($filter === 'hari_ini') {
    $sql .= " WHERE p.tanggal_kunjungan = CURDATE()";
} else {
    $sql .= " WHERE 1=1";
}

if ($cari !== '') {
    $sql .= " AND (ps.nama_lengkap LIKE ? OR d.nama_dokter LIKE ?)";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}

$sql .= " ORDER BY p.tanggal_kunjungan DESC, p.no_antrian ASC";
$stmt = $koneksi->prepare($sql);
$stmt->execute(empty($params) ? [] : $params);
$daftar_pendaftaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../includes/header.php';
?>

<?php tampilkanFlashMessage(); ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    
    <div class="flex gap-2 w-full sm:w-auto">
        <form method="GET" class="relative w-full sm:max-w-xs flex-1">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            </span>
            <input type="hidden" name="filter" value="<?= clean($filter) ?>">
            <input
                type="text"
                name="cari"
                value="<?= clean($cari) ?>"
                placeholder="Cari pasien atau dokter..."
                class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
            >
        </form>
        
        <div class="flex bg-slate-100 rounded-lg p-1 border border-slate-200 flex-shrink-0">
            <a href="?filter=hari_ini&cari=<?= urlencode($cari) ?>" class="px-3 py-1.5 text-xs font-medium rounded-md <?= $filter === 'hari_ini' ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500 hover:text-slate-700' ?>">Hari Ini</a>
            <a href="?filter=semua&cari=<?= urlencode($cari) ?>" class="px-3 py-1.5 text-xs font-medium rounded-md <?= $filter === 'semua' ? 'bg-white shadow-sm text-slate-800' : 'text-slate-500 hover:text-slate-700' ?>">Semua Data</a>
        </div>
    </div>

    <a href="tambah.php" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition flex-shrink-0">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Pendaftaran Pasien
    </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3 w-20">Antrean</th>
                    <th class="text-left font-medium px-5 py-3">Tanggal Kunjungan</th>
                    <th class="text-left font-medium px-5 py-3">Pasien</th>
                    <th class="text-left font-medium px-5 py-3">Dokter Tujuan</th>
                    <th class="text-center font-medium px-5 py-3">Status</th>
                    <th class="text-right font-medium px-5 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($daftar_pendaftaran)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-slate-400 py-8">
                            Belum ada pendaftaran yang sesuai pencarian/filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar_pendaftaran as $pend): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-700 font-bold">
                                    <?= clean($pend['no_antrian']) ?>
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">
                                <?= formatTanggal($pend['tanggal_kunjungan']) ?><br>
                                <span class="text-xs text-slate-400">Didaftarkan: <?= date('H:i', strtotime($pend['waktu_daftar'])) ?></span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-700"><?= clean($pend['nama_pasien']) ?></div>
                                <div class="text-xs text-slate-500"><?= clean($pend['nim_nik']) ?></div>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-700"><?= clean($pend['nama_dokter']) ?></div>
                                <div class="text-xs text-slate-500"><?= formatJam($pend['jam_mulai']) ?> - <?= formatJam($pend['jam_selesai']) ?></div>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <?php [$label_status, $kelas_warna] = badgeStatusKunjungan($pend['status_kunjungan']); ?>
                                <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium <?= $kelas_warna ?>">
                                    <?= $label_status ?>
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <?php if ($pend['status_kunjungan'] === 'menunggu'): ?>
                                    <form action="hapus.php" method="POST" class="inline" onsubmit="return confirm('Hapus antrean pasien ini?');">
                                        <input type="hidden" name="id_pendaftaran" value="<?= $pend['id_pendaftaran'] ?>">
                                        <button type="submit" title="Batalkan Pendaftaran" class="p-1.5 rounded-lg hover:bg-red-50 text-red-600 transition">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
