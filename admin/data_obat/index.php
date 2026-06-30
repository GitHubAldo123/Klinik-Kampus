<?php
/**
 * admin/data_obat/index.php
 * -----------------------------------------------------------
 * Menampilkan daftar seluruh obat dengan fitur pencarian.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'data_obat';
$judul_halaman = 'Data Obat';

// Ambil keyword pencarian dari URL (?cari=...)
$cari = trim($_GET['cari'] ?? '');

if ($cari !== '') {
    $stmt = $koneksi->prepare("
        SELECT * FROM obat
        WHERE nama_obat LIKE ? OR jenis_obat LIKE ?
        ORDER BY nama_obat ASC
    ");
    $keyword = "%$cari%";
    $stmt->execute([$keyword, $keyword]);
} else {
    $stmt = $koneksi->query("SELECT * FROM obat ORDER BY nama_obat ASC");
}
$daftar_obat = $stmt->fetchAll();

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
            placeholder="Cari nama atau jenis obat..."
            class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
        >
    </form>

    <a href="tambah.php" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition flex-shrink-0">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
        Tambah Obat
    </a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">Nama Obat</th>
                    <th class="text-left font-medium px-5 py-3">Jenis</th>
                    <th class="text-center font-medium px-5 py-3">Stok</th>
                    <th class="text-left font-medium px-5 py-3">Kadaluarsa</th>
                    <th class="text-right font-medium px-5 py-3">Harga</th>
                    <th class="text-right font-medium px-5 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($daftar_obat)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-slate-400 py-8">
                            <?= $cari !== '' ? 'Tidak ada obat yang cocok dengan pencarian "' . clean($cari) . '"' : 'Belum ada data obat' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar_obat as $obat): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-700"><?= clean($obat['nama_obat']) ?></td>
                            <td class="px-5 py-3 text-slate-600"><?= clean($obat['jenis_obat']) ?></td>
                            <td class="px-5 py-3 text-center">
                                <?php if ($obat['stok'] <= $obat['stok_minimum']): ?>
                                    <span class="inline-block px-2 py-1 rounded bg-red-100 text-red-700 font-bold" title="Stok kritis!">
                                        <?= clean($obat['stok']) ?> <?= clean($obat['satuan']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-600">
                                        <?= clean($obat['stok']) ?> <?= clean($obat['satuan']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3">
                                <?php 
                                    $tgl_kadaluarsa = $obat['kadaluarsa'];
                                    $is_expired = $tgl_kadaluarsa < date('Y-m-d');
                                    $class_date = $is_expired ? 'text-red-600 font-medium' : 'text-slate-600';
                                ?>
                                <span class="<?= $class_date ?>"><?= clean($tgl_kadaluarsa ?: '-') ?></span>
                            </td>
                            <td class="px-5 py-3 text-right text-slate-600">
                                Rp <?= number_format($obat['harga'], 0, ',', '.') ?>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="edit.php?id=<?= $obat['id_obat'] ?>" title="Edit"
                                       class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600 transition">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </a>
                                    <form action="hapus.php" method="POST" class="inline" onsubmit="return konfirmasiHapus('Hapus data obat <?= clean($obat['nama_obat']) ?>?');">
                                        <input type="hidden" name="id_obat" value="<?= $obat['id_obat'] ?>">
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

<p class="text-sm text-slate-400 mt-3">Menampilkan <?= count($daftar_obat) ?> obat</p>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
