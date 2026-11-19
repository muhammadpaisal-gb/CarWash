<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_check(false)) {
    db_exec('UPDATE layanan SET status_aktif = 1 - status_aktif WHERE id_layanan=?', 'i', [(int)($_GET['id'] ?? 0)]);
    flash_set('success', 'Status layanan diubah.');
}
redirect('?open=layanan');
