<?php
$judul = 'Data Layanan';
$q = trim($_GET['q'] ?? ''); $st = $_GET['status'] ?? '';
$where = ['1=1']; $types = ''; $params = [];
if ($q !== '') { $where[] = '(nama_layanan LIKE ? OR deskripsi LIKE ?)'; $like = "%$q%"; $types .= 'ss'; array_push($params, $like, $like); }
if ($st === 'aktif') $where[] = 'status_aktif=1';
if ($st === 'nonaktif') $where[] = 'status_aktif=0';
$rows = db_all('SELECT * FROM layanan WHERE ' . implode(' AND ', $where) . ' ORDER BY status_aktif DESC, nama_layanan', $types, $params);
?>
<div class="card"><div class="card-body">
  <form class="row g-2 mb-3" method="get">
    <input type="hidden" name="open" value="layanan">
    <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari layanan..."></div>
    <div class="col-md-3"><select name="status" class="form-select" onchange="this.form.submit()">
      <option value="">Semua status</option><option value="aktif"<?= $st === 'aktif' ? ' selected' : '' ?>>Aktif</option><option value="nonaktif"<?= $st === 'nonaktif' ? ' selected' : '' ?>>Nonaktif</option></select></div>
    <div class="col-auto"><button class="btn btn-outline-brand"><i class="bi bi-search"></i> Cari</button></div>
    <?php if (is_admin()): ?><div class="col text-end"><a href="?open=layanan_tambah" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Tambah Layanan</a></div><?php endif; ?>
  </form>
  <div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>No</th><th>Layanan</th><th>Deskripsi</th><th class="text-end">Harga</th><th>Estimasi</th><th>Status</th><?php if (is_admin()): ?><th class="text-end">Aksi</th><?php endif; ?></tr></thead><tbody>
    <?php $no = 0; foreach ($rows as $r): $no++; ?>
      <tr><td><?= $no ?></td><td class="fw-semibold"><?= e($r['nama_layanan']) ?></td><td><?= e($r['deskripsi'] ?: '-') ?></td>
        <td class="text-end"><?= rupiah($r['harga']) ?></td><td><?= (int)$r['estimasi_menit'] ?> menit</td><td><?= badge($r['status_aktif'] ? 'Aktif' : 'Nonaktif') ?></td>
        <?php if (is_admin()): ?><td class="text-end text-nowrap">
          <form method="post" action="?open=layanan_toggle&id=<?= $r['id_layanan'] ?>" class="d-inline"><?= csrf_field(false) ?>
            <button class="btn btn-sm btn-outline-secondary" title="<?= $r['status_aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?>"><i class="bi bi-toggle-<?= $r['status_aktif'] ? 'on' : 'off' ?>"></i></button></form>
          <a class="btn btn-sm btn-outline-primary" href="?open=layanan_edit&id=<?= $r['id_layanan'] ?>"><i class="bi bi-pencil"></i></a>
          <?= delete_form('layanan_hapus', $r['id_layanan'], 'layanan ' . $r['nama_layanan']) ?></td><?php endif; ?></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="7" class="text-center text-muted py-4">Belum ada layanan.</td></tr><?php endif; ?>
    </tbody></table></div>
</div></div>
