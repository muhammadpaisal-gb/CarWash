<?php
$judul = 'Detail Pelanggan';
$id = (int)($_GET['id'] ?? 0);
$p = db_one('SELECT * FROM pelanggan WHERE id_pelanggan=?', 'i', [$id]);
if (!$p) { flash_set('danger', 'Pelanggan tidak ditemukan.'); redirect('?open=pelanggan'); }
$kend = db_all('SELECT * FROM kendaraan WHERE id_pelanggan=? ORDER BY id_kendaraan', 'i', [$id]);
$trx = db_all('SELECT t.*, k.nomor_polisi FROM transaksi t JOIN kendaraan k ON k.id_kendaraan=t.id_kendaraan WHERE t.id_pelanggan=? ORDER BY t.id_transaksi DESC LIMIT 20', 'i', [$id]);
?>
<div class="row g-3">
  <div class="col-lg-4"><div class="card"><div class="card-body">
    <h5 class="fw-bold"><?= e($p['nama']) ?></h5>
    <p class="mb-1"><i class="bi bi-telephone"></i> <?= e($p['no_telepon']) ?></p>
    <p class="mb-1"><i class="bi bi-geo-alt"></i> <?= e($p['alamat'] ?: '-') ?></p>
    <p class="text-muted small">Terdaftar: <?= tgl($p['tanggal_registrasi']) ?></p>
    <a href="?open=transaksi_tambah&pelanggan=<?= $id ?>" class="btn btn-brand btn-sm"><i class="bi bi-receipt"></i> Buat Transaksi</a>
    <a href="?open=pelanggan" class="btn btn-light btn-sm">Kembali</a>
  </div></div></div>
  <div class="col-lg-8">
    <div class="card mb-3"><div class="card-header d-flex justify-content-between">Kendaraan <a class="btn btn-sm btn-outline-brand" href="?open=kendaraan_tambah&pelanggan=<?= $id ?>">+ Tambah</a></div>
      <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Nopol</th><th>Merek / Tipe</th><th>Warna</th><th>Jenis</th></tr></thead><tbody>
      <?php foreach ($kend as $k): ?><tr><td class="fw-semibold"><?= e($k['nomor_polisi']) ?></td><td><?= e($k['merek'] . ' ' . $k['tipe']) ?></td><td><?= e($k['warna']) ?></td><td><?= e($k['jenis_kendaraan']) ?></td></tr>
      <?php endforeach; if (!$kend): ?><tr><td colspan="4" class="text-muted text-center py-3">Belum ada kendaraan.</td></tr><?php endif; ?></tbody></table></div></div>
    <div class="card"><div class="card-header">Riwayat Transaksi</div>
      <div class="table-responsive"><table class="table mb-0"><thead><tr><th>Kode</th><th>Tanggal</th><th>Nopol</th><th class="text-end">Total</th><th>Proses</th><th>Bayar</th></tr></thead><tbody>
      <?php foreach ($trx as $t): ?><tr><td><a href="?open=transaksi_detail&id=<?= $t['id_transaksi'] ?>"><?= e($t['kode_transaksi']) ?></a></td><td><?= tgl($t['tanggal_transaksi'], true) ?></td><td><?= e($t['nomor_polisi']) ?></td><td class="text-end"><?= rupiah($t['total_harga']) ?></td><td><?= badge($t['status_transaksi']) ?></td><td><?= badge($t['status_bayar']) ?></td></tr>
      <?php endforeach; if (!$trx): ?><tr><td colspan="6" class="text-muted text-center py-3">Belum ada transaksi.</td></tr><?php endif; ?></tbody></table></div></div>
  </div>
</div>
