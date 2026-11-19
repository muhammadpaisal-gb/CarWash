<?php
$judul = 'Proses Pencucian';
$f = $_GET['status'] ?? 'aktif';
$map = ['aktif' => "t.status_transaksi IN ('Menunggu','Sedang Dicuci','Selesai')", 'Menunggu' => "t.status_transaksi='Menunggu'",
        'Sedang Dicuci' => "t.status_transaksi='Sedang Dicuci'", 'Selesai' => "t.status_transaksi='Selesai'", 'Diambil' => "t.status_transaksi='Diambil'"];
if (!isset($map[$f])) $f = 'aktif';
$antrean = db_all("SELECT t.id_transaksi, t.kode_transaksi, t.status_transaksi, t.status_bayar, t.tanggal_transaksi, k.nomor_polisi, k.merek, k.tipe, p.nama pelanggan,
   (SELECT GROUP_CONCAT(CONCAT(l.nama_layanan, IF(d.jumlah>1, CONCAT(' x', d.jumlah), '')) SEPARATOR ', ') FROM detail_transaksi d JOIN layanan l ON l.id_layanan=d.id_layanan WHERE d.id_transaksi=t.id_transaksi) layanan,
   (SELECT COALESCE(SUM(l.estimasi_menit*d.jumlah),0) FROM detail_transaksi d JOIN layanan l ON l.id_layanan=d.id_layanan WHERE d.id_transaksi=t.id_transaksi) estimasi,
   (SELECT MAX(pr.waktu_mulai) FROM proses_pencucian pr WHERE pr.id_transaksi=t.id_transaksi AND pr.status_proses='Sedang Dicuci') mulai
   FROM transaksi t JOIN kendaraan k ON k.id_kendaraan=t.id_kendaraan JOIN pelanggan p ON p.id_pelanggan=t.id_pelanggan
   WHERE {$map[$f]} ORDER BY FIELD(t.status_transaksi,'Sedang Dicuci','Menunggu','Selesai','Diambil'), t.id_transaksi ASC LIMIT 100");
$log = db_all('SELECT pr.*, t.kode_transaksi, k.nomor_polisi, u.nama petugas FROM proses_pencucian pr JOIN transaksi t ON t.id_transaksi=pr.id_transaksi JOIN kendaraan k ON k.id_kendaraan=t.id_kendaraan JOIN users u ON u.id_user=pr.id_user ORDER BY pr.id_proses DESC LIMIT 15');
$aksiLabel = ['Menunggu' => ['mulai', 'Mulai Cuci', 'play-fill', 'info'], 'Sedang Dicuci' => ['selesai', 'Tandai Selesai', 'check2', 'success'], 'Selesai' => ['diambil', 'Serahkan / Diambil', 'key', 'dark']];
?>
<ul class="nav nav-pills mb-3">
  <?php foreach (['aktif' => 'Antrean Aktif', 'Menunggu' => 'Menunggu', 'Sedang Dicuci' => 'Sedang Dicuci', 'Selesai' => 'Selesai', 'Diambil' => 'Diambil'] as $k => $lbl): ?>
    <li class="nav-item"><a class="nav-link<?= $f === $k ? ' active bg-info-subtle text-info-emphasis' : ' text-secondary' ?>" href="?open=proses&status=<?= urlencode($k) ?>"><?= $lbl ?></a></li>
  <?php endforeach; ?>
</ul>
<div class="row g-3 mb-4">
<?php foreach ($antrean as $a): $al = $aksiLabel[$a['status_transaksi']] ?? null; ?>
  <div class="col-md-6 col-xl-4"><div class="card h-100"><div class="card-body">
    <div class="d-flex justify-content-between"><div><div class="fs-5 fw-bold"><?= e($a['nomor_polisi']) ?></div><small class="text-muted"><?= e($a['merek'] . ' ' . $a['tipe']) ?> &middot; <?= e($a['pelanggan']) ?></small></div><div class="text-end"><?= badge($a['status_transaksi']) ?><br><?= badge($a['status_bayar']) ?></div></div>
    <hr class="my-2">
    <div class="small mb-1"><i class="bi bi-stars"></i> <?= e($a['layanan']) ?></div>
    <div class="small text-muted mb-2"><a href="?open=transaksi_detail&id=<?= $a['id_transaksi'] ?>" class="text-decoration-none"><?= e($a['kode_transaksi']) ?></a> &middot; Estimasi <?= (int)$a['estimasi'] ?> menit<?= $a['mulai'] ? ' &middot; Mulai ' . tgl($a['mulai'], true) : '' ?></div>
    <?php if ($al): ?>
    <form method="post" action="?open=proses_aksi&id=<?= $a['id_transaksi'] ?>&status=<?= urlencode($f) ?>" class="d-flex gap-2">
      <?= csrf_field(false) ?><input type="hidden" name="aksi" value="<?= $al[0] ?>">
      <input name="catatan" maxlength="255" class="form-control form-control-sm" placeholder="Catatan pengerjaan (opsional)">
      <?php if ($al[0] === 'diambil' && $a['status_bayar'] !== 'Lunas'): ?>
        <a class="btn btn-sm btn-danger text-nowrap" href="?open=pembayaran_tambah&transaksi=<?= $a['id_transaksi'] ?>">Bayar dulu</a>
      <?php else: ?>
        <button class="btn btn-sm btn-<?= $al[3] ?> text-nowrap"><i class="bi bi-<?= $al[2] ?>"></i> <?= $al[1] ?></button>
      <?php endif; ?>
    </form>
    <?php endif; ?>
  </div></div></div>
<?php endforeach; if (!$antrean): ?><div class="col-12"><div class="card"><div class="card-body text-center text-muted py-5">Tidak ada kendaraan pada kategori ini.</div></div></div><?php endif; ?>
</div>

<div class="card"><div class="card-header">Log Proses Terbaru</div><div class="table-responsive"><table class="table table-sm mb-0">
  <thead><tr><th>Transaksi</th><th>Nopol</th><th>Status</th><th>Mulai</th><th>Selesai</th><th>Petugas</th><th>Catatan</th><th class="text-end">Aksi</th></tr></thead><tbody>
  <?php foreach ($log as $l): ?><tr><td><?= e($l['kode_transaksi']) ?></td><td><?= e($l['nomor_polisi']) ?></td><td><?= badge($l['status_proses']) ?></td><td><?= tgl($l['waktu_mulai'], true) ?></td><td><?= tgl($l['waktu_selesai'], true) ?></td><td><?= e($l['petugas']) ?></td><td><?= e($l['catatan'] ?: '-') ?></td>
    <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="?open=proses_edit&id=<?= $l['id_proses'] ?>"><i class="bi bi-pencil"></i></a><?php if (is_admin()) echo delete_form('proses_hapus', $l['id_proses'], 'log proses ini'); ?></td></tr>
  <?php endforeach; if (!$log): ?><tr><td colspan="8" class="text-center text-muted py-3">Belum ada log.</td></tr><?php endif; ?></tbody></table></div></div>
