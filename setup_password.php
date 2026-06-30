<?php
require_once __DIR__ . '/config/database.php';

try {
    // Set password untuk semua admin menjadi: admin123
    $pass_admin = password_hash('admin123', PASSWORD_DEFAULT);
    $koneksi->query("UPDATE users SET password = '$pass_admin' WHERE role = 'admin'");
    
    // Set password untuk semua dokter menjadi: dokter123
    $pass_dokter = password_hash('dokter123', PASSWORD_DEFAULT);
    $koneksi->query("UPDATE users SET password = '$pass_dokter' WHERE role = 'dokter'");
    
    echo "<h1>Sukses!</h1>";
    echo "<p>Password akun default telah direset (di-hash) ke standar yang benar.</p>";
    echo "<ul>";
    echo "<li><strong>Admin:</strong> Username: admin1 | Password: admin123</li>";
    echo "<li><strong>Dokter:</strong> Username: dokter1 | Password: dokter123</li>";
    echo "</ul>";
    echo "<a href='auth/login.php'>Kembali ke Login</a>";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
