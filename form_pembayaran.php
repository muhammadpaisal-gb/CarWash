<?php
$id = (int)($_GET['id'] ?? 0);
$judul = $id ? 'Edit Pembayaran' : 'Catat Pembayaran';
$d = ['id_transaksi' => (int)($_GET['transaksi'] ?? 0), 'metode_pembayaran' => 'Tunai', 'jumlah_dibayar' => ''];
if ($id) {
    $row = db_one('SELECT * FROM pembayaran WHERE id_pembayaran=?', 'i', [$id]);
    if (!$row) { flash_set('danger', 'Pembayaran tidak ditemukan.'); redirect('?open=pembayaran'); }
    $d = ['id_transaksi' => (int)$row['id_transaksi'], 'metode_pembayaran' => $row['metode_pembayaran'], 'jumlah_dibayar' => (int)$row['jumlah_dibayar']];
}
$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    if (!$id) $d['id_transaksi'] = (int)post('id_transaksi');
    $d['metode_pembayaran'] = post('metode_pembayaran'); $d['jumlah_dibayar'] = post('jumlah_dibayar');
    if (!in_array($d['metode_pembayaran'], ['Tunai', 'Transfer', 'QRIS'], true)) $err[] = 'Pilih metode pembayaran.';
    if (!ctype_digit((string)$d['jumlah_dibayar']) || (int)$d['jumlah_dibayar'] <= 0) $err[] = 'Jumlah dibayar harus berupa angka lebih dari 0.';
    if (!$err) {
        try {
            $koneksi->begin_transaction();
            $t = db_one('SELECT id_transaksi, total_harga FROM transaksi WHERE id_transaksi=? FOR UPDATE', 'i', [$d['id_transaksi']]);
            if (!$t) throw new RuntimeException('Pilih transaksi yang valid.');
            $sisa = (float)$t['total_harga'] - total_terbayar($d['id_transaksi'], $id);
            $jumlah = (float)$d['jumlah_dibayar'];
            if ($sisa <= 0) throw new RuntimeException('Transaksi ini sudah lunas.');
            if ($d['metode_pembayaran'] === 'Tunai') $kembali = max(0, $jumlah - $sisa);
            else { if ($jumlah > $sisa) throw new RuntimeException('Pembayaran non-tunai tidak boleh melebihi sisa tagihan (' . rupiah($sisa) . ').'); $kembali = 0; }
            if ($id) {
                db_exec('UPDATE pembayaran SET metode_pembayaran=?, jumlah_dibayar=?, kembalian=? WHERE id_pembayaran=?', 'sddi', [$d['metode_pembayaran'], $jumlah, $kembali, $id]);
            } else {
                db_exec("INSERT INTO pembayaran (id_transaksi, tanggal_pembayaran, metode_pembayaran, jumlah_dibayar, kembalian, status_pembayaran) VALUES (?,?,?,?,?,'Sebagian')", 'issdd',
                    [$d['id_transaksi'], date('Y-m-d H:i:s'), $d['metode_pembayaran'], $jumlah, $kembali]);
            }
            recalc_pembayaran($d['id_transaksi']);
            $koneksi->commit();
            $lunas = db_val('SELECT status_bayar FROM transaksi WHERE id_transaksi=?', 'i', [$d['id_transaksi']]) === 'Lunas';
            flash_set('success', 'Pembayaran disimpan.' . ($kembali > 0 ? ' Kembalian: ' . rupiah($kembali) . '.' : '') . ($lunas ? ' Transaksi LUNAS.' : ' Masih ada sisa tagihan.'));
            redirect('?open=transaksi_detail&id=' . $d['id_transaksi']);
        } catch (mysqli_sql_exception $ex) { $koneksi->rollback(); $err[] = 'Gagal menyimpan pembayaran.'; }
        catch (RuntimeException $ex) { $koneksi->rollback(); $err[] = $ex->getMessage(); }
    }
}
// daftar transaksi: yang belum lunas (+ transaksi yang sedang diedit / dipilih)
$trx = db_all("SELECT t.id_transaksi, t.kode_transaksi, t.total_harga, p.nama, k.nomor_polisi,
   (SELECT COALESCE(SUM(jumlah_dibayar-kembalian),0) FROM pembayaran b WHERE b.id_transaksi=t.id_transaksi AND b.id_pembayaran<>?) terbayar
   FROM transaksi t JOIN pelanggan p ON p.id_pelanggan=t.id_pelanggan JOIN kendaraan k ON k.id_kendaraan=t.id_kendaraan
   WHERE t.status_bayar='Belum Bayar' OR t.id_transaksi=? ORDER BY t.id_transaksi DESC LIMIT 200", 'ii', [$id, $d['id_transaksi']]);
?>
<div class="card" style="max-width:640px"><div class="card-header"><?= e($judul) ?></div><div class="card-body">
  <?php if ($err): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($err as $x) echo '<li>' . e($x) . '</li>'; ?></ul></div><?php endif; ?>
  <form method="post" data-once class="row g-3"><?= csrf_field() ?>
    <div class="col-12"><label class="form-label">Transaksi</label>
      <select name="id_transaksi" id="selTrx" class="form-select" required <?= $id ? 'disabled' : '' ?>><option value="">-- Pilih transaksi belum lunas --</option>
      <?php foreach ($trx as $t): ?><option value="<?= $t['id_transaksi'] ?>" data-total="<?= (float)$t['total_harga'] ?>" data-paid="<?= (float)$t['terbayar'] ?>"<?= (int)$d['id_transaksi'] === (int)$t['id_transaksi'] ? ' selected' : '' ?>><?= e($t['kode_transaksi'] . ' - ' . $t['nomor_polisi'] . ' - ' . $t['nama']) ?></option><?php endforeach; ?></select>
      <?php if ($id): ?><input type="hidden" name="id_transaksi" value="<?= (int)$d['id_transaksi'] ?>"><?php endif; ?></div>
    <div class="col-12"><div class="p-3 rounded bg-light">
      <div class="d-flex justify-content-between"><span>Total tagihan</span><strong id="vTotal">-</strong></div>
      <div class="d-flex justify-content-between"><span>Sudah dibayar</span><strong id="vPaid">-</strong></div>
      <div class="d-flex justify-content-between"><span>Sisa tagihan</span><strong id="vSisa" class="text-danger">-</strong></div></div></div>
    <div class="col-md-6"><label class="form-label">Metode Pembayaran</label><select name="metode_pembayaran" id="selMetode" class="form-select">
      <?php foreach (['Tunai', 'Transfer', 'QRIS'] as $m) echo '<option' . ($d['metode_pembayaran'] === $m ? ' selected' : '') . '>' . $m . '</option>'; ?></select></div>
    <div class="col-md-6"><label class="form-label">Jumlah Dibayar (Rp)</label><input type="number" min="1" step="1" name="jumlah_dibayar" id="inBayar" class="form-control" value="<?= e($d['jumlah_dibayar']) ?>" required>
      <button type="button" class="btn btn-link btn-sm p-0" id="btnPas">Isi sesuai sisa tagihan</button></div>
    <div class="col-12"><div class="alert alert-info mb-0 py-2" id="boxKembali">Kembalian: <strong id="vKembali">Rp 0</strong></div></div>
    <div class="col-12"><button type="submit" class="btn btn-brand"><i class="bi bi-save"></i> Simpan Pembayaran</button> <a href="?open=pembayaran" class="btn btn-light">Batal</a></div>
  </form></div></div>
<script>
(function () {
  var sel = document.getElementById('selTrx'), metode = document.getElementById('selMetode'), inp = document.getElementById('inBayar');
  var fmt = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }, sisa = 0;
  function upd() {
    var o = sel.options[sel.selectedIndex];
    if (!o || !o.dataset.total) { sisa = 0; document.getElementById('vTotal').textContent = document.getElementById('vPaid').textContent = document.getElementById('vSisa').textContent = '-'; }
    else {
      var t = parseFloat(o.dataset.total), p = parseFloat(o.dataset.paid); sisa = Math.max(0, t - p);
      document.getElementById('vTotal').textContent = fmt(t); document.getElementById('vPaid').textContent = fmt(p); document.getElementById('vSisa').textContent = fmt(sisa);
    }
    var tunai = metode.value === 'Tunai', b = parseFloat(inp.value || 0) || 0;
    document.getElementById('boxKembali').style.display = tunai ? '' : 'none';
    document.getElementById('vKembali').textContent = fmt(Math.max(0, b - sisa));
  }
  [sel, metode, inp].forEach(function (el) { el.addEventListener('input', upd); el.addEventListener('change', upd); });
  document.getElementById('btnPas').addEventListener('click', function () { inp.value = Math.round(sisa); upd(); });
  upd();
})();
</script>
