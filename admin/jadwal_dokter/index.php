<?php
/**
 * admin/jadwal_dokter/index.php
 * -----------------------------------------------------------
 * Menampilkan jadwal praktik seluruh dokter.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'jadwal_dokter';
$judul_halaman = 'Jadwal Dokter';

$cari = trim($_GET['cari'] ?? '');

$sql = "
    SELECT j.*, d.nama_dokter, d.spesialisasi 
    FROM jadwal_dokter j
    JOIN dokter d ON j.id_dokter = d.id_dokter
";

if ($cari !== '') {
    $sql .= " WHERE d.nama_dokter LIKE ? OR j.hari LIKE ?";
    $sql .= " ORDER BY FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam_mulai ASC";
    $stmt = $koneksi->prepare($sql);
    $keyword = "%$cari%";
    $stmt->execute([$keyword, $keyword]);
} else {
    $sql .= " ORDER BY d.nama_dokter ASC, FIELD(j.hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), j.jam_mulai ASC";
    $stmt = $koneksi->query($sql);
}

$daftar_jadwal = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<?php tampilkanFlashMessage(); ?>

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
    
    <form method="GET" class="relative w-full sm:max-w-xs">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        </span>
        <input
            type="text"
            name="cari"
            value="<?= clean($cari) ?>"
            placeholder="Cari nama dokter atau hari..."
            class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
        >
    </form>

    <a href="tambah.php" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition flex-shrink-0">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Tambah Jadwal
    </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">Nama Dokter</th>
                    <th class="text-left font-medium px-5 py-3">Spesialisasi</th>
                    <th class="text-left font-medium px-5 py-3">Hari</th>
                    <th class="text-center font-medium px-5 py-3">Jam Praktik</th>
                    <th class="text-center font-medium px-5 py-3">Kuota</th>
                    <th class="text-center font-medium px-5 py-3">Status</th>
                    <th class="text-right font-medium px-5 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($daftar_jadwal)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-slate-400 py-8">
                            <?= $cari !== '' ? 'Tidak ada jadwal yang cocok dengan pencarian "' . clean($cari) . '"' : 'Belum ada data jadwal dokter' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar_jadwal as $jadwal): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-700"><?= clean($jadwal['nama_dokter']) ?></td>
                            <td class="px-5 py-3 text-slate-600"><?= clean($jadwal['spesialisasi']) ?></td>
                            <td class="px-5 py-3 font-medium text-blue-600"><?= clean($jadwal['hari']) ?></td>
                            <td class="px-5 py-3 text-center text-slate-600">
                                <?= date('H:i', strtotime($jadwal['jam_mulai'])) ?> - <?= date('H:i', strtotime($jadwal['jam_selesai'])) ?>
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600"><?= clean($jadwal['kuota_pasien']) ?> pasien</td>
                            <td class="px-5 py-3 text-center">
                                <?php if ($jadwal['status'] === 'aktif'): ?>
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Aktif</span>
                                <?php else: ?>
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <a href="edit.php?id=<?= $jadwal['id_jadwal'] ?>" title="Edit"
                                       class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600 transition">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </a>
                                    <form action="hapus.php" method="POST" class="inline" onsubmit="return konfirmasiHapus('Hapus jadwal hari <?= clean($jadwal['hari']) ?> untuk dr. <?= clean($jadwal['nama_dokter']) ?>?');">
                                        <input type="hidden" name="id_jadwal" value="<?= $jadwal['id_jadwal'] ?>">
                                        <button type="submit" title="Hapus" class="p-1.5 rounded-lg hover:bg-red-50 text-red-600 transition">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<p class="text-sm text-slate-400 mt-3">Menampilkan <?= count($daftar_jadwal) ?> jadwal praktik</p>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
