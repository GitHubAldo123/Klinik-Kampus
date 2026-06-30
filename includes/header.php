<?php
/**
 * includes/header.php
 * -----------------------------------------------------------
 * Kerangka tampilan bagian ATAS: sidebar + topbar.
 * WAJIB di-include SETELAH cekLogin() dipanggil di setiap halaman,
 * karena header ini butuh data $_SESSION['role'] dan $_SESSION['nama'].
 *
 * Cara pakai di halaman ADMIN, contoh admin/dashboard.php:
 *   require_once __DIR__ . '/../includes/auth_check.php';
 *   cekLogin('admin');
 *   require_once __DIR__ . '/../includes/functions.php';
 *   $halaman_aktif = 'dashboard';   // dipakai untuk highlight menu sidebar
 *   require_once __DIR__ . '/../includes/header.php';
 *
 * Variabel yang BISA di-set sebelum include file ini (opsional):
 *   $halaman_aktif  -> string, untuk menandai menu mana yang sedang aktif
 *   $judul_halaman  -> string, judul yang tampil di tab browser & topbar
 * -----------------------------------------------------------
 */

$role        = $_SESSION['role']  ?? '';
$nama_user   = $_SESSION['nama']  ?? 'Pengguna';
$halaman_aktif = $halaman_aktif   ?? '';
$judul_halaman = $judul_halaman   ?? 'Klinik Sistem Informasi';

/**
 * Fungsi kecil untuk menentukan class menu aktif vs tidak aktif
 */
function kelasMenu($nama_halaman, $halaman_aktif_saat_ini) {
    if ($nama_halaman === $halaman_aktif_saat_ini) {
        return 'bg-blue-50 text-blue-700 font-medium';
    }
    return 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($judul_halaman) ?> - Klinik Sistem Informasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/custom.css">
</head>
<body class="bg-slate-50 min-h-screen" x-data="{ sidebarOpen: false }">

<div class="flex h-screen overflow-hidden">

    <!-- ===================== SIDEBAR ===================== -->
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-white border-r border-slate-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 flex flex-col">

        <!-- Logo -->
        <div class="h-16 flex items-center gap-3 px-5 border-b border-slate-200 flex-shrink-0">
            <div class="w-9 h-9 bg-blue-600 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7h-3a2 2 0 01-2-2V2M9 2v3a2 2 0 01-2 2H4m0 0v14a2 2 0 002 2h12a2 2 0 002-2V7" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h6v6H9V9z" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-slate-800 truncate">Klinik Sistem Informasi</p>
            </div>
        </div>

        <!-- Menu Navigasi -->
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">

            <?php if ($role === 'admin'): ?>
                <!-- ============ MENU KHUSUS ADMIN ============ -->
                <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Menu Admin</p>

                <a href="<?= BASE_URL ?>/admin/dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= kelasMenu('dashboard', $halaman_aktif) ?>">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    Dashboard
                </a>
                <a href="<?= BASE_URL ?>/admin/data_pasien/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= kelasMenu('data_pasien', $halaman_aktif) ?>">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" /></svg>
                    Data Pasien
                </a>
                <a href="<?= BASE_URL ?>/admin/data_dokter/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= kelasMenu('data_dokter', $halaman_aktif) ?>">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7h-3a2 2 0 01-2-2V2M9 2v3a2 2 0 01-2 2H4m0 0v14a2 2 0 002 2h12a2 2 0 002-2V7" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h6v6H9V9z" /></svg>
                    Data Dokter
                </a>
                <a href="<?= BASE_URL ?>/admin/pendaftaran/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= kelasMenu('pendaftaran', $halaman_aktif) ?>">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                    Pendaftaran Antrean
                </a>
                <a href="<?= BASE_URL ?>/admin/jadwal_dokter/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= kelasMenu('jadwal_dokter', $halaman_aktif) ?>">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    Jadwal Dokter
                </a>
                <a href="<?= BASE_URL ?>/admin/data_obat/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= kelasMenu('data_obat', $halaman_aktif) ?>">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                    Data Obat
                </a>
                <a href="<?= BASE_URL ?>/admin/laporan/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= kelasMenu('laporan', $halaman_aktif) ?>">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                    Laporan
                </a>

            <?php elseif ($role === 'dokter'): ?>
                <!-- ============ MENU KHUSUS DOKTER ============ -->
                <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Menu Dokter</p>

                <a href="<?= BASE_URL ?>/dokter/dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= kelasMenu('dashboard', $halaman_aktif) ?>">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                    Dashboard / Antrian
                </a>
                <a href="<?= BASE_URL ?>/dokter/rekam_medis/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= kelasMenu('rekam_medis', $halaman_aktif) ?>">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Rekam Medis
                </a>
                <a href="<?= BASE_URL ?>/dokter/jadwal_praktek/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= kelasMenu('jadwal_praktek', $halaman_aktif) ?>">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    Jadwal Praktek
                </a>
                <a href="<?= BASE_URL ?>/dokter/resep/index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= kelasMenu('resep', $halaman_aktif) ?>">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5C21.69 17.69 21 19 19.172 19H4.828C3 19 2.31 17.69 3.414 16.586l5-5A2 2 0 009 10.172V5L8 4z" /></svg>
                    Resep Obat
                </a>

            <?php endif; ?>
        </nav>

        <!-- Info User & Logout -->
        <div class="border-t border-slate-200 p-3 flex-shrink-0">
            <div class="flex items-center gap-3 px-2 py-2">
                <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold text-sm flex-shrink-0">
                    <?= htmlspecialchars(strtoupper(substr($nama_user, 0, 1))) ?>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-800 truncate"><?= htmlspecialchars($nama_user) ?></p>
                    <p class="text-xs text-slate-400 capitalize"><?= htmlspecialchars($role) ?></p>
                </div>
            </div>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="mt-1 flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-red-600 hover:bg-red-50 transition">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                Logout
            </a>
        </div>
    </aside>

    <!-- Overlay untuk mobile saat sidebar terbuka -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/30 z-30 hidden lg:hidden"></div>

    <!-- ===================== KONTEN UTAMA ===================== -->
    <div class="flex-1 flex flex-col overflow-hidden">

        <!-- Topbar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-6 flex-shrink-0">
            <div class="flex items-center gap-3">
                <!-- Tombol hamburger (mobile) -->
                <button id="btn-toggle-sidebar" class="lg:hidden p-2 rounded-lg hover:bg-slate-100 text-slate-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>
                <h1 class="text-lg font-semibold text-slate-800"><?= htmlspecialchars($judul_halaman) ?></h1>
            </div>

            <div class="flex items-center gap-2 text-sm text-slate-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                <span><?= date('d') ?> <?= ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'][(int)date('m')] ?> <?= date('Y') ?></span>
            </div>
        </header>

        <!-- Area Konten (akan diisi oleh setiap halaman) -->
        <main class="flex-1 overflow-y-auto p-4 lg:p-6">