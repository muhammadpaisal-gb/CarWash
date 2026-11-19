<?php
$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check(false)) {
    $trx = db_val('SELECT id_transaksi FROM pembayaran WHERE id_pembayaran=?', 'i', [$id]);
    if ($trx) {
        db_exec('DELETE FROM pembayaran WHERE id_pembayaran=?', 'i', [$id]);
        recalc_pembayaran((int)$trx);
        flash_set('success', 'Pembayaran dihapus dan status pembayaran transaksi dihitung ulang.');
    }
}
redirect('?open=pembayaran');
