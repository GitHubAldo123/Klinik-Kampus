<?php
/**
 * dokter/resep/tambah.php
 * -----------------------------------------------------------
 * Form untuk membuat dan menambah detail resep obat berdasarkan
 * rekam medis pasien.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('dokter');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'resep';
$judul_halaman = 'Buat / Detail Resep Obat';

$id_rekam_medis = $_GET['id_rekam_medis'] ?? null;
$id_dokter = $_SESSION['id_ref'];
$error = [];

if (!$id_rekam_medis) {
    redirectDenganPesan('index.php', 'ID Rekam Medis tidak valid.', 'error');
}

// 1. Validasi akses & ambil info pasien
$sql_info = "
    SELECT rm.id_rekam_medis, rm.diagnosis, ps.nama_lengkap, ps.nim_nik, ps.tanggal_lahir, ps.jenis_kelamin 
    FROM rekam_medis rm
    JOIN pendaftaran p ON rm.id_pendaftaran = p.id_pendaftaran
    JOIN pasien ps ON p.id_pasien = ps.id_pasien
    WHERE rm.id_rekam_medis = ? AND rm.id_dokter = ?
";
$stmt_info = $koneksi->prepare($sql_info);
$stmt_info->execute([$id_rekam_medis, $id_dokter]);
$info = $stmt_info->fetch();

if (!$info) {
    redirectDenganPesan('index.php', 'Rekam medis tidak ditemukan atau bukan pasien Anda.', 'error');
}

// 2. Cek header resep, jika belum ada, buat otomatis
$stmt_cek_resep = $koneksi->prepare("SELECT id_resep, status_resep, catatan_resep FROM resep WHERE id_rekam_medis = ?");
$stmt_cek_resep->execute([$id_rekam_medis]);
$resep = $stmt_cek_resep->fetch();

if (!$resep) {
    $stmt_buat_resep = $koneksi->prepare("INSERT INTO resep (id_rekam_medis) VALUES (?)");
    $stmt_buat_resep->execute([$id_rekam_medis]);
    $id_resep = $koneksi->lastInsertId();
    $status_resep = 'belum_diambil';
} else {
    $id_resep = $resep['id_resep'];
    $status_resep = $resep['status_resep'];
}

// 3. Tangani POST (Tambah Obat atau Hapus Obat)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    
    if ($aksi === 'tambah_obat' && $status_resep === 'belum_diambil') {
        $id_obat       = intval($_POST['id_obat'] ?? 0);
        $jumlah        = intval($_POST['jumlah'] ?? 1);
        $aturan_pakai  = trim($_POST['aturan_pakai'] ?? '');
        $catatan_pakai = trim($_POST['catatan_pakai'] ?? '');

        if ($id_obat <= 0) $error[] = 'Obat belum dipilih.';
        if ($jumlah <= 0)  $error[] = 'Jumlah obat harus lebih dari 0.';

        if (empty($error)) {
            // Cek stok apakah mencukupi
            $stmt_stok = $koneksi->prepare("SELECT stok, nama_obat, satuan FROM obat WHERE id_obat = ?");
            $stmt_stok->execute([$id_obat]);
            $obat_db = $stmt_stok->fetch();

            if (!$obat_db) {
                $error[] = 'Obat tidak ditemukan di database.';
            } elseif ($obat_db['stok'] < $jumlah) {
                $error[] = 'Stok ' . $obat_db['nama_obat'] . ' tidak mencukupi. (Sisa: ' . $obat_db['stok'] . ' ' . $obat_db['satuan'] . ')';
            } else {
                // Tambah ke detail_resep
                $stmt_add = $koneksi->prepare("
                    INSERT INTO detail_resep (id_resep, id_obat, jumlah, aturan_pakai, catatan_pakai)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_add->execute([$id_resep, $id_obat, $jumlah, $aturan_pakai, $catatan_pakai]);
                
                redirectDenganPesan("tambah.php?id_rekam_medis=$id_rekam_medis", "Obat " . $obat_db['nama_obat'] . " berhasil ditambahkan ke resep.", "success");
            }
        }
    } 
    elseif ($aksi === 'hapus_obat' && $status_resep === 'belum_diambil') {
        $id_detail = intval($_POST['id_detail'] ?? 0);
        $stmt_del = $koneksi->prepare("DELETE FROM detail_resep WHERE id_detail = ? AND id_resep = ?");
        $stmt_del->execute([$id_detail, $id_resep]);
        redirectDenganPesan("tambah.php?id_rekam_medis=$id_rekam_medis", "Obat berhasil dihapus dari daftar resep.", "success");
    }
}

// 4. Ambil Daftar Obat (untuk dropdown dropdown)
$stmt_daftar_obat = $koneksi->query("SELECT id_obat, nama_obat, jenis_obat, satuan, stok FROM obat ORDER BY nama_obat ASC");
$daftar_obat = $stmt_daftar_obat->fetchAll();

// 5. Ambil Detail Resep yang sudah diinput
$stmt_detail = $koneksi->prepare("
    SELECT dr.*, o.nama_obat, o.satuan, o.jenis_obat
    FROM detail_resep dr
    JOIN obat o ON dr.id_obat = o.id_obat
    WHERE dr.id_resep = ?
");
$stmt_detail->execute([$id_resep]);
$detail_resep = $stmt_detail->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<?php tampilkanFlashMessage(); ?>

<div class="mb-5 flex items-center justify-between">
    <a href="../rekam_medis/index.php?filter=semua" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Kembali ke Rekam Medis
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Info Pasien & Diagnosis -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-5">
            <h3 class="font-bold text-blue-800 mb-4 border-b border-blue-200 pb-2">Informasi Resep</h3>
            
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-blue-600 font-medium uppercase tracking-wider mb-0.5">Nama Pasien</p>
                    <p class="text-sm font-semibold text-slate-800"><?= clean($info['nama_lengkap']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-blue-600 font-medium uppercase tracking-wider mb-0.5">Umur / Jenis Kelamin</p>
                    <p class="text-sm text-slate-700"><?= hitungUmur($info['tanggal_lahir']) ?> / <?= clean($info['jenis_kelamin']) ?></p>
                </div>
                <div class="pt-2 border-t border-blue-200">
                    <p class="text-xs text-blue-600 font-medium uppercase tracking-wider mb-0.5">Diagnosis</p>
                    <p class="text-sm font-bold text-slate-800"><?= clean($info['diagnosis'] ?: 'Belum ada diagnosis') ?></p>
                </div>
                <div class="pt-2 border-t border-blue-200">
                    <p class="text-xs text-blue-600 font-medium uppercase tracking-wider mb-1">Status Resep Apotek</p>
                    <?php if ($status_resep === 'sudah_diambil'): ?>
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-200 text-emerald-800">Sudah Diambil oleh Pasien</span>
                    <?php else: ?>
                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-bold bg-amber-200 text-amber-800">Menunggu Diambil</span>
                        <p class="text-xs text-amber-700 mt-2 leading-relaxed">Resep yang belum diambil masih dapat Anda modifikasi (tambah/hapus obat).</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Daftar Obat yang Diberikan -->
    <div class="lg:col-span-2 space-y-6">
        
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 flex items-center gap-2">
                    <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                    Daftar Obat dalam Resep
                </h3>
                <div class="flex gap-2">
                    <a href="cetak.php?id_resep=<?= $id_resep ?>" target="_blank" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        Cetak Resep
                    </a>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="text-left font-medium px-4 py-3">Nama Obat</th>
                            <th class="text-center font-medium px-4 py-3">Jumlah</th>
                            <th class="text-left font-medium px-4 py-3">Aturan Pakai</th>
                            <?php if ($status_resep === 'belum_diambil'): ?>
                                <th class="text-right font-medium px-4 py-3 w-16">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($detail_resep)): ?>
                            <tr>
                                <td colspan="<?= $status_resep === 'belum_diambil' ? 4 : 3 ?>" class="text-center text-slate-400 py-6">
                                    Belum ada obat yang ditambahkan.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($detail_resep as $dr): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-700"><?= clean($dr['nama_obat']) ?></div>
                                        <div class="text-xs text-slate-500"><?= clean($dr['jenis_obat']) ?></div>
                                    </td>
                                    <td class="px-4 py-3 text-center text-slate-700 font-semibold">
                                        <?= clean($dr['jumlah']) ?> <span class="text-xs font-normal text-slate-500"><?= clean($dr['satuan']) ?></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-blue-700"><?= clean($dr['aturan_pakai'] ?: '-') ?></div>
                                        <div class="text-xs text-slate-500"><?= clean($dr['catatan_pakai']) ?></div>
                                    </td>
                                    <?php if ($status_resep === 'belum_diambil'): ?>
                                        <td class="px-4 py-3 text-right">
                                            <form method="POST" class="inline" onsubmit="return confirm('Hapus obat ini dari resep?');">
                                                <input type="hidden" name="aksi" value="hapus_obat">
                                                <input type="hidden" name="id_detail" value="<?= $dr['id_detail'] ?>">
                                                <button type="submit" class="p-1 rounded text-red-500 hover:bg-red-50 hover:text-red-700 transition" title="Hapus">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Form Tambah Obat (hanya jika belum diambil) -->
        <?php if ($status_resep === 'belum_diambil'): ?>
            <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
                <div class="p-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="font-bold text-slate-800">Tambahkan Obat ke Resep</h3>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-3 m-4 text-sm rounded">
                        <ul class="list-disc list-inside space-y-1">
                            <?php foreach ($error as $pesan): ?>
                                <li><?= clean($pesan) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" class="p-5">
                    <input type="hidden" name="aksi" value="tambah_obat">
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div class="md:col-span-3">
                            <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Obat (Stok Tersedia) <span class="text-red-500">*</span></label>
                            <select name="id_obat" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                <option value="">-- Cari dan Pilih Obat --</option>
                                <?php foreach ($daftar_obat as $obat): ?>
                                    <option value="<?= $obat['id_obat'] ?>" <?= $obat['stok'] <= 0 ? 'disabled' : '' ?>>
                                        <?= clean($obat['nama_obat']) ?> 
                                        [Stok: <?= clean($obat['stok']) ?> <?= clean($obat['satuan']) ?>]
                                        <?= $obat['stok'] <= 0 ? '(HABIS)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah <span class="text-red-500">*</span></label>
                            <input type="number" name="jumlah" min="1" value="1" required
                                   class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Aturan Pakai <span class="text-red-500">*</span></label>
                            <input type="text" name="aturan_pakai" placeholder="Misal: 3x1 Sesudah Makan" required
                                   class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Tambahan</label>
                            <input type="text" name="catatan_pakai" placeholder="Misal: Bila demam / Habiskan"
                                   class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>
                    </div>
                    
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition w-full flex items-center justify-center gap-2">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Tambahkan Obat
                    </button>
                </form>
            </div>

        <?php endif; ?>

        <?php if ($status_resep === 'belum_diambil'): ?>
            <div class="p-5 mt-6 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-emerald-100 text-emerald-700 font-bold font-serif text-lg">R/</span>
                        Selesai Membuat Resep?
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Pastikan semua obat sudah diinput dengan benar sebelum mengakhiri.</p>
                </div>
                <a href="index.php" onclick="return confirm('Selesai membuat resep?');" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition shadow-sm">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    Selesai & Simpan Resep
                </a>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
