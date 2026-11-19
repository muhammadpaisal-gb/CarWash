<?php
$judul = 'Data Pembayaran';
$q = trim($_GET['q'] ?? ''); $metode = $_GET['metode'] ?? ''; $dari = $_GET['dari'] ?? ''; $sampai = $_GET['sampai'] ?? '';
$per = 10; $page = page_num();
$where = ['1=1']; $types = ''; $params = [];
if ($q !== '') { $where[] = '(t.kode_transaksi LIKE ? OR p.nama LIKE ? OR k.nomor_polisi LIKE ?)'; $like = "%$q%"; $types .= 'sss'; array_push($params, $like, $like, $like); }
if (in_array($metode, ['Tunai', 'Transfer', 'QRIS'], true)) { $where[] = 'b.metode_pembayaran=?'; $types .= 's'; $params[] = $metode; }
if (valid_date($dari)) { $where[] = 'DATE(b.tanggal_pembayaran)>=?'; $types .= 's'; $params[] = $dari; }
if (valid_date($sampai)) { $where[] = 'DATE(b.tanggal_pembayaran)<=?'; $types .= 's'; $params[] = $sampai; }
$w = implode(' AND ', $where);
$from = 'FROM pembayaran b JOIN transaksi t ON t.id_transaksi=b.id_transaksi JOIN pelanggan p ON p.id_pelanggan=t.id_pelanggan JOIN kendaraan k ON k.id_kendaraan=t.id_kendaraan';
$total = (int)db_val("SELECT COUNT(*) $from WHERE $w", $types, $params);
$sum = (float)db_val("SELECT COALESCE(SUM(b.jumlah_dibayar-b.kembalian),0) $from WHERE $w", $types, $params);
$rows = db_all("SELECT b.*, t.kode_transaksi, t.total_harga, p.nama pelanggan, k.nomor_polisi $from WHERE $w ORDER BY b.id_pembayaran DESC LIMIT $per OFFSET " . (($page - 1) * $per), $types, $params);
?>
<div class="card"><div class="card-body">
  <form class="row g-2 mb-3" method="get"><input type="hidden" name="open" value="pembayaran">
    <div class="col-md-3"><input class="form-control form-control-sm" name="q" value="<?= e($q) ?>" placeholder="Kode / pelanggan / nopol"></div>
    <div class="col-6 col-md-2"><select name="metode" class="form-select form-select-sm"><option value="">Semua metode</option>
      <?php foreach (['Tunai', 'Transfer', 'QRIS'] as $m) echo '<option' . ($metode === $m ? ' selected' : '') . '>' . $m . '</option>'; ?></select></div>
    <div class="col-6 col-md-2"><input type="date" name="dari" class="form-control form-control-sm" value="<?= e($dari) ?>"></div>
    <div class="col-6 col-md-2"><input type="date" name="sampai" class="form-control form-control-sm" value="<?= e($sampai) ?>"></div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-brand"><i class="bi bi-funnel"></i> Filter</button></div>
    <div class="col text-end"><a href="?open=pembayaran_tambah" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Catat Pembayaran</a></div>
  </form>
  <div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>Tanggal</th><th>Transaksi</th><th>Pelanggan</th><th>Nopol</th><th>Metode</th><th class="text-end">Dibayar</th><th class="text-end">Kembalian</th><th>Status</th><th class="text-end">Aksi</th></tr></thead><tbody>
    <?php foreach ($rows as $r): ?><tr><td><?= tgl($r['tanggal_pembayaran'], true) ?></td>
      <td><a class="text-decoration-none" href="?open=transaksi_detail&id=<?= $r['id_transaksi'] ?>"><?= e($r['kode_transaksi']) ?></a></td><td><?= e($r['pelanggan']) ?></td><td><?= e($r['nomor_polisi']) ?></td>
      <td><?= e($r['metode_pembayaran']) ?></td><td class="text-end"><?= rupiah($r['jumlah_dibayar']) ?></td><td class="text-end"><?= rupiah($r['kembalian']) ?></td><td><?= badge($r['status_pembayaran']) ?></td>
      <td class="text-end text-nowrap"><?php if (is_admin()): ?><a class="btn btn-sm btn-outline-primary" href="?open=pembayaran_edit&id=<?= $r['id_pembayaran'] ?>"><i class="bi bi-pencil"></i></a> <?= delete_form('pembayaran_hapus', $r['id_pembayaran'], 'pembayaran ini') ?><?php else: ?><a class="btn btn-sm btn-outline-secondary" href="?open=transaksi_detail&id=<?= $r['id_transaksi'] ?>"><i class="bi bi-eye"></i></a><?php endif; ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="9" class="text-center text-muted py-4">Data tidak ditemukan.</td></tr><?php endif; ?>
    </tbody></table></div>
  <div class="d-flex justify-content-between align-items-center"><small class="text-muted"><?= $total ?> data &middot; Total diterima (bersih): <strong><?= rupiah($sum) ?></strong></small><?= pager($total, $per, $page) ?></div>
</div></div>
