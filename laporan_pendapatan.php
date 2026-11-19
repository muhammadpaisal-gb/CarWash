<?php
$judul = 'Laporan Pendapatan';
[$dari, $sampai] = rentang_tanggal();
$harian = db_all('SELECT DATE(b.tanggal_pembayaran) tgl, COUNT(*) n, SUM(b.jumlah_dibayar-b.kembalian) total FROM pembayaran b WHERE DATE(b.tanggal_pembayaran) BETWEEN ? AND ? GROUP BY DATE(b.tanggal_pembayaran) ORDER BY tgl', 'ss', [$dari, $sampai]);
$metode = db_all('SELECT b.metode_pembayaran m, COUNT(*) n, SUM(b.jumlah_dibayar-b.kembalian) total FROM pembayaran b WHERE DATE(b.tanggal_pembayaran) BETWEEN ? AND ? GROUP BY b.metode_pembayaran ORDER BY total DESC', 'ss', [$dari, $sampai]);
$populer = db_all('SELECT l.nama_layanan, SUM(d.jumlah) qty, SUM(d.subtotal) omzet FROM detail_transaksi d JOIN layanan l ON l.id_layanan=d.id_layanan JOIN transaksi t ON t.id_transaksi=d.id_transaksi WHERE DATE(t.tanggal_transaksi) BETWEEN ? AND ? GROUP BY l.id_layanan, l.nama_layanan ORDER BY qty DESC, omzet DESC', 'ss', [$dari, $sampai]);
$totalPendapatan = array_sum(array_column($harian, 'total'));
$jmlTrx = (int)db_val('SELECT COUNT(*) FROM transaksi WHERE DATE(tanggal_transaksi) BETWEEN ? AND ?', 'ss', [$dari, $sampai]);
$piutang = (float)db_val("SELECT COALESCE(SUM(t.total_harga - COALESCE((SELECT SUM(b.jumlah_dibayar-b.kembalian) FROM pembayaran b WHERE b.id_transaksi=t.id_transaksi),0)),0) FROM transaksi t WHERE t.status_bayar='Belum Bayar' AND DATE(t.tanggal_transaksi) BETWEEN ? AND ?", 'ss', [$dari, $sampai]);

$ex = $_GET['export'] ?? '';
if ($ex === 'xls') export_xls('pendapatan_harian', "Pendapatan Harian $dari s/d $sampai", ['Tanggal', 'Jumlah Pembayaran', 'Pendapatan'], array_map(fn($r) => [tgl($r['tgl']), $r['n'], $r['total']], $harian));
if ($ex === 'layanan') export_xls('layanan_populer', "Layanan Populer $dari s/d $sampai", ['Layanan', 'Jumlah Terjual', 'Omzet'], array_map(fn($r) => [$r['nama_layanan'], $r['qty'], $r['omzet']], $populer));
?>
<div class="card mb-3 no-print"><div class="card-body">
  <form class="row g-2 align-items-end" method="get"><input type="hidden" name="open" value="laporan_pendapatan">
    <?= filter_tanggal_form() ?>
    <div class="col-auto"><button class="btn btn-sm btn-brand"><i class="bi bi-funnel"></i> Terapkan</button></div>
    <div class="col text-end">
      <a class="btn btn-sm btn-outline-success" href="<?= e(qs(['export' => 'xls'])) ?>"><i class="bi bi-file-earmark-excel"></i> Excel Harian</a>
      <a class="btn btn-sm btn-outline-success" href="<?= e(qs(['export' => 'layanan'])) ?>"><i class="bi bi-file-earmark-excel"></i> Excel Layanan</a>
      <button type="button" class="btn btn-sm btn-outline-danger" onclick="window.print()"><i class="bi bi-file-earmark-pdf"></i> Cetak / PDF</button></div>
  </form></div></div>
<h6 class="text-muted mb-3">Periode <?= tgl($dari) ?> s/d <?= tgl($sampai) ?></h6>
<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card stat"><div><small>Total Pendapatan (kas masuk bersih)</small><div class="num text-success"><?= rupiah($totalPendapatan) ?></div></div></div></div>
  <div class="col-md-4"><div class="card stat"><div><small>Jumlah Transaksi</small><div class="num"><?= $jmlTrx ?></div></div></div></div>
  <div class="col-md-4"><div class="card stat"><div><small>Piutang (belum dibayar)</small><div class="num text-danger"><?= rupiah($piutang) ?></div></div></div></div>
</div>
<div class="row g-3">
  <div class="col-lg-7"><div class="card"><div class="card-header">Pendapatan Harian</div><div class="table-responsive"><table class="table table-sm mb-0">
    <thead><tr><th>Tanggal</th><th class="text-center">Jml Pembayaran</th><th class="text-end">Pendapatan</th></tr></thead><tbody>
    <?php foreach ($harian as $r): ?><tr><td><?= tgl($r['tgl']) ?></td><td class="text-center"><?= (int)$r['n'] ?></td><td class="text-end"><?= rupiah($r['total']) ?></td></tr>
    <?php endforeach; if (!$harian): ?><tr><td colspan="3" class="text-center text-muted py-3">Tidak ada pembayaran.</td></tr><?php endif; ?></tbody>
    <tfoot><tr class="fw-bold"><td colspan="2" class="text-end">Total</td><td class="text-end"><?= rupiah($totalPendapatan) ?></td></tr></tfoot></table></div></div></div>
  <div class="col-lg-5">
    <div class="card mb-3"><div class="card-header">Per Metode Pembayaran</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Metode</th><th class="text-center">Jml</th><th class="text-end">Total</th></tr></thead><tbody>
      <?php foreach ($metode as $r): ?><tr><td><?= e($r['m']) ?></td><td class="text-center"><?= (int)$r['n'] ?></td><td class="text-end"><?= rupiah($r['total']) ?></td></tr>
      <?php endforeach; if (!$metode): ?><tr><td colspan="3" class="text-center text-muted py-3">-</td></tr><?php endif; ?></tbody></table></div></div>
    <div class="card"><div class="card-header">Layanan Populer</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Layanan</th><th class="text-center">Terjual</th><th class="text-end">Omzet</th></tr></thead><tbody>
      <?php foreach ($populer as $r): ?><tr><td><?= e($r['nama_layanan']) ?></td><td class="text-center"><?= (int)$r['qty'] ?></td><td class="text-end"><?= rupiah($r['omzet']) ?></td></tr>
      <?php endforeach; if (!$populer): ?><tr><td colspan="3" class="text-center text-muted py-3">-</td></tr><?php endif; ?></tbody></table></div></div>
  </div>
</div>
