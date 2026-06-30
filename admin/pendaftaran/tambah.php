<?php
/**
 * admin/pendaftaran/tambah.php
 * -----------------------------------------------------------
 * Form mendaftarkan pasien ke jadwal praktik dokter.
 * Mendukung pemilihan tanggal reservasi.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/../../includes/auth_check.php';
cekLogin('admin');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$halaman_aktif = 'pendaftaran';
$judul_halaman = 'Pendaftaran Pasien Baru';
$error = [];

// Ambil data pasien (opsi dropdown)
$stmt_pasien = $koneksi->query("SELECT id_pasien, nama_lengkap, nim_nik FROM pasien ORDER BY nama_lengkap ASC");
$daftar_pasien = $stmt_pasien->fetchAll();

// Ambil semua jadwal dokter yang aktif
$stmt_jadwal = $koneksi->query("
    SELECT j.id_jadwal, j.hari, j.jam_mulai, j.jam_selesai, j.kuota_pasien, d.id_dokter, d.nama_dokter, d.spesialisasi
    FROM jadwal_dokter j
    JOIN dokter d ON j.id_dokter = d.id_dokter
    WHERE j.status = 'aktif'
    ORDER BY j.hari ASC, j.jam_mulai ASC
");
$semua_jadwal = $stmt_jadwal->fetchAll();

// Siapkan data jadwal dalam bentuk array untuk digunakan di JavaScript
$jadwal_json = json_encode($semua_jadwal);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pasien         = intval($_POST['id_pasien'] ?? 0);
    $tanggal_kunjungan = trim($_POST['tanggal_kunjungan'] ?? '');
    $id_jadwal_str     = $_POST['id_jadwal'] ?? ''; // Format: id_jadwal|id_dokter
    $keluhan_utama     = trim($_POST['keluhan_utama'] ?? '');

    if ($id_pasien <= 0) $error[] = 'Pasien wajib dipilih.';
    if (empty($tanggal_kunjungan)) $error[] = 'Tanggal kunjungan wajib dipilih.';
    if (empty($id_jadwal_str)) $error[] = 'Jadwal dokter wajib dipilih.';

    // Pecah id_jadwal_str
    $parts = explode('|', $id_jadwal_str);
    $id_jadwal = intval($parts[0] ?? 0);
    $id_dokter = intval($parts[1] ?? 0);

    // Pastikan jadwal yang dipilih sesuai dengan hari dari tanggal kunjungan
    if (empty($error)) {
        $hari_kunjungan_inggris = date('l', strtotime($tanggal_kunjungan));
        $map_hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $hari_kunjungan_indo = $map_hari[$hari_kunjungan_inggris];
        
        $stmt_cek_hari = $koneksi->prepare("SELECT hari FROM jadwal_dokter WHERE id_jadwal = ?");
        $stmt_cek_hari->execute([$id_jadwal]);
        $jadwal_terpilih = $stmt_cek_hari->fetch();
        
        if (!$jadwal_terpilih || $jadwal_terpilih['hari'] !== $hari_kunjungan_indo) {
            $error[] = "Jadwal dokter yang dipilih tidak tersedia pada hari $hari_kunjungan_indo.";
        }
    }

    // Cek apakah pasien sudah mendaftar di jadwal ini pada tanggal kunjungan tersebut
    if (empty($error)) {
        $stmt_cek = $koneksi->prepare("
            SELECT id_pendaftaran FROM pendaftaran 
            WHERE id_pasien = ? AND id_jadwal = ? AND tanggal_kunjungan = ?
        ");
        $stmt_cek->execute([$id_pasien, $id_jadwal, $tanggal_kunjungan]);
        if ($stmt_cek->fetch()) {
            $error[] = 'Pasien sudah terdaftar pada jadwal ini untuk tanggal kunjungan tersebut.';
        }
    }

    // Cek kuota
    if (empty($error)) {
        // Hitung pasien yang sudah daftar di jadwal ini pada tanggal kunjungan tersebut
        $stmt_kuota = $koneksi->prepare("SELECT COUNT(*) as total FROM pendaftaran WHERE id_jadwal = ? AND tanggal_kunjungan = ?");
        $stmt_kuota->execute([$id_jadwal, $tanggal_kunjungan]);
        $row_kuota    = $stmt_kuota->fetch(PDO::FETCH_ASSOC);
        $sudah_daftar = $row_kuota ? (int)$row_kuota['total'] : 0;

        // Ambil kuota max
        $stmt_max = $koneksi->prepare("SELECT kuota_pasien FROM jadwal_dokter WHERE id_jadwal = ?");
        $stmt_max->execute([$id_jadwal]);
        $row_max   = $stmt_max->fetch(PDO::FETCH_ASSOC);
        $kuota_max = $row_max ? (int)$row_max['kuota_pasien'] : 0;

        if ($kuota_max > 0 && $sudah_daftar >= $kuota_max) {
            $error[] = 'Kuota pasien untuk jadwal ini sudah penuh (' . $sudah_daftar . '/' . $kuota_max . ') pada tanggal tersebut.';
        }
    }

    if (empty($error)) {
        try {
            // Kita butuh fungsi nomorAntrianBerikutnya yang mendukung parameter tanggal
            // Karena fungsi aslinya pakai CURDATE(), kita hitung manual di sini saja
            $stmt_no = $koneksi->prepare("
                SELECT MAX(no_antrian) AS max_antrian
                FROM pendaftaran
                WHERE id_jadwal = ? AND tanggal_kunjungan = ?
            ");
            $stmt_no->execute([$id_jadwal, $tanggal_kunjungan]);
            $hasil_no = $stmt_no->fetch();
            $no_antrian = ($hasil_no['max_antrian'] ?? 0) + 1;
            
            $stmt = $koneksi->prepare("
                INSERT INTO pendaftaran (id_pasien, id_jadwal, id_dokter, tanggal_kunjungan, no_antrian, keluhan_utama, status_kunjungan)
                VALUES (?, ?, ?, ?, ?, ?, 'menunggu')
            ");
            $stmt->execute([$id_pasien, $id_jadwal, $id_dokter, $tanggal_kunjungan, $no_antrian, $keluhan_utama]);

            redirectDenganPesan('index.php', 'Berhasil! Pasien terdaftar untuk tanggal ' . formatTanggal($tanggal_kunjungan) . ' dengan No. Antrean: ' . $no_antrian, 'success');
        } catch (PDOException $e) {
            $error[] = 'Gagal menyimpan data: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl">
    <a href="index.php" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 mb-4">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Kembali ke Pendaftaran
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
        <div class="p-6 space-y-5">
            
            <!-- Pilih Pasien -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Pilih Pasien <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <select name="id_pasien" required class="flex-1 px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">-- Cari dan Pilih Pasien --</option>
                        <?php foreach ($daftar_pasien as $pasien): ?>
                            <?php $selected = (($_POST['id_pasien'] ?? '') == $pasien['id_pasien']) ? 'selected' : ''; ?>
                            <option value="<?= $pasien['id_pasien'] ?>" <?= $selected ?>>
                                <?= clean($pasien['nim_nik']) ?> - <?= clean($pasien['nama_lengkap']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="../data_pasien/tambah.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg transition" title="Tambah Pasien Baru">+ Baru</a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Tanggal Kunjungan -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Tanggal Kunjungan <span class="text-red-500">*</span></label>
                    <input type="date" id="tanggal_kunjungan" name="tanggal_kunjungan" required 
                           value="<?= clean($_POST['tanggal_kunjungan'] ?? date('Y-m-d')) ?>" 
                           min="<?= date('Y-m-d') ?>"
                           class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                    <p class="text-xs text-slate-500 mt-1" id="info_hari">Pilih tanggal untuk melihat jadwal.</p>
                </div>

                <!-- Pilih Jadwal -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Pilih Jadwal Dokter <span class="text-red-500">*</span></label>
                    <select id="id_jadwal" name="id_jadwal" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        <option value="">-- Pilih Tanggal Terlebih Dahulu --</option>
                    </select>
                </div>
            </div>

            <!-- Keluhan Utama -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Keluhan Utama (Opsional)</label>
                <textarea name="keluhan_utama" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"><?= clean($_POST['keluhan_utama'] ?? '') ?></textarea>
                <p class="text-xs text-slate-500 mt-1">Keluhan singkat yang disampaikan pasien saat mendaftar.</p>
            </div>

        </div>

        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex items-center gap-3">
            <button type="submit" id="btn_submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition">
                Daftarkan Pasien
            </button>
            <a href="index.php" class="text-sm text-slate-500 hover:text-slate-700 px-3 py-2.5">Batal</a>
        </div>
    </form>
</div>

<!-- Script untuk filter dropdown jadwal berdasarkan hari dari tanggal yang dipilih -->
<script>
    const semuaJadwal = <?= $jadwal_json ?>;
    const dateInput = document.getElementById('tanggal_kunjungan');
    const selectJadwal = document.getElementById('id_jadwal');
    const infoHari = document.getElementById('info_hari');
    const btnSubmit = document.getElementById('btn_submit');
    
    // Mapping nama hari JS (0=Minggu, 1=Senin, ...)
    const namaHari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    // Simpan jadwal yang dipilih sebelumnya jika form submit gagal (dari POST)
    const selectedJadwalBefore = '<?= clean($_POST['id_jadwal'] ?? '') ?>';

    function updateJadwal() {
        selectJadwal.innerHTML = '';
        const tanggalStr = dateInput.value;
        
        if (!tanggalStr) {
            selectJadwal.innerHTML = '<option value="">-- Pilih Tanggal Terlebih Dahulu --</option>';
            infoHari.textContent = 'Pilih tanggal untuk melihat jadwal.';
            btnSubmit.disabled = true;
            return;
        }

        // Tentukan hari dari tanggal
        // PENTING: Gunakan parse manual agar tidak terkena timezone offset.
        // new Date('YYYY-MM-DD') diparse sebagai UTC sehingga getDay() bisa meleset 1 hari.
        const [thn, bln, hari_tgl] = tanggalStr.split('-').map(Number);
        const dateObj = new Date(thn, bln - 1, hari_tgl); // local timezone
        const hariIndo = namaHari[dateObj.getDay()];
        infoHari.textContent = `Hari Terpilih: ${hariIndo}`;

        // Filter jadwal berdasarkan hari
        const jadwalHariIni = semuaJadwal.filter(j => j.hari === hariIndo);

        if (jadwalHariIni.length === 0) {
            selectJadwal.innerHTML = `<option value="">-- Tidak ada jadwal praktik hari ${hariIndo} --</option>`;
            selectJadwal.disabled = true;
            btnSubmit.disabled = true;
        } else {
            selectJadwal.innerHTML = `<option value="">-- Pilih Jadwal Praktik (${hariIndo}) --</option>`;
            selectJadwal.disabled = false;
            btnSubmit.disabled = false;
            
            jadwalHariIni.forEach(j => {
                const val = `${j.id_jadwal}|${j.id_dokter}`;
                const jamMulai = j.jam_mulai.substring(0, 5);
                const jamSelesai = j.jam_selesai.substring(0, 5);
                
                const option = document.createElement('option');
                option.value = val;
                option.textContent = `dr. ${j.nama_dokter} (${jamMulai} - ${jamSelesai})`;
                
                if (val === selectedJadwalBefore) {
                    option.selected = true;
                }
                
                selectJadwal.appendChild(option);
            });
        }
    }

    // Jalankan saat tanggal berubah
    dateInput.addEventListener('change', updateJadwal);

    // Jalankan saat halaman pertama kali diload
    updateJadwal();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
