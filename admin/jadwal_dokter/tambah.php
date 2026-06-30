<?php
/**
 * admin/jadwal_dokter/tambah.php
 * -----------------------------------------------------------
 * Form tambah jadwal dokter + proses simpan ke database.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'jadwal_dokter';
$judul_halaman = 'Tambah Jadwal Dokter';

$error = [];

// Ambil daftar dokter untuk dropdown
$stmt_dokter = $koneksi->query("SELECT id_dokter, nama_dokter, spesialisasi FROM dokter WHERE status_aktif = 'aktif' ORDER BY nama_dokter ASC");
$daftar_dokter = $stmt_dokter->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_dokter    = intval($_POST['id_dokter'] ?? 0);
    $hari         = trim($_POST['hari'] ?? '');
    $jam_mulai    = trim($_POST['jam_mulai'] ?? '');
    $jam_selesai  = trim($_POST['jam_selesai'] ?? '');
    $kuota_pasien = intval($_POST['kuota_pasien'] ?? 20);
    $status       = trim($_POST['status'] ?? 'aktif');

    if (empty($id_dokter))   $error[] = 'Dokter wajib dipilih.';
    if (empty($hari))        $error[] = 'Hari praktik wajib dipilih.';
    if (empty($jam_mulai))   $error[] = 'Jam mulai wajib diisi.';
    if (empty($jam_selesai)) $error[] = 'Jam selesai wajib diisi.';
    if ($kuota_pasien <= 0)  $error[] = 'Kuota pasien harus lebih dari 0.';

    if (strtotime($jam_mulai) >= strtotime($jam_selesai)) {
        $error[] = 'Jam selesai harus lebih besar dari jam mulai.';
    }

    // Cek bentrok jadwal (dokter yang sama di hari yang sama dengan jam beririsan)
    if (empty($error)) {
        $stmt_cek = $koneksi->prepare("
            SELECT id_jadwal FROM jadwal_dokter 
            WHERE id_dokter = ? AND hari = ? 
            AND ((jam_mulai <= ? AND jam_selesai > ?) OR (jam_mulai < ? AND jam_selesai >= ?))
        ");
        $stmt_cek->execute([$id_dokter, $hari, $jam_mulai, $jam_mulai, $jam_selesai, $jam_selesai]);
        if ($stmt_cek->fetch()) {
            $error[] = 'Dokter tersebut sudah memiliki jadwal yang beririsan pada hari dan jam tersebut.';
        }
    }

    if (empty($error)) {
        try {
            $stmt = $koneksi->prepare("
                INSERT INTO jadwal_dokter (id_dokter, hari, jam_mulai, jam_selesai, kuota_pasien, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $id_dokter,
                $hari,
                $jam_mulai,
                $jam_selesai,
                $kuota_pasien,
                $status
            ]);

            redirectDenganPesan('index.php', 'Jadwal praktik berhasil ditambahkan.', 'success');
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
        Kembali ke Jadwal Dokter
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
                
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Pilih Dokter <span class="text-red-500">*</span></label>
                    <select name="id_dokter" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">-- Pilih Dokter --</option>
                        <?php foreach ($daftar_dokter as $dokter): ?>
                            <?php $selected = (($_POST['id_dokter'] ?? '') == $dokter['id_dokter']) ? 'selected' : ''; ?>
                            <option value="<?= $dokter['id_dokter'] ?>" <?= $selected ?>>
                                <?= clean($dokter['nama_dokter']) ?> - <?= clean($dokter['spesialisasi']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Hari Praktik <span class="text-red-500">*</span></label>
                    <select name="hari" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">-- Pilih Hari --</option>
                        <?php 
                        $hari_list = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
                        foreach ($hari_list as $h) {
                            $selected = (($_POST['hari'] ?? '') == $h) ? 'selected' : '';
                            echo "<option value=\"$h\" $selected>$h</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Status Jadwal</label>
                    <select name="status" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="aktif" <?= (($_POST['status'] ?? '') === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= (($_POST['status'] ?? '') === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Jam Mulai <span class="text-red-500">*</span></label>
                    <input type="time" name="jam_mulai" required value="<?= clean($_POST['jam_mulai'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Jam Selesai <span class="text-red-500">*</span></label>
                    <input type="time" name="jam_selesai" required value="<?= clean($_POST['jam_selesai'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Kuota Pasien <span class="text-red-500">*</span></label>
                    <input type="number" name="kuota_pasien" required min="1" value="<?= clean($_POST['kuota_pasien'] ?? 20) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

            </div>
        </div>

        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex items-center gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                Simpan Jadwal Dokter
            </button>
            <a href="index.php" class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2.5">Batal</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
