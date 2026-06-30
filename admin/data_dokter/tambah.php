<?php
/**
 * admin/data_dokter/tambah.php
 * -----------------------------------------------------------
 * Form tambah dokter baru + proses simpan ke database.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'data_dokter';
$judul_halaman = 'Tambah Dokter';

$error = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nip_dokter   = trim($_POST['nip_dokter'] ?? '');
    $nama_dokter  = trim($_POST['nama_dokter'] ?? '');
    $spesialisasi = trim($_POST['spesialisasi'] ?? '');
    $no_telepon   = trim($_POST['no_telepon'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $status_aktif = trim($_POST['status_aktif'] ?? 'aktif');
    
    // Untuk login (users table)
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validasi sederhana
    if (empty($nama_dokter))  $error[] = 'Nama dokter wajib diisi.';
    if (empty($spesialisasi)) $error[] = 'Spesialisasi wajib diisi.';
    if (empty($username))     $error[] = 'Username wajib diisi untuk akses login.';
    if (empty($password))     $error[] = 'Password wajib diisi.';

    // Cek NIP jika diisi
    if (!empty($nip_dokter) && empty($error)) {
        $stmt = $koneksi->prepare("SELECT id_dokter FROM dokter WHERE nip_dokter = ?");
        $stmt->execute([$nip_dokter]);
        if ($stmt->fetch()) {
            $error[] = 'NIP/SIP ini sudah terdaftar.';
        }
    }
    
    // Cek Username
    if (empty($error)) {
        $stmt = $koneksi->prepare("SELECT id_user FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error[] = 'Username sudah digunakan, silakan pilih yang lain.';
        }
    }

    // Jika lolos validasi, simpan
    if (empty($error)) {
        try {
            $koneksi->beginTransaction();
            
            // 1. Simpan ke tabel dokter
            $stmt = $koneksi->prepare("
                INSERT INTO dokter (nip_dokter, nama_dokter, spesialisasi, no_telepon, email, status_aktif)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $nip_dokter ?: null,
                $nama_dokter,
                $spesialisasi,
                $no_telepon ?: null,
                $email ?: null,
                $status_aktif
            ]);
            
            $id_dokter = $koneksi->lastInsertId();
            
            // 2. Simpan ke tabel users
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt_user = $koneksi->prepare("
                INSERT INTO users (username, password, role, id_ref, email, status_aktif)
                VALUES (?, ?, 'dokter', ?, ?, ?)
            ");
            $stmt_user->execute([
                $username,
                $hashed_password,
                $id_dokter,
                $email ?: null,
                $status_aktif
            ]);
            
            $koneksi->commit();
            redirectDenganPesan('index.php', 'Data dokter "' . $nama_dokter . '" berhasil ditambahkan.', 'success');
        } catch (Exception $e) {
            $koneksi->rollBack();
            $error[] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl">

    <!-- Tombol Kembali -->
    <a href="index.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-4">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Kembali ke Data Dokter
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
            <h3 class="font-medium text-slate-800 border-b border-slate-100 pb-2 mb-4">Informasi Profil Dokter</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- NIP/SIP -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">NIP / SIP</label>
                    <input type="text" name="nip_dokter" value="<?= clean($_POST['nip_dokter'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <!-- Nama Dokter -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Dokter <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_dokter" required value="<?= clean($_POST['nama_dokter'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <!-- Spesialisasi -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Spesialisasi <span class="text-red-500">*</span></label>
                    <input type="text" name="spesialisasi" required value="<?= clean($_POST['spesialisasi'] ?? 'Dokter Umum') ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <!-- Status Aktif -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Status Aktif</label>
                    <select name="status_aktif" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="aktif" <?= (($_POST['status_aktif'] ?? '') === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= (($_POST['status_aktif'] ?? '') === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
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
            
            <h3 class="font-medium text-slate-800 border-b border-slate-100 pb-2 mt-6 mb-4">Informasi Akun Login</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Username -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" required value="<?= clean($_POST['username'] ?? '') ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Password <span class="text-red-500">*</span></label>
                    <input type="password" name="password" required
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
        </div>

        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex items-center gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                Simpan Data Dokter
            </button>
            <a href="index.php" class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2.5">Batal</a>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
