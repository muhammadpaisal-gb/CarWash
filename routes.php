<?php
// Peta menu: 'open' => [file di folder halaman/, hanya admin?]
return [
    'dashboard'         => ['dashboard.php', false],

    'pelanggan'         => ['data_pelanggan.php', false],
    'pelanggan_tambah'  => ['form_pelanggan.php', false],
    'pelanggan_edit'    => ['form_pelanggan.php', true],
    'pelanggan_detail'  => ['detail_pelanggan.php', false],
    'pelanggan_hapus'   => ['hapus_pelanggan.php', true],

    'kendaraan'         => ['data_kendaraan.php', false],
    'kendaraan_tambah'  => ['form_kendaraan.php', false],
    'kendaraan_edit'    => ['form_kendaraan.php', false],
    'kendaraan_hapus'   => ['hapus_kendaraan.php', false],

    'layanan'           => ['data_layanan.php', false],
    'layanan_tambah'    => ['form_layanan.php', true],
    'layanan_edit'      => ['form_layanan.php', true],
    'layanan_toggle'    => ['toggle_layanan.php', true],
    'layanan_hapus'     => ['hapus_layanan.php', true],

    'transaksi'         => ['data_transaksi.php', false],
    'transaksi_tambah'  => ['form_transaksi.php', false],
    'transaksi_edit'    => ['form_transaksi.php', false],
    'transaksi_detail'  => ['detail_transaksi.php', false],
    'transaksi_hapus'   => ['hapus_transaksi.php', true],

    'proses'            => ['proses_pencucian.php', false],
    'proses_aksi'       => ['proses_aksi.php', false],
    'proses_edit'       => ['form_proses.php', false],
    'proses_hapus'      => ['hapus_proses.php', true],

    'pembayaran'        => ['data_pembayaran.php', false],
    'pembayaran_tambah' => ['form_pembayaran.php', false],
    'pembayaran_edit'   => ['form_pembayaran.php', true],
    'pembayaran_hapus'  => ['hapus_pembayaran.php', true],

    'laporan_transaksi' => ['laporan_transaksi.php', true],
    'laporan_pendapatan'=> ['laporan_pendapatan.php', true],
    'riwayat'           => ['riwayat_pencucian.php', true],

    'user'              => ['data_user.php', true],
    'user_tambah'       => ['form_user.php', true],
    'user_edit'         => ['form_user.php', true],
    'user_hapus'        => ['hapus_user.php', true],
];
