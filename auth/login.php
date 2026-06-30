<?php
/**
 * auth/login.php
 * -----------------------------------------------------------
 * Halaman form login untuk Admin & Dokter (1 pintu masuk).
 * Setelah submit, form ini dikirim ke proses_login.php
 * -----------------------------------------------------------
 */
session_start();

// Jika user sudah login, langsung arahkan ke dashboard sesuai role (tidak perlu login lagi)
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/dashboard.php");
        exit;
    } elseif ($_SESSION['role'] === 'dokter') {
        header("Location: ../dokter/dashboard.php");
        exit;
    }
}

// Menentukan pesan error berdasarkan parameter URL (?error=...)
$pesan_error = '';
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'belum_login':
            $pesan_error = 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.';
            break;
        case 'akses_ditolak':
            $pesan_error = 'Anda tidak memiliki akses ke halaman tersebut.';
            break;
        case 'salah':
            $pesan_error = 'Username atau password yang Anda masukkan salah.';
            break;
        case 'nonaktif':
            $pesan_error = 'Akun Anda sudah tidak aktif. Hubungi administrator.';
            break;
        default:
            $pesan_error = '';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Klinik Sistem Informasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-emerald-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md">

        <!-- Logo & Judul -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl mb-4 shadow-lg">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7h-3a2 2 0 01-2-2V2M9 2v3a2 2 0 01-2 2H4m0 0v14a2 2 0 002 2h12a2 2 0 002-2V7" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9h6v6H9V9z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-800">Klinik Sistem Informasi</h1>
        </div>

        <!-- Card Form Login -->
        <div class="bg-white rounded-2xl shadow-xl border border-slate-100 p-8">
            <h2 class="text-xl font-semibold text-slate-800 mb-1">Selamat Datang</h2>
            <p class="text-slate-500 text-sm mb-6">Masuk untuk mengakses sistem</p>

            <?php if (!empty($pesan_error)): ?>
                <div class="bg-red-50 border-l-4 border-red-400 text-red-700 p-3 rounded mb-5 text-sm">
                    <?= htmlspecialchars($pesan_error) ?>
                </div>
            <?php endif; ?>

            <form action="proses_login.php" method="POST" class="space-y-4">

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700 mb-1.5">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            required
                            autofocus
                            placeholder="Masukkan username"
                            class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3V6a3 3 0 10-6 0v2c0 1.657 1.343 3 3 3zm6 0v6a2 2 0 01-2 2H8a2 2 0 01-2-2v-6h12z" />
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            placeholder="Masukkan password"
                            class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                        >
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-lg transition mt-6">
                    Login
                </button>

            </form>
        </div>
        
        <div class="text-center mt-8 text-sm text-slate-400">
            &copy; <?= date('Y') ?> Klinik Sistem Informasi. All rights reserved.
        </div>

    </div>

</body>
</html>