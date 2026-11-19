<?php
$id = (int)($_GET['id'] ?? 0);
$judul = $id ? 'Edit Pengguna' : 'Tambah Pengguna';
$d = ['nama' => '', 'username' => '', 'role' => 'petugas'];
if ($id) {
    $row = db_one('SELECT * FROM users WHERE id_user=?', 'i', [$id]);
    if (!$row) { flash_set('danger', 'Pengguna tidak ditemukan.'); redirect('?open=user'); }
    $d = $row;
}
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $d['nama'] = post('nama'); $d['username'] = post('username'); $d['role'] = post('role');
    $pw = (string)($_POST['password'] ?? ''); $pw2 = (string)($_POST['password2'] ?? '');
    if ($d['nama'] === '' || mb_strlen($d['nama']) > 100) $err[] = 'Nama wajib diisi (maks. 100 karakter).';
    if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $d['username'])) $err[] = 'Username 3-30 karakter: huruf, angka, titik, atau garis bawah.';
    if (!in_array($d['role'], ['admin', 'petugas'], true)) $err[] = 'Role tidak valid.';
    if ($id === user()['id'] && $d['role'] !== 'admin') $err[] = 'Anda tidak dapat menurunkan role akun Anda sendiri.';
    if (!$id || $pw !== '') {
        if (strlen($pw) < 6) $err[] = 'Password minimal 6 karakter.';
        if ($pw !== $pw2) $err[] = 'Konfirmasi password tidak sama.';
    }
    if (!$err && db_one('SELECT 1 FROM users WHERE username=? AND id_user<>?', 'si', [$d['username'], $id])) $err[] = 'Username sudah digunakan.';
    if (!$err) {
        if ($id) {
            if ($pw !== '') db_exec('UPDATE users SET nama=?, username=?, role=?, password_hash=? WHERE id_user=?', 'ssssi', [$d['nama'], $d['username'], $d['role'], password_hash($pw, PASSWORD_DEFAULT), $id]);
            else db_exec('UPDATE users SET nama=?, username=?, role=? WHERE id_user=?', 'sssi', [$d['nama'], $d['username'], $d['role'], $id]);
            if ($id === user()['id']) { $_SESSION['user']['nama'] = $d['nama']; $_SESSION['user']['username'] = $d['username']; }
            flash_set('success', 'Pengguna diperbarui.');
        } else {
            db_exec('INSERT INTO users (nama, username, password_hash, role) VALUES (?,?,?,?)', 'ssss', [$d['nama'], $d['username'], password_hash($pw, PASSWORD_DEFAULT), $d['role']]);
            flash_set('success', 'Pengguna ditambahkan.');
        }
        redirect('?open=user');
    }
}
?>
<div class="card" style="max-width:600px"><div class="card-header"><?= e($judul) ?></div><div class="card-body">
  <?php if ($err): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($err as $x) echo '<li>' . e($x) . '</li>'; ?></ul></div><?php endif; ?>
  <form method="post" data-once autocomplete="off" class="row g-3"><?= csrf_field() ?>
    <div class="col-12"><label class="form-label">Nama Lengkap</label><input name="nama" class="form-control" value="<?= e($d['nama']) ?>" required></div>
    <div class="col-md-6"><label class="form-label">Username</label><input name="username" class="form-control" value="<?= e($d['username']) ?>" required></div>
    <div class="col-md-6"><label class="form-label">Role</label><select name="role" class="form-select"><option value="petugas"<?= $d['role'] === 'petugas' ? ' selected' : '' ?>>Petugas</option><option value="admin"<?= $d['role'] === 'admin' ? ' selected' : '' ?>>Admin</option></select></div>
    <div class="col-md-6"><label class="form-label">Password <?= $id ? '<small class="text-muted fw-normal">(kosongkan jika tidak diubah)</small>' : '' ?></label><input type="password" name="password" class="form-control" autocomplete="new-password" <?= $id ? '' : 'required' ?>></div>
    <div class="col-md-6"><label class="form-label">Konfirmasi Password</label><input type="password" name="password2" class="form-control" autocomplete="new-password" <?= $id ? '' : 'required' ?>></div>
    <div class="col-12"><button type="submit" class="btn btn-brand"><i class="bi bi-save"></i> Simpan</button> <a href="?open=user" class="btn btn-light">Batal</a></div>
  </form></div></div>
