<?php
/**
 * dokter/rekam_medis/input.php
 * -----------------------------------------------------------
 * Form input / edit rekam medis oleh dokter untuk pasien tertentu.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('dokter');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'rekam_medis';
$judul_halaman = 'Input Rekam Medis';

$id_pendaftaran = $_GET['id_pendaftaran'] ?? null;
$id_dokter = $_SESSION['id_ref'];
$error = [];

if (!$id_pendaftaran) {
    redirectDenganPesan('index.php', 'ID Pendaftaran tidak ditemukan.', 'error');
}

// Cek data pendaftaran & validasi akses dokter
$stmt_pend = $koneksi->prepare("
    SELECT p.*, ps.nama_lengkap, ps.jenis_kelamin, ps.tanggal_lahir, ps.nim_nik 
    FROM pendaftaran p
    JOIN pasien ps ON p.id_pasien = ps.id_pasien
    WHERE p.id_pendaftaran = ? AND p.id_dokter = ?
");
$stmt_pend->execute([$id_pendaftaran, $id_dokter]);
$pendaftaran = $stmt_pend->fetch();

if (!$pendaftaran) {
    redirectDenganPesan('index.php', 'Akses ditolak atau pendaftaran tidak valid.', 'error');
}

// Cek apakah sudah ada rekam medis sebelumnya (jika ya, mode edit)
$stmt_rm = $koneksi->prepare("SELECT * FROM rekam_medis WHERE id_pendaftaran = ?");
$stmt_rm->execute([$id_pendaftaran]);
$rekam_medis = $stmt_rm->fetch();

// Update status menjadi 'diperiksa' saat form dibuka pertama kali (jika masih 'menunggu')
if ($pendaftaran['status_kunjungan'] === 'menunggu') {
    $stmt_up_status = $koneksi->prepare("UPDATE pendaftaran SET status_kunjungan = 'diperiksa' WHERE id_pendaftaran = ?");
    $stmt_up_status->execute([$id_pendaftaran]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $anamnesis         = trim($_POST['anamnesis'] ?? '');
    $pemeriksaan_fisik = trim($_POST['pemeriksaan_fisik'] ?? '');
    $diagnosis         = trim($_POST['diagnosis'] ?? '');
    $kode_icd10        = trim($_POST['kode_icd10'] ?? '');
    $tindakan          = trim($_POST['tindakan'] ?? '');
    $catatan_dokter    = trim($_POST['catatan_dokter'] ?? '');
    
    // Status aksi: Simpan Sementara (diperiksa) atau Selesai (selesai)
    $aksi = $_POST['aksi'] ?? 'simpan';
    $status_baru = ($aksi === 'selesai') ? 'selesai' : 'diperiksa';

    if (empty($diagnosis) && $aksi === 'selesai') {
        $error[] = 'Diagnosis wajib diisi jika ingin menyelesaikan pemeriksaan.';
    }

    if (empty($error)) {
        try {
            $koneksi->beginTransaction();
            
            if ($rekam_medis) {
                // Update
                $stmt = $koneksi->prepare("
                    UPDATE rekam_medis SET 
                        anamnesis = ?, pemeriksaan_fisik = ?, diagnosis = ?, 
                        kode_icd10 = ?, tindakan = ?, catatan_dokter = ?
                    WHERE id_rekam_medis = ?
                ");
                $stmt->execute([$anamnesis, $pemeriksaan_fisik, $diagnosis, $kode_icd10, $tindakan, $catatan_dokter, $rekam_medis['id_rekam_medis']]);
                $id_rekam_medis_baru = $rekam_medis['id_rekam_medis'];
            } else {
                // Insert
                $stmt = $koneksi->prepare("
                    INSERT INTO rekam_medis (id_pendaftaran, id_dokter, anamnesis, pemeriksaan_fisik, diagnosis, kode_icd10, tindakan, catatan_dokter)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$id_pendaftaran, $id_dokter, $anamnesis, $pemeriksaan_fisik, $diagnosis, $kode_icd10, $tindakan, $catatan_dokter]);
                $id_rekam_medis_baru = $koneksi->lastInsertId();
            }

            // Update status pendaftaran
            $stmt_stat = $koneksi->prepare("UPDATE pendaftaran SET status_kunjungan = ? WHERE id_pendaftaran = ?");
            $stmt_stat->execute([$status_baru, $id_pendaftaran]);

            $koneksi->commit();

            if ($aksi === 'selesai') {
                redirectDenganPesan('detail.php?id_pendaftaran=' . $id_pendaftaran, 'Pemeriksaan selesai. Data rekam medis berhasil disimpan.', 'success');
            } else {
                redirectDenganPesan("input.php?id_pendaftaran=$id_pendaftaran", 'Data rekam medis berhasil disimpan sementara.', 'success');
            }

        } catch (PDOException $e) {
            $koneksi->rollBack();
            $error[] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="flex flex-col lg:flex-row gap-6">
    
    <!-- Bagian Kiri: Info Pasien & Pendaftaran -->
    <div class="lg:w-1/3">
        <a href="index.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-4">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            Kembali ke Antrean
        </a>

        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden sticky top-6">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <h3 class="font-semibold text-slate-800">Informasi Pasien</h3>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-blue-700 font-bold text-sm">
                    #<?= clean($pendaftaran['no_antrian']) ?>
                </span>
            </div>
            <div class="p-5 space-y-4">
                <div>
                    <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">Nama Lengkap</p>
                    <p class="text-sm font-semibold text-slate-800"><?= clean($pendaftaran['nama_lengkap']) ?></p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">NIM / NIK</p>
                        <p class="text-sm text-slate-700"><?= clean($pendaftaran['nim_nik']) ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">Jenis Kelamin</p>
                        <p class="text-sm text-slate-700"><?= clean($pendaftaran['jenis_kelamin']) ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">Umur</p>
                        <p class="text-sm text-slate-700"><?= hitungUmur($pendaftaran['tanggal_lahir']) ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1">Waktu Daftar</p>
                        <p class="text-sm text-slate-700"><?= date('H:i', strtotime($pendaftaran['waktu_daftar'])) ?></p>
                    </div>
                </div>
                
                <div class="pt-4 border-t border-slate-100">
                    <p class="text-xs text-slate-400 font-medium uppercase tracking-wider mb-1.5">Keluhan Utama (Pendaftaran)</p>
                    <div class="bg-amber-50 text-amber-800 text-sm p-3 rounded-lg border border-amber-100">
                        <?= nl2br(clean($pendaftaran['keluhan_utama'] ?: 'Tidak ada keluhan dicatat saat mendaftar.')) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bagian Kanan: Form Rekam Medis -->
    <div class="lg:w-2/3">
        <?php if (!empty($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-4 rounded mb-5">
                <ul class="list-disc list-inside text-sm space-y-1">
                    <?php foreach ($error as $pesan): ?>
                        <li><?= clean($pesan) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php tampilkanFlashMessage(); ?>

        <form method="POST" class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm">
            
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-white">
                <h2 class="text-lg font-bold text-slate-800">Lembar Rekam Medis</h2>
                <div class="flex gap-2">
                    <button type="submit" name="aksi" value="simpan" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                        Simpan Draf
                    </button>
                    <button type="submit" name="aksi" value="selesai" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm flex items-center gap-1.5" onclick="return confirm('Selesaikan pemeriksaan? Anda akan diarahkan ke halaman detail.');">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        Selesai Periksa
                    </button>
                </div>
            </div>

            <div class="p-6 space-y-6">
                <!-- S - Anamnesis -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Subjective (Anamnesis)</label>
                    <p class="text-xs text-slate-500 mb-2">Keluhan utama, riwayat penyakit, keluhan tambahan yang disampaikan pasien.</p>
                    <textarea name="anamnesis" rows="3" required
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"><?= clean($_POST['anamnesis'] ?? ($rekam_medis['anamnesis'] ?? $pendaftaran['keluhan_utama'])) ?></textarea>
                </div>

                <!-- O - Pemeriksaan Fisik -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Objective (Pemeriksaan Fisik / Penunjang)</label>
                    <p class="text-xs text-slate-500 mb-2">Tanda vital (TD, Nadi, Suhu, RR) dan hasil pemeriksaan fisik lainnya.</p>
                    <textarea name="pemeriksaan_fisik" rows="3" required
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"><?= clean($_POST['pemeriksaan_fisik'] ?? ($rekam_medis['pemeriksaan_fisik'] ?? '')) ?></textarea>
                </div>

                <!-- A - Assessment -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <div class="md:col-span-3">
                        <label class="block text-sm font-bold text-slate-800 mb-1">Assessment (Diagnosis) <span class="text-red-500">*</span></label>
                        <p class="text-xs text-slate-500 mb-2">Diagnosis medis pasien.</p>
                        <input type="text" name="diagnosis" value="<?= clean($_POST['diagnosis'] ?? ($rekam_medis['diagnosis'] ?? '')) ?>"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-800 mb-1">Kode ICD-10</label>
                        <p class="text-xs text-slate-500 mb-2">Opsional</p>
                        <input type="text" name="kode_icd10" value="<?= clean($_POST['kode_icd10'] ?? ($rekam_medis['kode_icd10'] ?? '')) ?>"
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none bg-white">
                    </div>
                </div>

                <!-- P - Plan -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Plan (Tindakan / Terapi)</label>
                    <p class="text-xs text-slate-500 mb-2">Rencana pengobatan, tindakan yang dilakukan di klinik, atau rujukan.</p>
                    <textarea name="tindakan" rows="3"
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"><?= clean($_POST['tindakan'] ?? ($rekam_medis['tindakan'] ?? '')) ?></textarea>
                </div>

                <!-- Catatan Tambahan -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Catatan Tambahan Khusus Dokter</label>
                    <p class="text-xs text-slate-500 mb-2">Saran edukasi, anjuran istirahat, dll.</p>
                    <textarea name="catatan_dokter" rows="2"
                              class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"><?= clean($_POST['catatan_dokter'] ?? ($rekam_medis['catatan_dokter'] ?? '')) ?></textarea>
                </div>
            </div>

        </form>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
