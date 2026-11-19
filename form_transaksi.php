<?php
$id = (int)($_GET['id'] ?? 0);
$judul = $id ? 'Edit Transaksi' : 'Tambah Transaksi';
$d = ['id_pelanggan' => (int)($_GET['pelanggan'] ?? 0), 'id_kendaraan' => 0, 'diskon' => 0, 'kode_transaksi' => ''];
$oldLines = [];
if ($id) {
    $row = db_one('SELECT * FROM transaksi WHERE id_transaksi=?', 'i', [$id]);
    if (!$row) { flash_set('danger', 'Transaksi tidak ditemukan.'); redirect('?open=transaksi'); }
    if ($row['status_transaksi'] !== 'Menunggu') { flash_set('warning', 'Transaksi hanya dapat diedit saat status masih Menunggu.'); redirect('?open=transaksi_detail&id=' . $id); }
    $d = ['id_pelanggan' => (int)$row['id_pelanggan'], 'id_kendaraan' => (int)$row['id_kendaraan'], 'diskon' => (int)$row['diskon'], 'kode_transaksi' => $row['kode_transaksi']];
    foreach (db_all('SELECT * FROM detail_transaksi WHERE id_transaksi=?', 'i', [$id]) as $l) $oldLines[(int)$l['id_layanan']] = ['harga' => (float)$l['harga_saat_transaksi'], 'jumlah' => (int)$l['jumlah']];
}
// Layanan yang bisa dipilih: yang aktif + yang sudah ada di transaksi ini
$layanan = db_all('SELECT * FROM layanan WHERE status_aktif=1 OR id_layanan IN (' . ($oldLines ? implode(',', array_keys($oldLines)) : '0') . ') ORDER BY nama_layanan');
$sel = [];
foreach ($oldLines as $lid => $l) $sel[$lid] = $l['jumlah'];

$err = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check()) {
    $d['id_pelanggan'] = (int)post('id_pelanggan');
    $d['id_kendaraan'] = (int)post('id_kendaraan');
    $diskonRaw = post('diskon') === '' ? '0' : post('diskon');
    $d['diskon'] = $diskonRaw;
    $pilih = $_POST['layanan'] ?? []; $qty = $_POST['qty'] ?? [];
    $sel = []; $lines = []; $subtotal = 0.0;

    if (!db_one('SELECT 1 FROM pelanggan WHERE id_pelanggan=?', 'i', [$d['id_pelanggan']])) $err[] = 'Pilih pelanggan.';
    elseif (!db_one('SELECT 1 FROM kendaraan WHERE id_kendaraan=? AND id_pelanggan=?', 'ii', [$d['id_kendaraan'], $d['id_pelanggan']])) $err[] = 'Pilih kendaraan milik pelanggan tersebut.';
    foreach ($layanan as $l) {
        $lid = (int)$l['id_layanan'];
        if (!is_array($pilih) || !isset($pilih[$lid])) continue;
        $j = (int)(is_array($qty) ? ($qty[$lid] ?? 1) : 1);
        if ($j < 1 || $j > 99) { $err[] = 'Jumlah layanan harus 1 - 99.'; $j = 1; }
        $harga = isset($oldLines[$lid]) ? $oldLines[$lid]['harga'] : (float)$l['harga'];   // harga snapshot
        $lines[] = ['id' => $lid, 'harga' => $harga, 'jumlah' => $j, 'sub' => $harga * $j];
        $sel[$lid] = $j; $subtotal += $harga * $j;
    }
    if (!$lines) $err[] = 'Pilih minimal satu layanan.';
    if (!ctype_digit($diskonRaw)) $err[] = 'Diskon harus berupa angka bulat (Rp).';
    elseif ((float)$diskonRaw > $subtotal) $err[] = 'Diskon tidak boleh melebihi subtotal.';

    if (!$err) {
        $diskon = (float)$diskonRaw; $total = $subtotal - $diskon;
        try {
            $koneksi->begin_transaction();
            if ($id) {
                db_exec('UPDATE transaksi SET id_pelanggan=?, id_kendaraan=?, subtotal=?, diskon=?, total_harga=? WHERE id_transaksi=?', 'iidddi',
                    [$d['id_pelanggan'], $d['id_kendaraan'], $subtotal, $diskon, $total, $id]);
                db_exec('DELETE FROM detail_transaksi WHERE id_transaksi=?', 'i', [$id]);
                $trxId = $id;
            } else {
                $thn = date('Y');
                $last = db_val('SELECT kode_transaksi FROM transaksi WHERE kode_transaksi LIKE ? ORDER BY id_transaksi DESC LIMIT 1 FOR UPDATE', 's', ["TRX-$thn-%"]);
                $n = $last ? ((int)substr($last, strrpos($last, '-') + 1)) + 1 : 1;
                $kode = sprintf('TRX-%s-%04d', $thn, $n);
                db_exec("INSERT INTO transaksi (kode_transaksi, id_pelanggan, id_kendaraan, id_user, tanggal_transaksi, subtotal, diskon, total_harga, status_transaksi, status_bayar)
                         VALUES (?,?,?,?,?,?,?,?,'Menunggu','Belum Bayar')", 'siiisddd',
                    [$kode, $d['id_pelanggan'], $d['id_kendaraan'], user()['id'], date('Y-m-d H:i:s'), $subtotal, $diskon, $total]);
                $trxId = last_id();
                db_exec("INSERT INTO proses_pencucian (id_transaksi, id_user, status_proses) VALUES (?,?, 'Menunggu')", 'ii', [$trxId, user()['id']]);
            }
            foreach ($lines as $l) {
                db_exec('INSERT INTO detail_transaksi (id_transaksi, id_layanan, harga_saat_transaksi, jumlah, subtotal) VALUES (?,?,?,?,?)', 'iidid',
                    [$trxId, $l['id'], $l['harga'], $l['jumlah'], $l['sub']]);
            }
            if ($id) recalc_pembayaran($trxId);
            $koneksi->commit();
            flash_set('success', $id ? 'Transaksi diperbarui.' : "Transaksi $kode berhasil dibuat dengan status Menunggu.");
            redirect('?open=transaksi_detail&id=' . $trxId);
        } catch (mysqli_sql_exception $ex) {
            $koneksi->rollback();
            $err[] = 'Gagal menyimpan transaksi. Silakan coba lagi.';
        }
    }
}
$pel = db_all('SELECT id_pelanggan, nama, no_telepon FROM pelanggan ORDER BY nama');
$kend = db_all('SELECT id_kendaraan, id_pelanggan, nomor_polisi, merek, tipe, warna FROM kendaraan ORDER BY nomor_polisi');
$kendJs = array_map(fn($k) => ['id' => (int)$k['id_kendaraan'], 'p' => (int)$k['id_pelanggan'], 'l' => $k['nomor_polisi'] . ' - ' . $k['merek'] . ' ' . $k['tipe'] . ' (' . $k['warna'] . ')'], $kend);
?>
<?php if ($err): ?><div class="alert alert-danger"><ul class="mb-0"><?php foreach ($err as $x) echo '<li>' . e($x) . '</li>'; ?></ul></div><?php endif; ?>
<form method="post" data-once><?= csrf_field() ?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card mb-3"><div class="card-header">1. Pelanggan &amp; Kendaraan <?= $d['kode_transaksi'] ? '<span class="text-muted">(' . e($d['kode_transaksi']) . ')</span>' : '' ?></div><div class="card-body row g-3">
      <div class="col-12"><label class="form-label">Pelanggan</label>
        <select name="id_pelanggan" id="selPel" class="form-select" required><option value="">-- Pilih pelanggan --</option>
        <?php foreach ($pel as $p): ?><option value="<?= $p['id_pelanggan'] ?>"<?= (int)$d['id_pelanggan'] === (int)$p['id_pelanggan'] ? ' selected' : '' ?>><?= e($p['nama'] . ' - ' . $p['no_telepon']) ?></option><?php endforeach; ?></select>
        <div class="form-text">Belum terdaftar? <a href="?open=pelanggan_tambah">Tambah pelanggan baru</a>.</div></div>
      <div class="col-12"><label class="form-label">Kendaraan</label>
        <select name="id_kendaraan" id="selKend" class="form-select" required></select>
        <div class="form-text" id="hintKend"></div></div>
    </div></div>
    <div class="card"><div class="card-header">2. Pilih Layanan</div><div class="card-body">
      <?php if (!$layanan): ?><div class="text-muted">Belum ada layanan aktif.</div><?php endif; ?>
      <div class="row g-2">
      <?php foreach ($layanan as $l): $lid = (int)$l['id_layanan']; $harga = $oldLines[$lid]['harga'] ?? (float)$l['harga']; $on = isset($sel[$lid]); ?>
        <div class="col-md-6"><div class="svc-item<?= $on ? ' on' : '' ?>">
          <div class="form-check">
            <input class="form-check-input svc-cb" type="checkbox" name="layanan[<?= $lid ?>]" value="1" id="l<?= $lid ?>" data-harga="<?= (int)$harga ?>"<?= $on ? ' checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="l<?= $lid ?>"><?= e($l['nama_layanan']) ?><?= $l['status_aktif'] ? '' : ' <small class="text-muted">(nonaktif)</small>' ?></label>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-1">
            <small class="text-muted"><?= rupiah($harga) ?> &middot; <?= (int)$l['estimasi_menit'] ?> mnt</small>
            <input type="number" min="1" max="99" name="qty[<?= $lid ?>]" value="<?= (int)($sel[$lid] ?? 1) ?>" class="form-control form-control-sm svc-qty" style="width:70px" title="Jumlah">
          </div></div></div>
      <?php endforeach; ?>
      </div>
    </div></div>
  </div>
  <div class="col-lg-5">
    <div class="card position-sticky" style="top:80px"><div class="card-header">3. Ringkasan</div><div class="card-body">
      <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong id="tSub">Rp 0</strong></div>
      <div class="mb-2"><label class="form-label">Diskon (Rp)</label><input type="number" min="0" step="1" name="diskon" id="inDiskon" class="form-control" value="<?= e($d['diskon']) ?>"></div>
      <hr><div class="d-flex justify-content-between fs-5"><span>Total</span><strong id="tTotal" class="text-success">Rp 0</strong></div>
      <p class="small text-muted mt-2 mb-3">Harga dihitung ulang oleh server dari data layanan, dan disimpan sebagai harga saat transaksi.</p>
      <button type="submit" class="btn btn-brand w-100"><i class="bi bi-save"></i> Simpan Transaksi</button>
      <a href="?open=transaksi" class="btn btn-light w-100 mt-2">Batal</a>
    </div></div>
  </div>
</div>
</form>
<script>
(function () {
  var kend = <?= json_encode($kendJs) ?>, selK = <?= (int)$d['id_kendaraan'] ?>;
  var selPel = document.getElementById('selPel'), selKend = document.getElementById('selKend'), hint = document.getElementById('hintKend');
  function fillKend() {
    var p = parseInt(selPel.value || 0, 10), h = '<option value="">-- Pilih kendaraan --</option>', n = 0;
    kend.forEach(function (k) { if (k.p === p) { n++; h += '<option value="' + k.id + '"' + (k.id === selK ? ' selected' : '') + '>' + k.l.replace(/</g, '&lt;') + '</option>'; } });
    selKend.innerHTML = h;
    hint.innerHTML = !p ? 'Pilih pelanggan terlebih dahulu.' : (n ? '' : 'Pelanggan ini belum punya kendaraan. <a href="?open=kendaraan_tambah&pelanggan=' + p + '&kembali=transaksi">Tambah kendaraan</a>.');
  }
  selPel.addEventListener('change', function () { selK = 0; fillKend(); });
  fillKend();
  var fmt = function (n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); };
  function hitung() {
    var sub = 0;
    document.querySelectorAll('.svc-cb').forEach(function (cb) {
      var item = cb.closest('.svc-item'), q = parseInt(item.querySelector('.svc-qty').value || 1, 10) || 1;
      item.classList.toggle('on', cb.checked);
      if (cb.checked) sub += parseFloat(cb.dataset.harga) * q;
    });
    var dk = parseFloat(document.getElementById('inDiskon').value || 0) || 0;
    document.getElementById('tSub').textContent = fmt(sub);
    document.getElementById('tTotal').textContent = fmt(Math.max(0, sub - dk));
  }
  document.querySelectorAll('.svc-cb,.svc-qty,#inDiskon').forEach(function (el) { el.addEventListener('input', hitung); el.addEventListener('change', hitung); });
  hitung();
})();
</script>
