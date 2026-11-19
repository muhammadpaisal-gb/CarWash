<?php
$u = user();
$menu = function ($key, $icon, $label, $match = null) use ($open) {
    $m = $match ?? $key;
    $aktif = (strpos($open, $m) === 0) ? ' active' : '';
    return '<a class="nav-link' . $aktif . '" href="?open=' . $key . '"><i class="bi bi-' . $icon . '"></i><span>' . $label . '</span></a>';
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> - Car Wash System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="src/style.css" rel="stylesheet">
</head>
<body>
<aside class="sidebar" id="sidebar">
  <div class="brand"><i class="bi bi-droplet-half"></i> CarWash<span>.sys</span></div>
  <nav class="nav flex-column">
    <?= $menu('dashboard', 'speedometer2', 'Dashboard') ?>
    <div class="nav-group">Data Master</div>
    <?= $menu('pelanggan', 'people', 'Data Pelanggan') ?>
    <?= $menu('kendaraan', 'car-front', 'Data Kendaraan') ?>
    <?= $menu('layanan', 'stars', 'Data Layanan') ?>
    <div class="nav-group">Operasional</div>
    <?= $menu('transaksi', 'receipt', 'Data Transaksi') ?>
    <?= $menu('proses', 'water', 'Proses Pencucian') ?>
    <?= $menu('pembayaran', 'cash-coin', 'Data Pembayaran') ?>
    <?php if (is_admin()): ?>
    <div class="nav-group">Laporan</div>
    <?= $menu('laporan_transaksi', 'file-earmark-text', 'Laporan Transaksi') ?>
    <?= $menu('laporan_pendapatan', 'graph-up-arrow', 'Laporan Pendapatan') ?>
    <?= $menu('riwayat', 'clock-history', 'Riwayat Pencucian') ?>
    <div class="nav-group">Pengaturan</div>
    <?= $menu('user', 'person-gear', 'Pengguna') ?>
    <?php endif; ?>
    <a class="nav-link mt-3 text-danger-emphasis" href="logout.php" onclick="return confirm('Keluar dari sistem?')"><i class="bi bi-box-arrow-left"></i><span>Logout</span></a>
  </nav>
</aside>

<div class="main">
  <header class="topbar">
    <button class="btn btn-light d-lg-none" id="toggleSidebar"><i class="bi bi-list"></i></button>
    <h5 class="mb-0 fw-semibold"><?= e($judul) ?></h5>
    <div class="ms-auto d-flex align-items-center gap-3">
      <span class="text-muted small d-none d-md-inline"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></span>
      <div class="user-chip"><i class="bi bi-person-circle fs-5"></i>
        <div class="lh-1"><div class="fw-semibold small"><?= e($u['nama']) ?></div><small class="text-muted"><?= e(ucfirst($u['role'])) ?></small></div>
      </div>
    </div>
  </header>
  <main class="content">
    <?php foreach (flash_get() as $f): ?>
      <div class="alert alert-<?= e($f['t']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['m']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endforeach; ?>
    <?= $content ?>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="src/app.js"></script>
</body>
</html>
