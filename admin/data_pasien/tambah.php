<?php
/**
 * admin/data_pasien/tambah.php
 * -----------------------------------------------------------
 * Form tambah pasien baru + proses simpan ke database.
 * Satu file ini menangani baik tampilan form maupun proses POST-nya.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'data_pasien';
$judul_halaman = 'Tambah Pasien';

$error = [];

// ============================================================
// PROSES SIMPAN (jika form di-submit)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nim_nik         = trim($_POST['nim_nik'] ?? '');
    $nama_lengkap    = trim($_POST['nama_lengkap'] ?? '');
    $jenis_kelamin   = trim($_POST['jenis_kelamin'] ?? '');
    $tanggal_lahir   = trim($_POST['tanggal_lahir'] ?? '');
    $alamat          = trim($_POST['alamat'] ?? '');
    $no_telepon      = trim($_POST['no_telepon'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $fakultas        = trim($_POST['fakultas'] ?? '');
    $status_akademik = trim($_POST['status_akademik'] ?? '');

    // Validasi sederhana
    if (empty($nim_nik))       $error[] = 'NIM/NIK wajib diisi.';
    if (empty($nama_lengkap))  $error[] = 'Nama lengkap wajib diisi.';
    if (empty($jenis_kelamin)) $error[] = 'Jenis kelamin wajib dipilih.';

    // Cek apakah NIM/NIK sudah terdaftar
    if (empty($error)) {
        $stmt = $koneksi->prepare("SELECT id_pasien FROM pasien WHERE nim_nik = ?");
        $stmt->execute([$nim_nik]);
        if ($stmt->fetch()) {
            $error[] = 'NIM/NIK ini sudah terdaftar di sistem.';
        }
    }

    // Jika lolos validasi, simpan ke database
    if (empty($error)) {
        $stmt = $koneksi->prepare("
            INSERT INTO pasien
                (nim_nik, nama_lengkap, jenis_kelamin, tanggal_lahir, alamat, no_telepon, email, fakultas, status_akademik)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nim_nik,
            $nama_lengkap,
            $jenis_kelamin,
            $tanggal_lahir ?: null,
            $alamat ?: null,
            $no_telepon ?: null,
            $email ?: null,
            $fakultas ?: null,
            $status_akademik ?: 'Mahasiswa',
        ]);

        redirectDenganPesan('index.php', 'Data pasien "' . $nama_lengkap . '" berhasil ditambahkan.', 'success');
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl">

    <!-- Tombol Kembali -->
    <a href="index.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-4">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Kembali ke Data Pasien
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

    <form method="POST" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- NIM/NIK -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">NIM / NIK <span class="text-red-500">*</span></label>
                <input type="text" name="nim_nik" required value="<?= clean($_POST['nim_nik'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>

            <!-- Nama Lengkap -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Lengkap <span class="text-red-500">*</span></label>
                <input type="text" name="nama_lengkap" required value="<?= clean($_POST['nama_lengkap'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>

            <!-- Jenis Kelamin -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Jenis Kelamin <span class="text-red-500">*</span></label>
                <select name="jenis_kelamin" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="">-- Pilih --</option>
                    <option value="Laki-laki" <?= (($_POST['jenis_kelamin'] ?? '') === 'Laki-laki') ? 'selected' : '' ?>>Laki-laki</option>
                    <option value="Perempuan" <?= (($_POST['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : '' ?>>Perempuan</option>
                </select>
            </div>

            <!-- Tanggal Lahir -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal Lahir</label>
                <input type="date" name="tanggal_lahir" value="<?= clean($_POST['tanggal_lahir'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>

            <!-- Status Akademik -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Status Akademik</label>
                <select name="status_akademik" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <option value="Mahasiswa">Mahasiswa</option>
                    <option value="Dosen">Dosen</option>
                    <option value="Tenaga Kependidikan">Tenaga Kependidikan</option>
                </select>
            </div>

            <!-- Fakultas / Unit -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Fakultas / Program Studi / Unit</label>
                <input type="text" name="fakultas" value="<?= clean($_POST['fakultas'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>

            <!-- No Telepon -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">No. Telepon</label>
                <input type="text" name="no_telepon" value="<?= clean($_POST['no_telepon'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                <input type="email" name="email" value="<?= clean($_POST['email'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
            </div>
        </div>

        <!-- Alamat -->
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Alamat</label>
            <textarea name="alamat" rows="3"
                      class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"><?= clean($_POST['alamat'] ?? '') ?></textarea>
        </div>

        <!-- Tombol Aksi -->
        <div class="flex items-center gap-3 pt-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                Simpan Data Pasien
            </button>
            <a href="index.php" class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2.5">Batal</a>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>