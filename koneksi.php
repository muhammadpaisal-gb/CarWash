<?php
// Koneksi database (MySQLi). Sesuaikan jika user/password MySQL Anda berbeda.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $koneksi = new mysqli('localhost', 'root', '', 'db_carwash');
    $koneksi->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    die('Koneksi database gagal: ' . htmlspecialchars($e->getMessage()) . '. Pastikan MySQL aktif dan database db_carwash sudah di-import.');
}
