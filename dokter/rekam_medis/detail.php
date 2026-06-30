<?php
/**
 * dokter/rekam_medis/detail.php
 * -----------------------------------------------------------
 * Menampilkan detail rekam medis yang sudah selesai diinput.
 * Memberikan opsi untuk membuat resep obat.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('dokter');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'rekam_medis';
$judul_halaman = 'Detail Rekam Medis';

$id_pendaftaran = $_GET['id_pendaftaran'] ?? null;
$id_dokter = $_SESSION['id_ref'];

if (!$id_pendaftaran) {
    redirectDenganPesan('index.php', 'ID Pendaftaran tidak ditemukan.', 'error');
}

// Ambil data rekam medis + pendaftaran + pasien
$sql = "
    SELECT rm.*, p.waktu_daftar, p.no_antrian, ps.nama_lengkap, ps.nim_nik, ps.jenis_kelamin, ps.tanggal_lahir, ps.status_akademik
    FROM rekam_medis rm
    JOIN pendaftaran p ON rm.id_pendaftaran = p.id_pendaftaran
    JOIN pasien ps ON p.id_pasien = ps.id_pasien
    WHERE p.id_pendaftaran = ? AND p.id_dokter = ?
";
$stmt = $koneksi->prepare($sql);
$stmt->execute([$id_pendaftaran, $id_dokter]);
$rm = $stmt->fetch();

if (!$rm) {
    redirectDenganPesan('index.php', 'Data rekam medis tidak ditemukan atau Anda tidak memiliki akses.', 'error');
}

// Cek apakah sudah ada resep untuk rekam medis ini
$stmt_resep = $koneksi->prepare("SELECT id_resep FROM resep WHERE id_rekam_medis = ?");
$stmt_resep->execute([$rm['id_rekam_medis']]);
$resep = $stmt_resep->fetch();

require_once __DIR__ . '/../../includes/header.php';
?>

<?php tampilkanFlashMessage(); ?>

<div class="mb-5 flex items-center justify-between">
    <a href="index.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Kembali ke Antrean
    </a>
    
    <div class="flex gap-2">
        <a href="cetak.php?id_pendaftaran=<?= $id_pendaftaran ?>" target="_blank" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
            Cetak RM
        </a>
        <a href="input.php?id_pendaftaran=<?= $id_pendaftaran ?>" class="inline-flex items-center gap-1.5 bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
            Edit Rekam Medis
        </a>
        
        <?php if ($resep): ?>
            <a href="../resep/tambah.php?id_rekam_medis=<?= $rm['id_rekam_medis'] ?>" class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                Lihat Resep Obat
            </a>
        <?php else: ?>
            <a href="../resep/tambah.php?id_rekam_medis=<?= $rm['id_rekam_medis'] ?>" class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition shadow-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Buat Resep Obat
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Profil Pasien -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white rounded-xl border border-slate-200 p-6 text-center">
            <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-500 font-bold text-2xl mb-4">
                <?= substr(clean($rm['nama_lengkap']), 0, 1) ?>
            </div>
            <h2 class="text-xl font-bold text-slate-800"><?= clean($rm['nama_lengkap']) ?></h2>
            <p class="text-sm text-slate-500 mt-1"><?= clean($rm['nim_nik']) ?> • <?= clean($rm['status_akademik']) ?></p>
            
            <div class="mt-6 pt-6 border-t border-slate-100 text-left space-y-4">
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Jenis Kelamin</p>
                    <p class="text-sm font-medium text-slate-700"><?= clean($rm['jenis_kelamin']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Umur</p>
                    <p class="text-sm font-medium text-slate-700"><?= hitungUmur($rm['tanggal_lahir']) ?></p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wider mb-1">Waktu Periksa</p>
                    <p class="text-sm font-medium text-slate-700"><?= date('d M Y, H:i', strtotime($rm['waktu_periksa'])) ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Rekam Medis (SOAP) -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex items-center gap-3">
                <svg class="h-5 w-5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                <h3 class="font-bold text-slate-800">Catatan SOAP (Rekam Medis)</h3>
            </div>
            
            <div class="p-6 space-y-6">
                <!-- S -->
                <div>
                    <h4 class="text-sm font-bold text-slate-800 mb-2 flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-blue-100 text-blue-700 text-xs">S</span> 
                        Subjective (Anamnesis)
                    </h4>
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-100 text-sm text-slate-700">
                        <?= nl2br(clean($rm['anamnesis'] ?: '-')) ?>
                    </div>
                </div>

                <!-- O -->
                <div>
                    <h4 class="text-sm font-bold text-slate-800 mb-2 flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-blue-100 text-blue-700 text-xs">O</span> 
                        Objective (Pemeriksaan Fisik)
                    </h4>
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-100 text-sm text-slate-700">
                        <?= nl2br(clean($rm['pemeriksaan_fisik'] ?: '-')) ?>
                    </div>
                </div>

                <!-- A -->
                <div>
                    <h4 class="text-sm font-bold text-slate-800 mb-2 flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-blue-100 text-blue-700 text-xs">A</span> 
                        Assessment (Diagnosis)
                    </h4>
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 text-sm text-slate-800">
                        <span class="font-semibold"><?= clean($rm['diagnosis'] ?: '-') ?></span>
                        <?php if ($rm['kode_icd10']): ?>
                            <span class="ml-2 inline-block px-2 py-0.5 rounded bg-blue-200 text-blue-800 text-xs font-mono">
                                ICD-10: <?= clean($rm['kode_icd10']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- P -->
                <div>
                    <h4 class="text-sm font-bold text-slate-800 mb-2 flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded bg-blue-100 text-blue-700 text-xs">P</span> 
                        Plan (Tindakan/Terapi)
                    </h4>
                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-100 text-sm text-slate-700">
                        <?= nl2br(clean($rm['tindakan'] ?: '-')) ?>
                    </div>
                </div>
                
                <?php if ($rm['catatan_dokter']): ?>
                    <div class="pt-4 border-t border-slate-100">
                        <h4 class="text-sm font-bold text-slate-800 mb-2">Catatan Khusus Dokter</h4>
                        <div class="text-sm text-slate-600 italic">
                            "<?= nl2br(clean($rm['catatan_dokter'])) ?>"
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
