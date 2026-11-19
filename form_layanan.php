<?php
$id = (int)($_GET['id'] ?? 0);
$judul = $id ? 'Edit Layanan' : 'Tambah Layanan';
$d = ['nama_layanan' => '', 'deskripsi' => '', 'harga' => '', 'estimasi_menit' => 30, 'status_aktif' => 1];
if ($id) {
    $row = db_one('SELECT * FROM layanan WHERE id_layanan=?', 'i', [$id]);
    if (!$row) { flash_set('danger', 'Layanan tidak ditemukan.'); redirect('?open=layanan'); }
    $d = $row; $d['harga'] = (int)$d['harga'];
}
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $d['nama_layanan'] = post('nama_layanan'); $d['deskripsi'] = post('deskripsi');
    $d['harga'] = post('harga'); $d['estimasi_menit'] = post('estimasi_menit'); $d['status_aktif'] = isset($_POST['status_aktif']) ? 1 : 0;
    if ($d['nama_layanan'] === '' || mb_strlen($d['nama_layanan']) > 100) $err[] = 'Nama layanan wajib diisi (maks. 100 karakter).';
    if (!ctype_digit((string)$d['harga']) || (int)$d['harga'] <= 0 || (int)$d['harga'] > 99999999) $err[] = 'Harga harus berupa angka bulat lebih dari 0.';
    if (!ctype_digit((string)$d['estimasi_menit']) || (int)$d['estimasi_menit'] < 1 || (int)$d['estimasi_menit'] > 1440) $err[] = 'Estimasi waktu harus 1 - 1440 menit.';
    if (mb_strlen($d['deskripsi']) > 255) $err[] = 'Deskripsi maksimal 255 karakter.';
    if (!$err) {
        if ($id) {
            db_exec('UPDATE layanan SET nama_layanan=?, deskripsi=?, harga=?, estimasi_menit=?, status_aktif=? WHERE id_layanan=?', 'ssdiii',
                [$d['nama_layanan'], $d['deskripsi'], (int)$d['harga'], (int)$d['estimasi_menit'], $d['status_aktif'], $id]);
            flash_set('success', 'Layanan diperbarui. Transaksi lama tetap memakai harga saat transaksi.');
        } else {
            db_exec('INSERT INTO layanan (nama_layanan, deskripsi, harga, estimasi_menit, status_aktif) VALUES (?,?,?,?,?)', 'ssdii',
                [$d['nama_layanan'], $d['deskripsi'], (int)$d['harga'], (int)$d['estimasi_menit'], $d['status_aktif']]);
            flash_set('success', 'Layanan ditambahkan.');
        }
        redirect('?open=layanan');
    }
}
?>
<div class="card" style="max-width:640px"><div class="card-header"><?= e($judul) ?></div><div class="card-body">
  <?php if ($err): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($err as $x) echo '<li>' . e($x) . '</li>'; ?></ul></div><?php endif; ?>
  <form method="post" data-once class="row g-3"><?= csrf_field() ?>
    <div class="col-12"><label class="form-label">Nama Layanan</label><input name="nama_layanan" class="form-control" value="<?= e($d['nama_layanan']) ?>" required></div>
    <div class="col-12"><label class="form-label">Deskripsi</label><textarea name="deskripsi" class="form-control" rows="2"><?= e($d['deskripsi']) ?></textarea></div>
    <div class="col-md-6"><label class="form-label">Harga (Rp)</label><input name="harga" type="number" min="1" step="1" class="form-control" value="<?= e($d['harga']) ?>" required></div>
    <div class="col-md-6"><label class="form-label">Estimasi (menit)</label><input name="estimasi_menit" type="number" min="1" class="form-control" value="<?= e($d['estimasi_menit']) ?>" required></div>
    <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="status_aktif" id="sa"<?= $d['status_aktif'] ? ' checked' : '' ?>><label class="form-check-label" for="sa">Layanan aktif (tampil pada transaksi baru)</label></div></div>
    <div class="col-12"><button type="submit" class="btn btn-brand"><i class="bi bi-save"></i> Simpan</button> <a href="?open=layanan" class="btn btn-light">Batal</a></div>
  </form></div></div>
