<?php
$judul = 'Laporan Transaksi';
[$dari, $sampai] = rentang_tanggal();
$q = trim($_GET['q'] ?? ''); $sp = $_GET['proses'] ?? ''; $sb = $_GET['bayar'] ?? '';
$where = ['DATE(t.tanggal_transaksi) BETWEEN ? AND ?']; $types = 'ss'; $params = [$dari, $sampai];
if ($q !== '') { $where[] = '(t.kode_transaksi LIKE ? OR p.nama LIKE ? OR k.nomor_polisi LIKE ?)'; $like = "%$q%"; $types .= 'sss'; array_push($params, $like, $like, $like); }
if (in_array($sp, ['Menunggu', 'Sedang Dicuci', 'Selesai', 'Diambil'], true)) { $where[] = 't.status_transaksi=?'; $types .= 's'; $params[] = $sp; }
if (in_array($sb, ['Belum Bayar', 'Lunas'], true)) { $where[] = 't.status_bayar=?'; $types .= 's'; $params[] = $sb; }
$w = implode(' AND ', $where);
$from = 'FROM transaksi t JOIN pelanggan p ON p.id_pelanggan=t.id_pelanggan JOIN kendaraan k ON k.id_kendaraan=t.id_kendaraan JOIN users u ON u.id_user=t.id_user';
$sel = "SELECT t.*, p.nama pelanggan, k.nomor_polisi, u.nama kasir,
   (SELECT GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') FROM detail_transaksi d JOIN layanan l ON l.id_layanan=d.id_layanan WHERE d.id_transaksi=t.id_transaksi) layanan,
   (SELECT COALESCE(SUM(b.jumlah_dibayar-b.kembalian),0) FROM pembayaran b WHERE b.id_transaksi=t.id_transaksi) terbayar $from WHERE $w ORDER BY t.tanggal_transaksi DESC, t.id_transaksi DESC";
$ringkas = db_one("SELECT COUNT(*) n, COALESCE(SUM(t.total_harga),0) tot, COALESCE(SUM(t.diskon),0) dsk $from WHERE $w", $types, $params);
$terbayarTotal = (float)db_val("SELECT COALESCE(SUM(b.jumlah_dibayar-b.kembalian),0) FROM pembayaran b JOIN transaksi t ON t.id_transaksi=b.id_transaksi JOIN pelanggan p ON p.id_pelanggan=t.id_pelanggan JOIN kendaraan k ON k.id_kendaraan=t.id_kendaraan WHERE $w", $types, $params);

if (($_GET['export'] ?? '') === 'xls') {
    $r = [];
    foreach (db_all($sel, $types, $params) as $x) $r[] = [$x['kode_transaksi'], tgl($x['tanggal_transaksi'], true), $x['pelanggan'], $x['nomor_polisi'], $x['layanan'], $x['subtotal'], $x['diskon'], $x['total_harga'], $x['terbayar'], $x['status_transaksi'], $x['status_bayar'], $x['kasir']];
    export_xls('laporan_transaksi', "Laporan Transaksi $dari s/d $sampai", ['Kode', 'Tanggal', 'Pelanggan', 'Nopol', 'Layanan', 'Subtotal', 'Diskon', 'Total', 'Terbayar', 'Proses', 'Bayar', 'Kasir'], $r);
}
$cetak = isset($_GET['cetak']); $per = 20; $page = page_num();
$rows = db_all($sel . ($cetak ? '' : " LIMIT $per OFFSET " . (($page - 1) * $per)), $types, $params);
?>
<div class="card mb-3 no-print"><div class="card-body">
  <form class="row g-2 align-items-end" method="get"><input type="hidden" name="open" value="laporan_transaksi">
    <?= filter_tanggal_form() ?>
    <div class="col-md-2"><label class="form-label small">Proses</label><select name="proses" class="form-select form-select-sm"><option value="">Semua</option>
      <?php foreach (['Menunggu', 'Sedang Dicuci', 'Selesai', 'Diambil'] as $s) echo '<option' . ($sp === $s ? ' selected' : '') . '>' . $s . '</option>'; ?></select></div>
    <div class="col-md-2"><label class="form-label small">Pembayaran</label><select name="bayar" class="form-select form-select-sm"><option value="">Semua</option>
      <?php foreach (['Belum Bayar', 'Lunas'] as $s) echo '<option' . ($sb === $s ? ' selected' : '') . '>' . $s . '</option>'; ?></select></div>
    <div class="col-md-3"><label class="form-label small">Cari</label><input name="q" class="form-control form-control-sm" value="<?= e($q) ?>" placeholder="Kode / pelanggan / nopol"></div>
    <div class="col-auto"><button class="btn btn-sm btn-brand"><i class="bi bi-funnel"></i> Terapkan</button></div>
    <div class="col text-end">
      <a class="btn btn-sm btn-outline-success" href="<?= e(qs(['export' => 'xls', 'page' => null])) ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a>
      <a class="btn btn-sm btn-outline-danger" target="_blank" href="<?= e(qs(['cetak' => 1, 'page' => null])) ?>"><i class="bi bi-file-earmark-pdf"></i> Cetak / PDF</a></div>
  </form></div></div>
<div class="card"><div class="card-header">Laporan Transaksi <small class="text-muted fw-normal">&middot; <?= tgl($dari) ?> s/d <?= tgl($sampai) ?></small></div>
  <div class="row g-0 text-center border-bottom">
    <div class="col-4 p-3"><small class="text-muted">Jumlah Transaksi</small><div class="fs-5 fw-bold"><?= (int)$ringkas['n'] ?></div></div>
    <div class="col-4 p-3"><small class="text-muted">Total Nilai Transaksi</small><div class="fs-5 fw-bold"><?= rupiah($ringkas['tot']) ?></div></div>
    <div class="col-4 p-3"><small class="text-muted">Total Sudah Dibayar</small><div class="fs-5 fw-bold text-success"><?= rupiah($terbayarTotal) ?></div></div>
  </div>
  <div class="table-responsive"><table class="table table-sm table-hover mb-0">
    <thead><tr><th>Kode</th><th>Tanggal</th><th>Pelanggan</th><th>Nopol</th><th>Layanan</th><th class="text-end">Total</th><th class="text-end">Terbayar</th><th>Proses</th><th>Bayar</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td><?= e($r['kode_transaksi']) ?></td><td><?= tgl($r['tanggal_transaksi'], true) ?></td><td><?= e($r['pelanggan']) ?></td><td><?= e($r['nomor_polisi']) ?></td><td><?= e($r['layanan']) ?></td>
      <td class="text-end"><?= rupiah($r['total_harga']) ?></td><td class="text-end"><?= rupiah($r['terbayar']) ?></td><td><?= badge($r['status_transaksi']) ?></td><td><?= badge($r['status_bayar']) ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">Tidak ada transaksi pada periode ini.</td></tr><?php endif; ?></tbody></table></div>
  <?php if (!$cetak): ?><div class="card-body"><?= pager((int)$ringkas['n'], $per, $page) ?></div><?php endif; ?>
</div>
<?php if ($cetak): ?><script>window.addEventListener('load', function () { window.print(); });</script><?php endif; ?>
