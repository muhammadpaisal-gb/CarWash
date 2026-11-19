<?php
$judul = 'Data Kendaraan';
$q = trim($_GET['q'] ?? ''); $jenis = $_GET['jenis'] ?? '';
$per = 10; $page = page_num();
$where = ['1=1']; $types = ''; $params = [];
if ($q !== '') { $where[] = '(k.nomor_polisi LIKE ? OR p.nama LIKE ? OR k.merek LIKE ?)'; $like = '%' . $q . '%'; $types .= 'sss'; array_push($params, $like, $like, $like); }
if (in_array($jenis, ['Mobil', 'SUV', 'MPV', 'Pickup', 'Truk'], true)) { $where[] = 'k.jenis_kendaraan=?'; $types .= 's'; $params[] = $jenis; }
$w = implode(' AND ', $where);
$total = (int)db_val("SELECT COUNT(*) FROM kendaraan k JOIN pelanggan p ON p.id_pelanggan=k.id_pelanggan WHERE $w", $types, $params);
$rows = db_all("SELECT k.*, p.nama pemilik FROM kendaraan k JOIN pelanggan p ON p.id_pelanggan=k.id_pelanggan WHERE $w ORDER BY k.id_kendaraan DESC LIMIT $per OFFSET " . (($page - 1) * $per), $types, $params);
?>
<div class="card"><div class="card-body">
  <form class="row g-2 mb-3" method="get">
    <input type="hidden" name="open" value="kendaraan">
    <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nomor polisi / pemilik / merek..."></div>
    <div class="col-md-3"><select name="jenis" class="form-select" onchange="this.form.submit()"><option value="">Semua jenis</option>
      <?php foreach (['Mobil', 'SUV', 'MPV', 'Pickup', 'Truk'] as $j) echo '<option' . ($jenis === $j ? ' selected' : '') . '>' . $j . '</option>'; ?></select></div>
    <div class="col-auto"><button class="btn btn-outline-brand"><i class="bi bi-search"></i> Cari</button></div>
    <div class="col text-end"><a href="?open=kendaraan_tambah" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Tambah Kendaraan</a></div>
  </form>
  <div class="table-responsive"><table class="table table-hover">
    <thead><tr><th>No</th><th>Nomor Polisi</th><th>Pemilik</th><th>Merek</th><th>Tipe</th><th>Warna</th><th>Jenis</th><th class="text-end">Aksi</th></tr></thead><tbody>
    <?php $no = ($page - 1) * $per; foreach ($rows as $r): $no++; ?>
      <tr><td><?= $no ?></td><td class="fw-bold"><?= e($r['nomor_polisi']) ?></td>
        <td><a class="text-decoration-none" href="?open=pelanggan_detail&id=<?= $r['id_pelanggan'] ?>"><?= e($r['pemilik']) ?></a></td>
        <td><?= e($r['merek']) ?></td><td><?= e($r['tipe']) ?></td><td><?= e($r['warna']) ?></td><td><?= e($r['jenis_kendaraan']) ?></td>
        <td class="text-end text-nowrap">
          <a class="btn btn-sm btn-outline-primary" title="Edit" href="?open=kendaraan_edit&id=<?= $r['id_kendaraan'] ?>"><i class="bi bi-pencil"></i></a>
          <?= delete_form('kendaraan_hapus', $r['id_kendaraan'], 'kendaraan ' . $r['nomor_polisi']) ?></td></tr>
    <?php endforeach; if (!$rows): ?><tr><td colspan="8" class="text-center text-muted py-4">Data tidak ditemukan.</td></tr><?php endif; ?>
    </tbody></table></div>
  <div class="d-flex justify-content-between align-items-center"><small class="text-muted"><?= $total ?> data</small><?= pager($total, $per, $page) ?></div>
</div></div>
