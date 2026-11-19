<?php
require_once 'src/helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
if (!csrf_check(false)) redirect('index.php?pesan=token');

$now = time();
if (($_SESSION['lock_until'] ?? 0) > $now) redirect('index.php?pesan=terkunci');

$username = post('username');
$password = (string)($_POST['password'] ?? '');
$u = db_one('SELECT * FROM users WHERE username = ?', 's', [$username]);

if ($u && password_verify($password, $u['password_hash'])) {
    unset($_SESSION['gagal'], $_SESSION['lock_until']);
    login_user($u);
    redirect('dashboard.php');
}
$_SESSION['gagal'] = ($_SESSION['gagal'] ?? 0) + 1;
if ($_SESSION['gagal'] >= 5) { $_SESSION['lock_until'] = $now + 60; $_SESSION['gagal'] = 0; }
redirect('index.php?pesan=gagal');
