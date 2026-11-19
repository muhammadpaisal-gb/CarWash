<?php
$judul = 'Riwayat Pencucian';
$q = trim($_GET['q'] ?? '');
$where = ['1=1']; $types = ''; $params = [];
if ($q !== '') { $where[] = '(k.nomor_polisi LIKE ? OR p.nama LIKE ?)'; $like = "%$q%"; $types .= 'ss'; array_push($params, $like, $like); }
if (($_GET['dari'] ?? '') !== '' || ($_GET['sampai'] ?? '') !== '' || ($_GET['bulan'] ?? '') !== '' || ($_GET['tahun'] ?? '') !== '') {
    [$dari, $sampai] = rentang_tanggal();
    $where[] = 'DATE(t.tanggal_transaksi) BETWEEN ? AND ?'; $types .= 'ss'; array_push($params, $dari, $sampai);
}
$w = implode(' AND ', $where);
$from = 'FROM transaksi t JOIN kendaraan k ON k.id_kendaraan=t.id_kendaraan JOIN pelanggan p ON p.id_pelanggan=t.id_pelanggan';
$sel = "SELECT t.kode_transaksi, t.tanggal_transaksi, t.status_transaksi, t.total_harga, k.nomor_polisi, k.merek, k.tipe, p.nama pelanggan,
   (SELECT GROUP_CONCAT(l.nama_layanan SEPARATOR ', ') FROM detail_transaksi d JOIN layanan l ON l.id_layanan=d.id_layanan WHERE d.id_transaksi=t.id_transaksi) layanan,
   (SELECT MIN(pr.waktu_mulai) FROM proses_pencucian pr WHERE pr.id_transaksi=t.id_transaksi) mulai,
   (SELECT MAX(pr.waktu_selesai) FROM proses_pencucian pr WHERE pr.id_transaksi=t.id_transaksi AND pr.status_proses='Selesai') selesai
   $from WHERE $w ORDER BY t.tanggal_transaksi DESC";
if (($_GET['export'] ?? '') === 'xls') {
    $r = [];
    foreach (db_all($sel, $types, $params) as $x) $r[] = [$x['nomor_polisi'], $x['merek'] . ' ' . $x['tipe'], $x['pelanggan'], $x['kode_transaksi'], tgl($x['tanggal_transaksi'], true), $x['layanan'], tgl($x['mulai'], true), tgl($x['selesai'], true), $x['status_transaksi'], $x['total_harga']];
    export_xls('riwayat_pencucian', 'Riwayat Pencucian Kendaraan', ['Nopol', 'Kendaraan', 'Pelanggan', 'Kode', 'Tanggal', 'Layanan', 'Mulai', 'Selesai', 'Status', 'Total'], $r);
}
$total = (int)db_val("SELECT COUNT(*) $from WHERE $w", $types, $params);
$cetak = isset($_GET['cetak']); $per = 20; $page = page_num();
$rows = db_all($sel . ($cetak ? '' : " LIMIT $per OFFSET " . (($page - 1) * $per)), $types, $params);
?>
<div class="card mb-3 no-print"><div class="card-body">
  <form class="row g-2 align-items-end" method="get"><input type="hidden" name="open" value="riwayat">
    <div class="col-md-3"><label class="form-label small">Nopol / Pelanggan</label><input name="q" class="form-control form-control-sm" value="<?= e($q) ?>" placeholder="DA 1234 XX"></div>
    <?= filter_tanggal_form() ?>
    <div class="col-auto"><button class="btn btn-sm btn-brand"><i class="bi bi-search"></i> Cari</button></div>
    <div class="col text-end"><a class="btn btn-sm btn-outline-success" href="<?= e(qs(['export' => 'xls', 'page' => null])) ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a>
      <a class="btn btn-sm btn-outline-danger" target="_blank" href="<?= e(qs(['cetak' => 1, 'page' => null])) ?>"><i class="bi bi-file-earmark-pdf"></i> Cetak / PDF</a></div>
  </form></div></div>
<div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
  <thead><tr><th>Nopol</th><th>Kendaraan</th><th>Pelanggan</th><th>Transaksi</th><th>Tanggal</th><th>Layanan</th><th>Mulai</th><th>Selesai</th><th>Status</th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?><tr><td class="fw-semibold"><?= e($r['nomor_polisi']) ?></td><td><?= e($r['merek'] . ' ' . $r['tipe']) ?></td><td><?= e($r['pelanggan']) ?></td><td><?= e($r['kode_transaksi']) ?></td>
    <td><?= tgl($r['tanggal_transaksi'], true) ?></td><td><?= e($r['layanan']) ?></td><td><?= tgl($r['mulai'], true) ?></td><td><?= tgl($r['selesai'], true) ?></td><td><?= badge($r['status_transaksi']) ?></td></tr>
  <?php endforeach; if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">Tidak ada riwayat.</td></tr><?php endif; ?></tbody></table></div>
  <?php if (!$cetak): ?><div class="card-body d-flex justify-content-between align-items-center"><small class="text-muted"><?= $total ?> data</small><?= pager($total, $per, $page) ?></div><?php endif; ?></div>
<?php if ($cetak): ?><script>window.addEventListener('load', function () { window.print(); });</script><?php endif; ?>
