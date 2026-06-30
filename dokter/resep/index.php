<?php
/**
 * dokter/resep/index.php
 * -----------------------------------------------------------
 * Menampilkan daftar resep yang pernah dibuat oleh dokter.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('dokter');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'resep';
$judul_halaman = 'Riwayat Resep Obat';

$id_dokter = $_SESSION['id_ref'];
$cari = trim($_GET['cari'] ?? '');

$sql = "
    SELECT r.id_resep, r.tanggal_resep, r.status_resep, ps.nama_lengkap, ps.nim_nik, rm.diagnosis
    FROM resep r
    JOIN rekam_medis rm ON r.id_rekam_medis = rm.id_rekam_medis
    JOIN pendaftaran p ON rm.id_pendaftaran = p.id_pendaftaran
    JOIN pasien ps ON p.id_pasien = ps.id_pasien
    WHERE rm.id_dokter = ?
";
$params = [$id_dokter];

if ($cari !== '') {
    $sql .= " AND (ps.nama_lengkap LIKE ? OR rm.diagnosis LIKE ?)";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}

$sql .= " ORDER BY r.tanggal_resep DESC";

$stmt = $koneksi->prepare($sql);
$stmt->execute($params);
$daftar_resep = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<?php tampilkanFlashMessage(); ?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
    
    <form method="GET" class="relative w-full sm:max-w-xs">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        </span>
        <input
            type="text"
            name="cari"
            value="<?= clean($cari) ?>"
            placeholder="Cari nama pasien atau diagnosis..."
            class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
        >
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3 w-16">ID Resep</th>
                    <th class="text-left font-medium px-5 py-3">Tanggal Resep</th>
                    <th class="text-left font-medium px-5 py-3">Nama Pasien</th>
                    <th class="text-left font-medium px-5 py-3">Diagnosis</th>
                    <th class="text-center font-medium px-5 py-3">Status Apotek</th>
                    <th class="text-right font-medium px-5 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($daftar_resep)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-slate-400 py-8">
                            <?= $cari !== '' ? 'Tidak ada resep yang cocok dengan pencarian "' . clean($cari) . '"' : 'Belum ada riwayat resep obat' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar_resep as $resep): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-slate-700 text-center">
                                #<?= clean($resep['id_resep']) ?>
                            </td>
                            <td class="px-5 py-3 text-slate-600 whitespace-nowrap">
                                <?= date('d M Y, H:i', strtotime($resep['tanggal_resep'])) ?>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-700"><?= clean($resep['nama_lengkap']) ?></div>
                                <div class="text-xs text-slate-500"><?= clean($resep['nim_nik']) ?></div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                <?= clean($resep['diagnosis']) ?>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <?php if ($resep['status_resep'] === 'sudah_diambil'): ?>
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Sudah Diambil</span>
                                <?php else: ?>
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Belum Diambil</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="tambah.php?id_rekam_medis=<?= $resep['id_resep'] ?>" class="inline-flex items-center gap-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 text-xs font-medium px-3 py-1.5 rounded-lg transition">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    Detail Resep
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
