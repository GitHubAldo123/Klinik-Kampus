<?php
require 'config/database.php';
$stmt = $koneksi->query('SELECT * FROM jadwal_dokter');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
