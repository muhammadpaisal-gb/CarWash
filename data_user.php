<?php
$judul = 'Pengguna';
$q = trim($_GET['q'] ?? ''); $role = $_GET['role'] ?? '';
$where = ['1=1']; $types = ''; $params = [];
if ($q !== '') { $where[] = '(nama LIKE ? OR username LIKE ?)'; $like = "%$q%"; $types .= 'ss'; array_push($params, $like, $like); }
if (in_array($role, ['admin', 'petugas'], true)) { $where[] = 'role=?'; $types .= 's'; $params[] = $role; }
$rows = db_all('SELECT * FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY role, nama', $types, $params);
?>
<div class="card"><div class="card-body">
  <form class="row g-2 mb-3" method="get"><input type="hidden" name="open" value="user">
    <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Cari nama / username..."></div>
    <div class="col-md-3"><select name="role" class="form-select" onchange="this.form.submit()"><option value="">Semua role</option>
      <option value="admin"<?= $role === 'admin' ? ' selected' : '' ?>>Admin</option><option value="petugas"<?= $role === 'petugas' ? ' selected' : '' ?>>Petugas</option></select></div>
    <div class="col-auto"><button class="btn btn-outline-brand"><i class="bi bi-search"></i> Cari</button></div>
    <div class="col text-end"><a href="?open=user_tambah" class="btn btn-brand"><i class="bi bi-plus-lg"></i> Tambah Pengguna</a></div>
  </form>
  <div class="table-responsive"><table class="table table-hover"><thead><tr><th>No</th><th>Nama</th><th>Username</th><th>Role</th><th>Dibuat</th><th class="text-end">Aksi</th></tr></thead><tbody>
  <?php $no = 0; foreach ($rows as $r): $no++; $self = (int)$r['id_user'] === user()['id']; ?>
    <tr><td><?= $no ?></td><td class="fw-semibold"><?= e($r['nama']) ?><?= $self ? ' <small class="text-muted">(Anda)</small>' : '' ?></td><td><?= e($r['username']) ?></td><td><?= badge($r['role']) ?></td><td><?= tgl($r['created_at']) ?></td>
      <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-primary" href="?open=user_edit&id=<?= $r['id_user'] ?>"><i class="bi bi-pencil"></i></a>
      <?php if (!$self) echo delete_form('user_hapus', $r['id_user'], 'pengguna ' . $r['username']); ?></td></tr>
  <?php endforeach; ?></tbody></table></div>
</div></div>
