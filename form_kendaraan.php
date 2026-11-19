<?php
$id = (int)($_GET['id'] ?? 0);
$judul = $id ? 'Edit Kendaraan' : 'Tambah Kendaraan';
$d = ['id_pelanggan' => (int)($_GET['pelanggan'] ?? 0), 'nomor_polisi' => '', 'merek' => '', 'tipe' => '', 'warna' => '', 'jenis_kendaraan' => 'Mobil'];
if ($id) {
    $row = db_one('SELECT * FROM kendaraan WHERE id_kendaraan=?', 'i', [$id]);
    if (!$row) { flash_set('danger', 'Kendaraan tidak ditemukan.'); redirect('?open=kendaraan'); }
    $d = $row;
}
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $d['id_pelanggan'] = (int)post('id_pelanggan');
    $plat = normalisasi_plat(post('nomor_polisi'));
    $d['nomor_polisi'] = $plat ?? strtoupper(post('nomor_polisi'));
    foreach (['merek', 'tipe', 'warna'] as $f) $d[$f] = post($f);
    $d['jenis_kendaraan'] = post('jenis_kendaraan');
    if (!db_one('SELECT 1 FROM pelanggan WHERE id_pelanggan=?', 'i', [$d['id_pelanggan']])) $err[] = 'Pilih pelanggan pemilik kendaraan.';
    if ($plat === null) $err[] = 'Format nomor polisi tidak valid (contoh: DA 1234 XX).';
    foreach (['merek' => 'Merek', 'tipe' => 'Tipe', 'warna' => 'Warna'] as $f => $lbl) if ($d[$f] === '' || mb_strlen($d[$f]) > 50) $err[] = "$lbl wajib diisi (maks. 50 karakter).";
    if (!in_array($d['jenis_kendaraan'], ['Mobil', 'SUV', 'MPV', 'Pickup', 'Truk'], true)) $err[] = 'Jenis kendaraan tidak valid.';
    if (!$err && db_one('SELECT 1 FROM kendaraan WHERE nomor_polisi=? AND id_kendaraan<>?', 'si', [$plat, $id])) $err[] = 'Nomor polisi sudah terdaftar.';
    if (!$err) {
        if ($id) {
            db_exec('UPDATE kendaraan SET id_pelanggan=?, nomor_polisi=?, merek=?, tipe=?, warna=?, jenis_kendaraan=? WHERE id_kendaraan=?', 'isssssi',
                [$d['id_pelanggan'], $plat, $d['merek'], $d['tipe'], $d['warna'], $d['jenis_kendaraan'], $id]);
            flash_set('success', 'Data kendaraan diperbarui.');
        } else {
            db_exec('INSERT INTO kendaraan (id_pelanggan, nomor_polisi, merek, tipe, warna, jenis_kendaraan) VALUES (?,?,?,?,?,?)', 'isssss',
                [$d['id_pelanggan'], $plat, $d['merek'], $d['tipe'], $d['warna'], $d['jenis_kendaraan']]);
            flash_set('success', 'Kendaraan ditambahkan.');
        }
        redirect(isset($_GET['kembali']) && $_GET['kembali'] === 'transaksi' ? '?open=transaksi_tambah&pelanggan=' . $d['id_pelanggan'] : '?open=kendaraan');
    }
}
$pel = db_all('SELECT id_pelanggan, nama, no_telepon FROM pelanggan ORDER BY nama');
?>
<div class="card" style="max-width:720px">
  <div class="card-header"><?= e($judul) ?></div>
  <div class="card-body">
    <?php if ($err): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($err as $x) echo '<li>' . e($x) . '</li>'; ?></ul></div><?php endif; ?>
    <form method="post" data-once class="row g-3">
      <?= csrf_field() ?>
      <div class="col-12"><label class="form-label">Pemilik (Pelanggan)</label>
        <select name="id_pelanggan" class="form-select" required><option value="">-- Pilih pelanggan --</option>
        <?php foreach ($pel as $p): ?><option value="<?= $p['id_pelanggan'] ?>"<?= (int)$d['id_pelanggan'] === (int)$p['id_pelanggan'] ? ' selected' : '' ?>><?= e($p['nama'] . ' - ' . $p['no_telepon']) ?></option><?php endforeach; ?></select>
        <?php if (!$pel): ?><div class="form-text text-danger">Belum ada pelanggan. <a href="?open=pelanggan_tambah">Tambah pelanggan dahulu</a>.</div><?php endif; ?></div>
      <div class="col-md-6"><label class="form-label">Nomor Polisi</label><input name="nomor_polisi" class="form-control text-uppercase" value="<?= e($d['nomor_polisi']) ?>" placeholder="DA 1234 XX" maxlength="15" required></div>
      <div class="col-md-6"><label class="form-label">Jenis Kendaraan</label><select name="jenis_kendaraan" class="form-select">
        <?php foreach (['Mobil', 'SUV', 'MPV', 'Pickup', 'Truk'] as $j) echo '<option' . ($d['jenis_kendaraan'] === $j ? ' selected' : '') . '>' . $j . '</option>'; ?></select></div>
      <div class="col-md-4"><label class="form-label">Merek</label><input name="merek" class="form-control" value="<?= e($d['merek']) ?>" placeholder="Toyota" required></div>
      <div class="col-md-4"><label class="form-label">Tipe</label><input name="tipe" class="form-control" value="<?= e($d['tipe']) ?>" placeholder="Avanza" required></div>
      <div class="col-md-4"><label class="form-label">Warna</label><input name="warna" class="form-control" value="<?= e($d['warna']) ?>" placeholder="Putih" required></div>
      <div class="col-12"><button type="submit" class="btn btn-brand"><i class="bi bi-save"></i> Simpan</button> <a href="?open=kendaraan" class="btn btn-light">Batal</a></div>
    </form>
  </div>
</div>
