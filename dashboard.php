<?php
// Halaman utama (pengganti halaman_admin.php + buka_file.php pada proyek SPP).
// Semua menu dibuka lewat dashboard.php?open=nama_menu
require_once 'src/helpers.php';
require_login();

$routes = require __DIR__ . '/src/routes.php';
$open = $_GET['open'] ?? 'dashboard';
if (!isset($routes[$open])) $open = 'dashboard';
[$file, $adminOnly] = $routes[$open];
if ($adminOnly) require_admin();

$judul = 'Dashboard';
ob_start();                              // buffer: agar redirect/ekspor bisa dilakukan sebelum HTML keluar
include __DIR__ . '/halaman/' . $file;
$content = ob_get_clean();
include __DIR__ . '/halaman/_layout.php';
