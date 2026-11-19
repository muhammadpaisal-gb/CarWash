<?php
$judul = 'Data Pelanggan';
$q = trim($_GET['q'] ?? ''); $urut = $_GET['urut'] ?? 'terbaru';
$per = 10; $page = page_num();
$where = '1=1'; $types = ''; $params = [];
if ($q !== '') { $where = '(p.nama LIKE ? OR p.no_telepon LIKE ?)'; $like = "%$q%"; $types = 'ss'; $params = [$like, $like]; }
$order = $urut === 'nama' ? 'p.nama ASC' : 'p.id_pelanggan DESC';
$total = (int)db_val("SELECT COUNT(*) FROM pelanggan p WHERE $where", $types, $params);
$rows = db_all("SELECT p.*,
   (SELECT COUNT(*) FROM kendaraan k WHERE k.id_pelanggan=p.id_pelanggan) jml_kendaraan,
   (SELECT COUNT(*) FROM transaksi t WHERE t.id_pelanggan=p.id_pelanggan) jml_transaksi
   FROM pelanggan p WHERE $where ORDER BY $order LIMIT $per OFFSET " . (($page - 1) * $per), $types, $params);
?>
<div class="card">
  <div class="card-body">
    <form class="row g-2 mb-3" method="get">
      <input type="hidden" name="open" value="pelanggan">
      <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama / no. telepon..."></div>
      <div class="col-md-3"><select name="urut" class="form-select" onchange="this.form.submit()">
        <option value="terbaru"<?= $urut !== 'nama' ? ' selected' : '' ?>>Urut: Terbaru</option>
        <option value="nama"<?= $urut === 'nama' ? ' selected' : '' ?>>Urut: Nama A-Z</option></select></div>
      <div class="col-auto"><button class="btn btn-outline-brand"><i class="bi bi-search"></i> Cari</button></div>
      <div class="col text-end"><a href="?open=pelanggan_tambah" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Tambah Pelanggan</a></div>
    </form>
    <div class="table-responsive"><table class="table table-hover">
      <thead><tr><th>No</th><th>Nama</th><th>No. Telepon</th><th>Alamat</th><th>Registrasi</th><th>Kendaraan</th><th>Transaksi</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php $no = ($page - 1) * $per; foreach ($rows as $r): $no++; ?>
        <tr>
          <td><?= $no ?></td><td class="fw-semibold"><?= e($r['nama']) ?></td><td><?= e($r['no_telepon']) ?></td>
          <td><?= e($r['alamat'] ?: '-') ?></td><td><?= tgl($r['tanggal_registrasi']) ?></td>
          <td><?= (int)$r['jml_kendaraan'] ?></td><td><?= (int)$r['jml_transaksi'] ?></td>
          <td class="text-end text-nowrap">
            <a class="btn btn-sm btn-outline-secondary" title="Detail" href="?open=pelanggan_detail&id=<?= $r['id_pelanggan'] ?>"><i class="bi bi-eye"></i></a>
            <?php if (is_admin()): ?>
            <a class="btn btn-sm btn-outline-primary" title="Edit" href="?open=pelanggan_edit&id=<?= $r['id_pelanggan'] ?>"><i class="bi bi-pencil"></i></a>
            <?= delete_form('pelanggan_hapus', $r['id_pelanggan'], 'pelanggan ' . $r['nama']) ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">Data tidak ditemukan.</td></tr><?php endif; ?>
      </tbody>
    </table></div>
    <div class="d-flex justify-content-between align-items-center"><small class="text-muted"><?= $total ?> data</small><?= pager($total, $per, $page) ?></div>
  </div>
</div>
