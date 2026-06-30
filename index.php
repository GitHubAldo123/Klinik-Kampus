<?php
/**
 * index.php
 * -----------------------------------------------------------
 * Halaman utama (root). Otomatis mengarahkan pengguna ke
 * halaman login. Jika sudah login, file login.php akan 
 * otomatis mengarahkannya ke dashboard masing-masing role.
 * -----------------------------------------------------------
 */

header("Location: auth/login.php");
exit;
