<?php
$id = (int)($_GET['id'] ?? 0);
if ($id === user()['id']) { flash_set('danger', 'Anda tidak dapat menghapus akun Anda sendiri.'); redirect('?open=user'); }
do_delete('users', 'id_user', $id, '?open=user', 'Pengguna');
