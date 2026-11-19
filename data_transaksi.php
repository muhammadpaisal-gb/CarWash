<?php
$judul = 'Data Transaksi';
$q = trim($_GET['q'] ?? ''); $sp = $_GET['proses'] ?? ''; $sb = $_GET['bayar'] ?? '';
$dari = $_GET['dari'] ?? ''; $sampai = $_GET['sampai'] ?? '';
$per = 10; $page = page_num();
$where = ['1=1']; $types = ''; $params = [];
if ($q !== '') { $where[] = '(t.kode_transaksi LIKE ? OR p.nama LIKE ? OR k.nomor_polisi LIKE ?)'; $like = "%$q%"; $types .= 'sss'; array_push($params, $like, $like, $like); }
if (in_array($sp, ['Menunggu', 'Sedang Dicuci', 'Selesai', 'Diambil'], true)) { $where[] = 't.status_transaksi=?'; $types .= 's'; $params[] = $sp; }
if (in_array($sb, ['Belum Bayar', 'Lunas'], true)) { $where[] = 't.status_bayar=?'; $types .= 's'; $params[] = $sb; }
if (valid_date($dari)) { $where[] = 'DATE(t.tanggal_transaksi)>=?'; $types .= 's'; $params[] = $dari; }
if (valid_date($sampai)) { $where[] = 'DATE(t.tanggal_transaksi)<=?'; $types .= 's'; $params[] = $sampai; }
$w = implode(' AND ', $where);
$from = 'FROM transaksi t JOIN pelanggan p ON p.id_pelanggan=t.id_pelanggan JOIN kendaraan k ON k.id_kendaraan=t.id_kendaraan JOIN users u ON u.id_user=t.id_user';
$total = (int)db_val("SELECT COUNT(*) $from WHERE $w", $types, $params);
$rows = db_all("SELECT t.*, p.nama pelanggan, k.nomor_polisi, u.nama kasir $from WHERE $w ORDER BY t.id_transaksi DESC LIMIT $per OFFSET " . (($page - 1) * $per), $types, $params);
?>
<div class="card"><div class="card-body">
  <form class="row g-2 mb-3" method="get">
    <input type="hidden" name="open" value="transaksi">
    <div class="col-md-3"><input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Kode / pelanggan / nopol"></div>
    <div class="col-6 col-md-2"><select name="proses" class="form-select form-select-sm"><option value="">Semua proses</option>
      <?php foreach (['Menunggu', 'Sedang Dicuci', 'Selesai', 'Diambil'] as $s) echo '<option' . ($sp === $s ? ' selected' : '') . '>' . $s . '</option>'; ?></select></div>
    <div class="col-6 col-md-2"><select name="bayar" class="form-select form-select-sm"><option value="">Semua bayar</option>
      <?php foreach (['Belum Bayar', 'Lunas'] as $s) echo '<option' . ($sb === $s ? ' selected' : '') . '>' . $s . '</option>'; ?></select></div>
    <div class="col-6 col-md-2"><input type="date" name="dari" class="form-control form-control-sm" value="<?= e($dari) ?>" title="Dari tanggal"></div>
    <div class="col-6 col-md-2"><input type="date" name="sampai" class="form-control form-control-sm" value="<?= e($sampai) ?>" title="Sampai tanggal"></div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-brand"><i class="bi bi-funnel"></i> Filter</button></div>
    <div class="col-12 text-end"><a href="?open=transaksi_tambah" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Tambah Transaksi</a></div>
  </form>
  <div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Kode</th><th>Tanggal</th><th>Pelanggan</th><th>Nopol</th><th class="text-end">Total</th><th>Proses</th><th>Bayar</th><th>Kasir</th><th class="text-end">Aksi</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?>
      <tr><td class="fw-semibold"><?= e($r['kode_transaksi']) ?></td><td><?= tgl($r['tanggal_transaksi'], true) ?></td><td><?= e($r['pelanggan']) ?></td><td><?= e($r['nomor_polisi']) ?></td>
        <td class="text-end"><?= rupiah($r['total_harga']) ?></td><td><?= badge($r['status_transaksi']) ?></td><td><?= badge($r['status_bayar']) ?></td><td><?= e($r['kasir']) ?></td>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-outline-secondary" title="Detail" href="?open=transaksi_detail&id=<?= $r['id_transaksi'] ?>"><i class="bi bi-eye"></i></a>
          <?php if ($r['status_transaksi'] === 'Menunggu'): ?><a class="btn btn-sm btn-outline-primary" title="Edit" href="?open=transaksi_edit&id=<?= $r['id_transaksi'] ?>"><i class="bi bi-pencil"></i></a><?php endif; ?>
          <?php if (is_admin()) echo delete_form('transaksi_hapus', $r['id_transaksi'], 'transaksi ' . $r['kode_transaksi']); ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">Data tidak ditemukan.</td></tr><?php endif; ?>
    </tbody></table></div>
  <div class="d-flex justify-content-between align-items-center"><small class="text-muted"><?= $total ?> data</small><?= pager($total, $per, $page) ?></div>
</div></div>
