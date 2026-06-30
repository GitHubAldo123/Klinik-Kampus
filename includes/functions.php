<?php
/**
 * includes/functions.php
 * -----------------------------------------------------------
 * Kumpulan fungsi bantu (helper) yang dipakai di banyak halaman.
 * Cara pakai di file lain:
 *   require_once __DIR__ . '/../includes/functions.php';
 * -----------------------------------------------------------
 */

/**
 * Membersihkan input dari karakter berbahaya (mencegah XSS saat ditampilkan)
 */
function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format tanggal Indonesia: 2026-06-24 -> 24 Juni 2026
 */
function formatTanggal($tanggal) {
    if (empty($tanggal)) return '-';
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $waktu = strtotime($tanggal);
    $hari  = date('d', $waktu);
    $bln   = (int) date('m', $waktu);
    $thn   = date('Y', $waktu);
    return $hari . ' ' . $bulan[$bln] . ' ' . $thn;
}

/**
 * Format tanggal + jam Indonesia: 2026-06-24 13:45:00 -> 24 Juni 2026, 13:45
 */
function formatTanggalJam($datetime) {
    if (empty($datetime)) return '-';
    $tanggal = formatTanggal($datetime);
    $jam = date('H:i', strtotime($datetime));
    return $tanggal . ', ' . $jam . ' WIB';
}

/**
 * Format jam saja: 08:00:00 -> 08:00
 */
function formatJam($waktu) {
    if (empty($waktu)) return '-';
    return date('H:i', strtotime($waktu));
}

/**
 * Format Rupiah: 15000 -> Rp 15.000
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Generate nomor antrian otomatis berikutnya untuk 1 jadwal pada hari ini
 * $koneksi = koneksi PDO, $id_jadwal = id jadwal dokter yang dipilih
 */
function nomorAntrianBerikutnya($koneksi, $id_jadwal) {
    $stmt = $koneksi->prepare("
        SELECT MAX(no_antrian) AS max_antrian
        FROM pendaftaran
        WHERE id_jadwal = ?
        AND DATE(waktu_daftar) = CURDATE()
    ");
    $stmt->execute([$id_jadwal]);
    $hasil = $stmt->fetch();
    return ($hasil['max_antrian'] ?? 0) + 1;
}

/**
 * Badge status kunjungan (untuk ditampilkan dengan warna berbeda di Tailwind)
 * Mengembalikan array [label, kelas_warna_tailwind]
 */
function badgeStatusKunjungan($status) {
    switch ($status) {
        case 'menunggu':
            return ['Menunggu', 'bg-yellow-100 text-yellow-700'];
        case 'diperiksa':
            return ['Diperiksa', 'bg-blue-100 text-blue-700'];
        case 'selesai':
            return ['Selesai', 'bg-green-100 text-green-700'];
        case 'batal':
            return ['Batal', 'bg-red-100 text-red-700'];
        default:
            return [ucfirst($status), 'bg-gray-100 text-gray-700'];
    }
}

/**
 * Badge status resep
 */
function badgeStatusResep($status) {
    switch ($status) {
        case 'belum_diambil':
            return ['Belum Diambil', 'bg-yellow-100 text-yellow-700'];
        case 'sudah_diambil':
            return ['Sudah Diambil', 'bg-green-100 text-green-700'];
        default:
            return [ucfirst($status), 'bg-gray-100 text-gray-700'];
    }
}

/**
 * Redirect dengan pesan flash (disimpan sementara di session)
 * Dipakai setelah proses tambah/edit/hapus data, sebelum redirect ke halaman list
 */
function redirectDenganPesan($url, $pesan, $tipe = 'success') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_pesan'] = $pesan;
    $_SESSION['flash_tipe']  = $tipe; // success | error
    header("Location: $url");
    exit;
}

/**
 * Menampilkan flash message (dipanggil sekali di bagian atas halaman, setelah header)
 * Otomatis hilang setelah ditampilkan sekali
 */
function tampilkanFlashMessage() {
    if (!empty($_SESSION['flash_pesan'])) {
        $tipe  = $_SESSION['flash_tipe'] ?? 'success';
        $pesan = $_SESSION['flash_pesan'];

        $warna = $tipe === 'success'
            ? 'bg-green-50 border-green-400 text-green-700'
            : 'bg-red-50 border-red-400 text-red-700';

        echo "
        <div class='border-l-4 $warna p-4 rounded mb-4' role='alert'>
            <p class='font-medium'>" . clean($pesan) . "</p>
        </div>
        ";

        unset($_SESSION['flash_pesan']);
        unset($_SESSION['flash_tipe']);
    }
}

/**
 * Hitung umur dari tanggal lahir
 */
function hitungUmur($tanggal_lahir) {
    if (empty($tanggal_lahir)) return '-';
    $lahir = new DateTime($tanggal_lahir);
    $sekarang = new DateTime('today');
    $umur = $lahir->diff($sekarang)->y;
    return $umur . ' tahun';
}

/**
 * Nama hari ini dalam Bahasa Indonesia (Senin, Selasa, dst)
 */
function namaHariIni() {
    $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
             'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
             'Saturday' => 'Sabtu'];
    return $hari[date('l')];
}