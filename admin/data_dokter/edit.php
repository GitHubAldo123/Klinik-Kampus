<?php
/**
 * admin/data_dokter/edit.php
 * -----------------------------------------------------------
 * Form edit data dokter + proses update ke database.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'data_dokter';
$judul_halaman = 'Edit Dokter';

$error = [];
$id = $_GET['id'] ?? null;

if (!$id) {
    redirectDenganPesan('index.php', 'ID Dokter tidak ditemukan.', 'error');
}

// Ambil data dokter
$stmt = $koneksi->prepare("SELECT * FROM dokter WHERE id_dokter = ?");
$stmt->execute([$id]);
$dokter = $stmt->fetch();

if (!$dokter) {
    redirectDenganPesan('index.php', 'Data dokter tidak ditemukan.', 'error');
}

// Ambil data akun login dokter
$stmt_user = $koneksi->prepare("SELECT id_user, username FROM users WHERE role = 'dokter' AND id_ref = ?");
$stmt_user->execute([$id]);
$akun = $stmt_user->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip_dokter   = trim($_POST['nip_dokter'] ?? '');
    $nama_dokter  = trim($_POST['nama_dokter'] ?? '');
    $spesialisasi = trim($_POST['spesialisasi'] ?? '');
    $no_telepon   = trim($_POST['no_telepon'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $status_aktif = trim($_POST['status_aktif'] ?? 'aktif');
    
    // Data login
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validasi
    if (empty($nama_dokter))  $error[] = 'Nama dokter wajib diisi.';
    if (empty($spesialisasi)) $error[] = 'Spesialisasi wajib diisi.';
    if (empty($username))     $error[] = 'Username wajib diisi.';

    // Cek NIP
    if (!empty($nip_dokter) && empty($error)) {
        $stmt_cek = $koneksi->prepare("SELECT id_dokter FROM dokter WHERE nip_dokter = ? AND id_dokter != ?");
        $stmt_cek->execute([$nip_dokter, $id]);
        if ($stmt_cek->fetch()) {
            $error[] = 'NIP/SIP ini sudah terdaftar pada dokter lain.';
        }
    }
    
    // Cek Username
    if (empty($error)) {
        // Abaikan user id dari dokter ini
        $id_user_exclude = $akun ? $akun['id_user'] : 0;
        
        $stmt_cek_username = $koneksi->prepare("SELECT id_user FROM users WHERE username = ? AND id_user != ?");
        $stmt_cek_username->execute([$username, $id_user_exclude]);
        if ($stmt_cek_username->fetch()) {
            $error[] = 'Username sudah digunakan, silakan pilih yang lain.';
        }
    }

    if (empty($error)) {
        try {
            $koneksi->beginTransaction();
            
            // 1. Update dokter
            $stmt_update = $koneksi->prepare("
                UPDATE dokter SET 
                    nip_dokter = ?, 
                    nama_dokter = ?, 
                    spesialisasi = ?, 
                    no_telepon = ?, 
                    email = ?, 
                    status_aktif = ?
                WHERE id_dokter = ?
            ");
            $stmt_update->execute([
                $nip_dokter ?: null,
                $nama_dokter,
                $spesialisasi,
                $no_telepon ?: null,
                $email ?: null,
                $status_aktif,
                $id
            ]);
            
            // 2. Update akun user
            if ($akun) {
                if (!empty($password)) {
                    // Update password juga
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_up_user = $koneksi->prepare("UPDATE users SET username = ?, password = ?, email = ?, status_aktif = ? WHERE id_user = ?");
                    $stmt_up_user->execute([$username, $hashed_password, $email ?: null, $status_aktif, $akun['id_user']]);
                } else {
                    // Hanya update username & email
                    $stmt_up_user = $koneksi->prepare("UPDATE users SET username = ?, email = ?, status_aktif = ? WHERE id_user = ?");
                    $stmt_up_user->execute([$username, $email ?: null, $status_aktif, $akun['id_user']]);
                }
            } else {
                // Jika entah kenapa akun belum ada, buat baru
                $hashed_password = password_hash($password ?: 'dokter123', PASSWORD_DEFAULT);
                $stmt_ins_user = $koneksi->prepare("
                    INSERT INTO users (username, password, role, id_ref, email, status_aktif)
                    VALUES (?, ?, 'dokter', ?, ?, ?)
                ");
                $stmt_ins_user->execute([$username, $hashed_password, $id, $email ?: null, $status_aktif]);
            }
            
            $koneksi->commit();
            redirectDenganPesan('index.php', 'Data dokter "' . $nama_dokter . '" berhasil diperbarui.', 'success');
        } catch (Exception $e) {
            $koneksi->rollBack();
            $error[] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl">

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
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">NIP / SIP</label>
                    <input type="text" name="nip_dokter" value="<?= clean($_POST['nip_dokter'] ?? $dokter['nip_dokter']) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Nama Dokter <span class="text-red-500">*</span></label>
                    <input type="text" name="nama_dokter" required value="<?= clean($_POST['nama_dokter'] ?? $dokter['nama_dokter']) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Spesialisasi <span class="text-red-500">*</span></label>
                    <input type="text" name="spesialisasi" required value="<?= clean($_POST['spesialisasi'] ?? $dokter['spesialisasi']) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Status Aktif</label>
                    <select name="status_aktif" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="aktif" <?= (($_POST['status_aktif'] ?? $dokter['status_aktif']) === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                        <option value="nonaktif" <?= (($_POST['status_aktif'] ?? $dokter['status_aktif']) === 'nonaktif') ? 'selected' : '' ?>>Nonaktif</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">No. Telepon</label>
                    <input type="text" name="no_telepon" value="<?= clean($_POST['no_telepon'] ?? $dokter['no_telepon']) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" value="<?= clean($_POST['email'] ?? $dokter['email']) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
            
            <h3 class="font-medium text-slate-800 border-b border-slate-100 pb-2 mt-6 mb-4">Informasi Akun Login</h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Username <span class="text-red-500">*</span></label>
                    <input type="text" name="username" required value="<?= clean($_POST['username'] ?? ($akun['username'] ?? '')) ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                </div>
            </div>
        </div>

        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex items-center gap-3">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                Perbarui Data Dokter
            </button>
            <a href="index.php" class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2.5">Batal</a>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
