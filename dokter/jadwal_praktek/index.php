<?php
/**
 * dokter/jadwal_praktek/index.php
 * -----------------------------------------------------------
 * Menampilkan jadwal praktik khusus untuk dokter yang sedang login.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('dokter');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'jadwal_praktek';
$judul_halaman = 'Jadwal Praktek Saya';

$id_dokter = $_SESSION['id_ref'];

$sql = "
    SELECT * 
    FROM jadwal_dokter 
    WHERE id_dokter = ?
    ORDER BY FIELD(hari, 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'), jam_mulai ASC
";
$stmt = $koneksi->prepare($sql);
$stmt->execute([$id_dokter]);
$daftar_jadwal = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="p-5 border-b border-slate-200">
        <h3 class="font-semibold text-slate-800">Daftar Jadwal Praktek Rutin</h3>
        <p class="text-sm text-slate-500 mt-1">Ini adalah jadwal rutin Anda. Jika ada perubahan, silakan hubungi Admin Klinik.</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-slate-500">
                <tr>
                    <th class="text-left font-medium px-5 py-3">Hari</th>
                    <th class="text-center font-medium px-5 py-3">Jam Praktik</th>
                    <th class="text-center font-medium px-5 py-3">Kuota Pasien</th>
                    <th class="text-center font-medium px-5 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php if (empty($daftar_jadwal)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-slate-400 py-8">
                            Belum ada jadwal praktik yang ditugaskan kepada Anda.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($daftar_jadwal as $jadwal): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-medium text-blue-600"><?= clean($jadwal['hari']) ?></td>
                            <td class="px-5 py-3 text-center text-slate-600 font-medium">
                                <?= date('H:i', strtotime($jadwal['jam_mulai'])) ?> - <?= date('H:i', strtotime($jadwal['jam_selesai'])) ?>
                            </td>
                            <td class="px-5 py-3 text-center text-slate-600"><?= clean($jadwal['kuota_pasien']) ?> pasien / hari</td>
                            <td class="px-5 py-3 text-center">
                                <?php if ($jadwal['status'] === 'aktif'): ?>
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">Aktif</span>
                                <?php else: ?>
                                    <span class="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6 bg-blue-50 border border-blue-100 rounded-xl p-5 flex items-start gap-4">
    <div class="mt-0.5 text-blue-600">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
    </div>
    <div>
        <h4 class="text-blue-800 font-semibold mb-1">Informasi Penjadwalan</h4>
        <p class="text-sm text-blue-700 leading-relaxed">
            Pasien hanya dapat mendaftar (mengambil antrean) pada hari dan jam sesuai jadwal praktik yang berstatus "Aktif". 
            Mohon bersiap di klinik minimal 15 menit sebelum jam praktik dimulai.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
