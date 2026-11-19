<?php
require_once 'src/helpers.php';
if (user()) redirect('dashboard.php');
flash_get(); // buang flash lama agar tidak muncul setelah login
$pesan = $_GET['pesan'] ?? '';
$teks = ['gagal' => ['danger', 'Username atau password tidak sesuai.'],
         'terkunci' => ['warning', 'Terlalu banyak percobaan gagal. Coba lagi dalam 1 menit.'],
         'token' => ['danger', 'Sesi formulir kedaluwarsa. Silakan coba lagi.'],
         'logout' => ['success', 'Anda berhasil logout.']];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Car Wash System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="src/style.css" rel="stylesheet">
</head>
<body class="login-body">
<div class="container min-vh-100 d-flex align-items-center justify-content-center py-4">
  <div class="login-card shadow-lg">
    <div class="login-side d-none d-md-flex">
      <div>
        <i class="bi bi-droplet-half display-3"></i>
        <h2 class="mt-3 fw-bold">Car Wash<br>Management</h2>
        <p class="opacity-75 mb-0">Kelola pelanggan, antrean cuci, pembayaran, dan laporan dalam satu tempat.</p>
      </div>
    </div>
    <div class="login-form">
      <h4 class="fw-bold mb-1">Selamat datang</h4>
      <p class="text-muted mb-4">Masuk untuk melanjutkan</p>
      <?php if (isset($teks[$pesan])): ?>
        <div class="alert alert-<?= $teks[$pesan][0] ?> py-2"><?= e($teks[$pesan][1]) ?></div>
      <?php endif; ?>
      <form method="post" action="login_validasi.php" autocomplete="off">
        <?= csrf_field(false) ?>
        <div class="mb-3">
          <label class="form-label">Username</label>
          <div class="input-group"><span class="input-group-text"><i class="bi bi-person"></i></span>
          <input name="username" class="form-control" required autofocus></div>
        </div>
        <div class="mb-4">
          <label class="form-label">Password</label>
          <div class="input-group"><span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input name="password" type="password" class="form-control" required></div>
        </div>
        <button class="btn btn-brand w-100 py-2">Masuk</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
