<?php
$id = (int)($_GET['id'] ?? 0);
$back = '?open=proses&status=' . urlencode($_GET['status'] ?? 'aktif');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check(false)) redirect($back);

$aksi = post('aksi'); $catatan = mb_substr(post('catatan'), 0, 255);
$alur = ['mulai' => ['Menunggu', 'Sedang Dicuci'], 'selesai' => ['Sedang Dicuci', 'Selesai'], 'diambil' => ['Selesai', 'Diambil']];
$t = db_one('SELECT status_transaksi, status_bayar FROM transaksi WHERE id_transaksi=?', 'i', [$id]);
if (!$t || !isset($alur[$aksi])) { flash_set('danger', 'Permintaan tidak valid.'); redirect($back); }
[$dari, $ke] = $alur[$aksi];
if ($t['status_transaksi'] !== $dari) { flash_set('warning', 'Status transaksi sudah berubah. Muat ulang halaman.'); redirect($back); }
if ($aksi === 'diambil' && $t['status_bayar'] !== 'Lunas') { flash_set('danger', 'Kendaraan baru dapat diserahkan setelah pembayaran Lunas.'); redirect($back); }

$now = date('Y-m-d H:i:s'); $mulai = null; $selesai = null;
if ($aksi === 'mulai') $mulai = $now;
if ($aksi === 'selesai') { $mulai = db_val("SELECT MAX(waktu_mulai) FROM proses_pencucian WHERE id_transaksi=? AND status_proses='Sedang Dicuci'", 'i', [$id]); $selesai = $now; }
if ($aksi === 'diambil') $selesai = $now;
try {
    $koneksi->begin_transaction();
    db_exec('INSERT INTO proses_pencucian (id_transaksi, id_user, waktu_mulai, waktu_selesai, status_proses, catatan) VALUES (?,?,?,?,?,?)', 'iissss', [$id, user()['id'], $mulai, $selesai, $ke, $catatan]);
    db_exec('UPDATE transaksi SET status_transaksi=? WHERE id_transaksi=?', 'si', [$ke, $id]);
    $koneksi->commit();
    flash_set('success', "Status berubah menjadi $ke.");
} catch (mysqli_sql_exception $ex) { $koneksi->rollback(); flash_set('danger', 'Gagal memperbarui proses.'); }
redirect($back);
