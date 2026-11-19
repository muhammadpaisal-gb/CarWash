<?php
$judul = 'Edit Log Proses';
$id = (int)($_GET['id'] ?? 0);
$d = db_one('SELECT pr.*, t.kode_transaksi FROM proses_pencucian pr JOIN transaksi t ON t.id_transaksi=pr.id_transaksi WHERE pr.id_proses=?', 'i', [$id]);
if (!$d) { flash_set('danger', 'Log proses tidak ditemukan.'); redirect('?open=proses'); }
$err = [];
$dt = fn($v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $d['catatan'] = post('catatan');
    $m = post('waktu_mulai'); $s = post('waktu_selesai');
    $mulai = $m !== '' ? date('Y-m-d H:i:s', strtotime($m)) : null; $selesai = $s !== '' ? date('Y-m-d H:i:s', strtotime($s)) : null;
    if (($m !== '' && !strtotime($m)) || ($s !== '' && !strtotime($s))) $err[] = 'Format waktu tidak valid.';
    if ($mulai && $selesai && $selesai < $mulai) $err[] = 'Waktu selesai tidak boleh lebih awal dari waktu mulai.';
    if (mb_strlen($d['catatan']) > 255) $err[] = 'Catatan maksimal 255 karakter.';
    $d['waktu_mulai'] = $mulai; $d['waktu_selesai'] = $selesai;
    if (!$err) {
        db_exec('UPDATE proses_pencucian SET catatan=?, waktu_mulai=?, waktu_selesai=? WHERE id_proses=?', 'sssi', [$d['catatan'], $mulai, $selesai, $id]);
        flash_set('success', 'Log proses diperbarui.');
        redirect('?open=proses');
    }
}
?>
<div class="card" style="max-width:560px"><div class="card-header">Edit Log Proses - <?= e($d['kode_transaksi']) ?> <?= badge($d['status_proses']) ?></div><div class="card-body">
  <?php if ($err): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($err as $x) echo '<li>' . e($x) . '</li>'; ?></ul></div><?php endif; ?>
  <form method="post" data-once class="row g-3"><?= csrf_field() ?>
    <div class="col-md-6"><label class="form-label">Waktu Mulai</label><input type="datetime-local" name="waktu_mulai" class="form-control" value="<?= e($dt($d['waktu_mulai'])) ?>"></div>
    <div class="col-md-6"><label class="form-label">Waktu Selesai</label><input type="datetime-local" name="waktu_selesai" class="form-control" value="<?= e($dt($d['waktu_selesai'])) ?>"></div>
    <div class="col-12"><label class="form-label">Catatan Pengerjaan</label><textarea name="catatan" rows="3" maxlength="255" class="form-control"><?= e($d['catatan']) ?></textarea></div>
    <div class="col-12"><button type="submit" class="btn btn-brand"><i class="bi bi-save"></i> Simpan</button> <a href="?open=proses" class="btn btn-light">Batal</a></div>
  </form></div></div>
