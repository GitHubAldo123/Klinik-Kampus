<?php
/**
 * admin/data_obat/tambah.php
 * -----------------------------------------------------------
 * Form tambah obat baru + proses simpan ke database.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'data_obat';
$judul_halaman = 'Tambah Obat';

$error = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_obat    = trim($_POST['nama_obat'] ?? '');
    $jenis_obat   = trim($_POST['jenis_obat'] ?? '');
    $satuan       = trim($_POST['satuan'] ?? 'pcs');
    $stok         = intval($_POST['stok'] ?? 0);
    $stok_minimum = intval($_POST['stok_minimum'] ?? 10);
    $kadaluarsa   = trim($_POST['kadaluarsa'] ?? '');
    $harga        = floatval($_POST['harga'] ?? 0);

    if (empty($nama_obat)) $error[] = 'Nama obat wajib diisi.';
    if ($stok < 0)         $error[] = 'Stok tidak boleh negatif.';
    if ($harga < 0)        $error[] = 'Harga tidak boleh negatif.';

    // Cek duplikasi nama obat
    if (empty($error)) {
        $stmt_cek = $koneksi->prepare("SELECT id_obat FROM obat WHERE nama_obat = ?");
        $stmt_cek->execute([$nama_obat]);
        if ($stmt_cek->fetch()) {
            $error[] = 'Obat dengan nama tersebut sudah ada di database.';
        }
    }

    if (empty($error)) {
        try {
            $stmt = $koneksi->prepare("
                INSERT INTO obat (nama_obat, jenis_obat, satuan, stok, stok_minimum, kadaluarsa, harga)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nama_obat,
                $jenis_obat ?: null,
                $satuan,
                $stok,
                $stok_minimum,
                $kadaluarsa ?: null,
                $harga
            ]);

            redirectDenganPesan('index.php', 'Data obat "' . $nama_obat . '" berhasil ditambahkan.', 'success');
        } catch (PDOException $e) {
            $error[] = 'Terjadi kesalahan database: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl">
    <a href="index.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-4">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Kembali ke Data Obat
    </a>

    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded mb-5">
            <ul class="list-disc list-inside text-sm space-y-1">
                <?php foreach ($error as $pesan): ?>
                    <li><?= clean($pesan) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Obat <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_obat" required value="<?= clean($_POST['nama_obat'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Jenis Obat</label>
                    <input type="text" name="jenis_obat" value="<?= clean($_POST['jenis_obat'] ?? '') ?>" placeholder="Misal: Tablet, Sirup, Salep"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Satuan</label>
                    <input type="text" name="satuan" value="<?= clean($_POST['satuan'] ?? 'pcs') ?>" placeholder="Misal: pcs, botol, tube"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal Kadaluarsa</label>
                    <input type="date" name="kadaluarsa" value="<?= clean($_POST['kadaluarsa'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Stok Awal <span class="text-red-500">*</span></label>
                    <input type="number" name="stok" required min="0" value="<?= clean($_POST['stok'] ?? 0) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Stok Minimum (Peringatan)</label>
                    <input type="number" name="stok_minimum" min="0" value="<?= clean($_POST['stok_minimum'] ?? 10) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Harga Obat (Rp)</label>
                    <input type="number" name="harga" min="0" value="<?= clean($_POST['harga'] ?? 0) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
        </div>

        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex items-center gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                Simpan Data Obat
            </button>
            <a href="index.php" class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2.5">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
