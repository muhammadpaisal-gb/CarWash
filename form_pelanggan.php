<?php
$id = (int)($_GET['id'] ?? 0);
$judul = $id ? 'Edit Pelanggan' : 'Tambah Pelanggan';
$d = ['nama' => '', 'no_telepon' => '', 'alamat' => ''];
if ($id) {
    $row = db_one('SELECT * FROM pelanggan WHERE id_pelanggan=?', 'i', [$id]);
    if (!$row) { flash_set('danger', 'Pelanggan tidak ditemukan.'); redirect('?open=pelanggan'); }
    $d = $row;
}
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $d['nama'] = post('nama'); $d['no_telepon'] = preg_replace('/[\s\-]/', '', post('no_telepon')); $d['alamat'] = post('alamat');
    if ($d['nama'] === '' || mb_strlen($d['nama']) > 100) $err[] = 'Nama wajib diisi (maksimal 100 karakter).';
    if (!valid_phone($d['no_telepon'])) $err[] = 'Nomor telepon tidak valid (contoh: 081234567890 atau +6281234567890).';
    if (mb_strlen($d['alamat']) > 255) $err[] = 'Alamat maksimal 255 karakter.';
    if (!$err) {
        if ($id) {
            db_exec('UPDATE pelanggan SET nama=?, no_telepon=?, alamat=? WHERE id_pelanggan=?', 'sssi', [$d['nama'], $d['no_telepon'], $d['alamat'], $id]);
            flash_set('success', 'Data pelanggan diperbarui.');
            redirect('?open=pelanggan');
        }
        db_exec('INSERT INTO pelanggan (nama, no_telepon, alamat, tanggal_registrasi) VALUES (?,?,?,?)', 'ssss', [$d['nama'], $d['no_telepon'], $d['alamat'], date('Y-m-d')]);
        $baru = last_id();
        flash_set('success', 'Pelanggan baru ditambahkan. Silakan tambahkan kendaraannya.');
        redirect('?open=kendaraan_tambah&pelanggan=' . $baru);
    }
}
?>
<div class="card" style="max-width:640px">
  <div class="card-header"><?= e($judul) ?></div>
  <div class="card-body">
    <?php if ($err): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($err as $x) echo '<li>' . e($x) . '</li>'; ?></ul></div><?php endif; ?>
    <form method="post" data-once>
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Nama Pelanggan</label><input name="nama" class="form-control" value="<?= e($d['nama']) ?>" maxlength="100" required></div>
      <div class="mb-3"><label class="form-label">No. Telepon</label><input name="no_telepon" class="form-control" value="<?= e($d['no_telepon']) ?>" maxlength="16" placeholder="081234567890" required></div>
      <div class="mb-3"><label class="form-label">Alamat</label><textarea name="alamat" class="form-control" rows="2" maxlength="255"><?= e($d['alamat']) ?></textarea></div>
      <button type="submit" class="btn btn-brand"><i class="bi bi-save"></i> Simpan</button>
      <a href="?open=pelanggan" class="btn btn-light">Batal</a>
    </form>
  </div>
</div>
