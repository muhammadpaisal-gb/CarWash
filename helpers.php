<?php
// Fungsi bantu: session, keamanan (CSRF), database (prepared statement), format, validasi.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
date_default_timezone_set('Asia/Pontianak');
require_once __DIR__ . '/koneksi.php';

/* ---------- Output & format ---------- */
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function tgl($d, $jam = false) {
    if (!$d) return '-';
    return date($jam ? 'd/m/Y H:i' : 'd/m/Y', strtotime($d));
}
function redirect($url) { header('Location: ' . $url); exit; }
function post($k, $d = '') { return trim((string)($_POST[$k] ?? $d)); }

function flash_set($type, $msg) { $_SESSION['flash'][] = ['t' => $type, 'm' => $msg]; }
function flash_get() { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function badge($s) {
    $map = ['Menunggu' => 'secondary', 'Sedang Dicuci' => 'info', 'Selesai' => 'success', 'Diambil' => 'dark',
            'Belum Bayar' => 'danger', 'Lunas' => 'success', 'Sebagian' => 'warning',
            'admin' => 'primary', 'petugas' => 'secondary', 'Aktif' => 'success', 'Nonaktif' => 'secondary'];
    return '<span class="badge text-bg-' . ($map[$s] ?? 'light') . '">' . e($s) . '</span>';
}

/* ---------- Autentikasi & hak akses ---------- */
function user() { return $_SESSION['user'] ?? null; }
function is_admin() { return (user()['role'] ?? '') === 'admin'; }
function login_user(array $u) {
    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int)$u['id_user'], 'nama' => $u['nama'], 'username' => $u['username'], 'role' => $u['role']];
}
function require_login() { if (!user()) redirect('index.php'); }
function require_admin() {
    if (!is_admin()) {
        flash_set('danger', 'Anda tidak memiliki hak akses ke halaman tersebut.');
        redirect('dashboard.php');
    }
}

/* ---------- CSRF + pencegah kirim ganda ---------- */
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
// $once=true -> tambahkan nonce sekali pakai (mencegah submit ganda pada form tambah/ubah)
function csrf_field($once = true) {
    $h = '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    if ($once) {
        $n = bin2hex(random_bytes(16));
        $_SESSION['nonces'][$n] = time();
        if (count($_SESSION['nonces']) > 200) array_shift($_SESSION['nonces']);
        $h .= '<input type="hidden" name="_once" value="' . $n . '">';
    }
    return $h;
}
function csrf_check($once = true) {
    if (!isset($_POST['_csrf']) || !hash_equals(csrf_token(), (string)$_POST['_csrf'])) {
        flash_set('danger', 'Token keamanan tidak valid. Silakan ulangi.');
        return false;
    }
    if ($once) {
        $n = (string)($_POST['_once'] ?? '');
        if (!isset($_SESSION['nonces'][$n])) {
            flash_set('warning', 'Formulir sudah pernah dikirim atau kedaluwarsa. Data tidak disimpan ganda.');
            return false;
        }
        unset($_SESSION['nonces'][$n]);
    }
    return true;
}

/* ---------- Database (selalu prepared statement) ---------- */
function db_run($sql, $types = '', $params = []) {
    global $koneksi;
    $st = $koneksi->prepare($sql);
    if ($types !== '') $st->bind_param($types, ...$params);
    $st->execute();
    return $st;
}
function db_all($sql, $types = '', $params = []) {
    $st = db_run($sql, $types, $params);
    $rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);
    $st->close();
    return $rows;
}
function db_one($sql, $types = '', $params = []) { $r = db_all($sql, $types, $params); return $r[0] ?? null; }
function db_val($sql, $types = '', $params = []) { $r = db_one($sql, $types, $params); return $r ? array_values($r)[0] : null; }
function db_exec($sql, $types = '', $params = []) {
    $st = db_run($sql, $types, $params);
    $n = $st->affected_rows;
    $st->close();
    return $n;
}
function last_id() { global $koneksi; return (int)$koneksi->insert_id; }

// Hapus satu baris dengan aman (POST + CSRF + penanganan foreign key)
function do_delete($table, $pk, $id, $back, $label) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_check(false)) redirect($back);
    try {
        $n = db_exec("DELETE FROM `$table` WHERE `$pk` = ?", 'i', [$id]);
        flash_set($n ? 'success' : 'warning', $n ? "$label berhasil dihapus." : "$label tidak ditemukan.");
    } catch (mysqli_sql_exception $ex) {
        if ((int)$ex->getCode() === 1451) {
            flash_set('danger', "$label tidak dapat dihapus karena masih digunakan oleh data lain (foreign key).");
        } else {
            flash_set('danger', 'Gagal menghapus data.');
        }
    }
    redirect($back);
}
function delete_form($open, $id, $label, $extra = '') {
    return '<form method="post" action="?open=' . e($open) . '&id=' . (int)$id . $extra . '" class="d-inline" onsubmit="return confirm(\'Yakin menghapus ' . e($label) . '?\')">'
         . csrf_field(false) . '<button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button></form>';
}

/* ---------- Pagination & query string ---------- */
function qs(array $o = []) {
    $p = array_merge($_GET, $o);
    foreach ($p as $k => $v) if ($v === null || $v === '') unset($p[$k]);
    return '?' . http_build_query($p);
}
function page_num() { return max(1, (int)($_GET['page'] ?? 1)); }
function pager($total, $per, $page) {
    $pages = max(1, (int)ceil($total / $per));
    if ($pages <= 1) return '';
    $h = '<nav><ul class="pagination pagination-sm mb-0 justify-content-end">';
    $item = function ($p, $label, $active = false, $disabled = false) {
        return '<li class="page-item' . ($active ? ' active' : '') . ($disabled ? ' disabled' : '') . '"><a class="page-link" href="' . e(qs(['page' => $p])) . '">' . $label . '</a></li>';
    };
    $h .= $item($page - 1, '&laquo;', false, $page <= 1);
    for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++) $h .= $item($i, $i, $i === $page);
    $h .= $item($page + 1, '&raquo;', false, $page >= $pages);
    return $h . '</ul></nav>';
}

/* ---------- Validasi ---------- */
function valid_phone($s) { return (bool)preg_match('/^(\+?62|0)[0-9]{8,13}$/', $s); }
function normalisasi_plat($s) {
    $s = strtoupper(trim($s));
    if (preg_match('/^([A-Z]{1,2})\s*([0-9]{1,4})\s*([A-Z]{0,3})$/', $s, $m)) return trim($m[1] . ' ' . $m[2] . ' ' . $m[3]);
    return null;
}
function valid_date($s) { $d = DateTime::createFromFormat('Y-m-d', $s); return $d && $d->format('Y-m-d') === $s; }

/* ---------- Pembayaran & status ---------- */
// Hitung ulang status bayar transaksi dari seluruh pembayaran (urut kronologis)
function recalc_pembayaran($id_trx) {
    $t = db_one('SELECT total_harga FROM transaksi WHERE id_transaksi=?', 'i', [$id_trx]);
    if (!$t) return;
    $total = (float)$t['total_harga'];
    $cum = 0.0;
    foreach (db_all('SELECT id_pembayaran, jumlah_dibayar, kembalian FROM pembayaran WHERE id_transaksi=? ORDER BY tanggal_pembayaran, id_pembayaran', 'i', [$id_trx]) as $p) {
        $cum += (float)$p['jumlah_dibayar'] - (float)$p['kembalian'];
        $st = ($cum + 0.001 >= $total) ? 'Lunas' : 'Sebagian';
        db_exec('UPDATE pembayaran SET status_pembayaran=? WHERE id_pembayaran=?', 'si', [$st, $p['id_pembayaran']]);
    }
    $lunas = ($cum + 0.001 >= $total) && $cum > 0;
    db_exec('UPDATE transaksi SET status_bayar=? WHERE id_transaksi=?', 'si', [$lunas ? 'Lunas' : 'Belum Bayar', $id_trx]);
}
function total_terbayar($id_trx, $exclude = 0) {
    return (float)db_val('SELECT COALESCE(SUM(jumlah_dibayar - kembalian),0) FROM pembayaran WHERE id_transaksi=? AND id_pembayaran<>?', 'ii', [$id_trx, $exclude]);
}

/* ---------- Ekspor Excel (tabel HTML .xls) ---------- */
function export_xls($file, $title, array $heads, array $rows) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $file . '_' . date('Ymd_His') . '.xls"');
    echo "\xEF\xBB\xBF<html><head><meta charset=\"utf-8\"></head><body><h3>" . e($title) . "</h3><table border=\"1\"><tr>";
    foreach ($heads as $h) echo '<th>' . e($h) . '</th>';
    echo '</tr>';
    foreach ($rows as $r) { echo '<tr>'; foreach ($r as $c) echo '<td>' . e($c) . '</td>'; echo '</tr>'; }
    echo '</table></body></html>';
    exit;
}

// Rentang tanggal dari filter: dari/sampai, atau bulan+tahun, atau tahun; default bulan berjalan
function rentang_tanggal() {
    $dari = $_GET['dari'] ?? ''; $sampai = $_GET['sampai'] ?? '';
    $bulan = (int)($_GET['bulan'] ?? 0); $tahun = (int)($_GET['tahun'] ?? 0);
    if (valid_date($dari) || valid_date($sampai)) {
        if (!valid_date($dari)) $dari = $sampai;
        if (!valid_date($sampai)) $sampai = $dari;
    } elseif ($tahun >= 2000 && $tahun <= 2100) {
        if ($bulan >= 1 && $bulan <= 12) {
            $dari = sprintf('%04d-%02d-01', $tahun, $bulan);
            $sampai = date('Y-m-t', strtotime($dari));
        } else { $dari = "$tahun-01-01"; $sampai = "$tahun-12-31"; }
    } else { $dari = date('Y-m-01'); $sampai = date('Y-m-t'); }
    if ($dari > $sampai) { $t = $dari; $dari = $sampai; $sampai = $t; }
    return [$dari, $sampai];
}
function filter_tanggal_form() {
    $b = (int)($_GET['bulan'] ?? 0);
    $nb = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $o = '<div class="col-6 col-md-2"><label class="form-label small">Dari</label><input type="date" name="dari" class="form-control form-control-sm" value="' . e($_GET['dari'] ?? '') . '"></div>';
    $o .= '<div class="col-6 col-md-2"><label class="form-label small">Sampai</label><input type="date" name="sampai" class="form-control form-control-sm" value="' . e($_GET['sampai'] ?? '') . '"></div>';
    $o .= '<div class="col-6 col-md-2"><label class="form-label small">Bulan</label><select name="bulan" class="form-select form-select-sm"><option value="">Semua</option>';
    for ($i = 1; $i <= 12; $i++) $o .= '<option value="' . $i . '"' . ($b === $i ? ' selected' : '') . '>' . $nb[$i] . '</option>';
    $o .= '</select></div><div class="col-6 col-md-2"><label class="form-label small">Tahun</label><input type="number" name="tahun" min="2000" max="2100" class="form-control form-control-sm" placeholder="' . date('Y') . '" value="' . e($_GET['tahun'] ?? '') . '"></div>';
    return $o;
}
