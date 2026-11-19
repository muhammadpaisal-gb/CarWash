<?php do_delete('transaksi', 'id_transaksi', (int)($_GET['id'] ?? 0), '?open=transaksi', 'Transaksi (beserta detail, proses, dan pembayarannya)');
