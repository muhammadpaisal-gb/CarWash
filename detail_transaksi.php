<?php
$judul = 'Detail Transaksi';
$id = (int)($_GET['id'] ?? 0);
$t = db_one('SELECT t.*, p.nama pelanggan, p.no_telepon, k.nomor_polisi, k.merek, k.tipe, k.warna, u.nama kasir
             FROM transaksi t JOIN pelanggan p ON p.id_pelanggan=t.id_pelanggan JOIN kendaraan k ON k.id_kendaraan=t.id_kendaraan JOIN users u ON u.id_user=t.id_user
             WHERE t.id_transaksi=?', 'i', [$id]);
if (!$t) { flash_set('danger', 'Transaksi tidak ditemukan.'); redirect('?open=transaksi'); }
$items = db_all('SELECT d.*, l.nama_layanan FROM detail_transaksi d JOIN layanan l ON l.id_layanan=d.id_layanan WHERE d.id_transaksi=?', 'i', [$id]);
$bayar = db_all('SELECT * FROM pembayaran WHERE id_transaksi=? ORDER BY tanggal_pembayaran, id_pembayaran', 'i', [$id]);
$proses = db_all('SELECT pr.*, u.nama petugas FROM proses_pencucian pr JOIN users u ON u.id_user=pr.id_user WHERE pr.id_transaksi=? ORDER BY pr.id_proses', 'i', [$id]);
$terbayar = total_terbayar($id);
$sisa = max(0, (float)$t['total_harga'] - $terbayar);
?>
<div class="nota">
<div class="d-flex flex-wrap gap-2 mb-3 no-print">
  <a href="?open=transaksi" class="btn btn-light"><i class="bi bi-arrow-left"></i> Kembali</a>
  <button class="btn btn-outline-brand" onclick="window.print()"><i class="bi bi-printer"></i> Cetak Nota / Simpan PDF</button>
  <?php if ($t['status_transaksi'] === 'Menunggu'): ?><a class="btn btn-outline-primary" href="?open=transaksi_edit&id=<?= $id ?>"><i class="bi bi-pencil"></i> Edit</a><?php endif; ?>
  <?php if ($t['status_bayar'] !== 'Lunas'): ?><a class="btn btn-brand" href="?open=pembayaran_tambah&transaksi=<?= $id ?>"><i class="bi bi-cash-coin"></i> Bayar</a><?php endif; ?>
</div>
<div class="card mb-3"><div class="card-body">
  <div class="d-flex justify-content-between flex-wrap">
    <div><h4 class="fw-bold mb-0"><?= e($t['kode_transaksi']) ?></h4><small class="text-muted"><?= tgl($t['tanggal_transaksi'], true) ?> &middot; Kasir: <?= e($t['kasir']) ?></small></div>
    <div><?= badge($t['status_transaksi']) ?> <?= badge($t['status_bayar']) ?></div>
  </div><hr>
  <div class="row">
    <div class="col-md-6"><small class="text-muted">Pelanggan</small><div class="fw-semibold"><?= e($t['pelanggan']) ?></div><div><?= e($t['no_telepon']) ?></div></div>
    <div class="col-md-6"><small class="text-muted">Kendaraan</small><div class="fw-semibold"><?= e($t['nomor_polisi']) ?></div><div><?= e($t['merek'] . ' ' . $t['tipe'] . ' - ' . $t['warna']) ?></div></div>
  </div>
</div>
<table class="table mb-0"><thead><tr><th>Layanan</th><th class="text-end">Harga</th><th class="text-center">Jml</th><th class="text-end">Subtotal</th></tr></thead><tbody>
<?php foreach ($items as $i): ?><tr><td><?= e($i['nama_layanan']) ?></td><td class="text-end"><?= rupiah($i['harga_saat_transaksi']) ?></td><td class="text-center"><?= (int)$i['jumlah'] ?></td><td class="text-end"><?= rupiah($i['subtotal']) ?></td></tr><?php endforeach; ?>
</tbody><tfoot>
  <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end"><?= rupiah($t['subtotal']) ?></td></tr>
  <tr><td colspan="3" class="text-end">Diskon</td><td class="text-end">- <?= rupiah($t['diskon']) ?></td></tr>
  <tr class="fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end"><?= rupiah($t['total_harga']) ?></td></tr>
  <tr><td colspan="3" class="text-end">Sudah dibayar</td><td class="text-end"><?= rupiah($terbayar) ?></td></tr>
  <tr class="fw-bold"><td colspan="3" class="text-end">Sisa tagihan</td><td class="text-end <?= $sisa > 0 ? 'text-danger' : 'text-success' ?>"><?= rupiah($sisa) ?></td></tr>
</tfoot></table></div>

<div class="row g-3">
  <div class="col-md-6"><div class="card h-100"><div class="card-header">Pembayaran</div><div class="table-responsive"><table class="table table-sm mb-0">
    <thead><tr><th>Tanggal</th><th>Metode</th><th class="text-end">Dibayar</th><th class="text-end">Kembali</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($bayar as $b): ?><tr><td><?= tgl($b['tanggal_pembayaran'], true) ?></td><td><?= e($b['metode_pembayaran']) ?></td><td class="text-end"><?= rupiah($b['jumlah_dibayar']) ?></td><td class="text-end"><?= rupiah($b['kembalian']) ?></td><td><?= badge($b['status_pembayaran']) ?></td></tr>
    <?php endforeach; if (!$bayar): ?><tr><td colspan="5" class="text-center text-muted py-3">Belum ada pembayaran.</td></tr><?php endif; ?></tbody></table></div></div></div>
  <div class="col-md-6 no-print"><div class="card h-100"><div class="card-header">Riwayat Proses</div><div class="table-responsive"><table class="table table-sm mb-0">
    <thead><tr><th>Status</th><th>Mulai</th><th>Selesai</th><th>Petugas</th><th>Catatan</th></tr></thead><tbody>
    <?php foreach ($proses as $p): ?><tr><td><?= badge($p['status_proses']) ?></td><td><?= tgl($p['waktu_mulai'], true) ?></td><td><?= tgl($p['waktu_selesai'], true) ?></td><td><?= e($p['petugas']) ?></td><td><?= e($p['catatan'] ?: '-') ?></td></tr>
    <?php endforeach; ?></tbody></table></div></div></div>
</div>
</div>
