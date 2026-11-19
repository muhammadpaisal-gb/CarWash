<?php
$id = (int)($_GET['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check(false)) {
    $trx = db_val('SELECT id_transaksi FROM proses_pencucian WHERE id_proses=?', 'i', [$id]);
    if ($trx) {
        db_exec('DELETE FROM proses_pencucian WHERE id_proses=?', 'i', [$id]);
        // sinkronkan status transaksi dengan log terakhir yang tersisa
        $last = db_val('SELECT status_proses FROM proses_pencucian WHERE id_transaksi=? ORDER BY id_proses DESC LIMIT 1', 'i', [$trx]) ?: 'Menunggu';
        db_exec('UPDATE transaksi SET status_transaksi=? WHERE id_transaksi=?', 'si', [$last, $trx]);
        flash_set('success', 'Log proses dihapus. Status transaksi disesuaikan menjadi ' . $last . '.');
    }
}
redirect('?open=proses');
