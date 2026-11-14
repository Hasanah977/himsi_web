<?php

return [
    // Nominal iuran bulanan sesuai rancangan KBH, bukan berasal dari input anggota.
    'nominal_per_bulan' => (int) env('HIMSI_IURAN_NOMINAL', 5000),
    // Isi dengan rekening asli Bendahara; jangan memasukkan rekening contoh.
    'qr_url' => env('HIMSI_IURAN_QR_URL'),
    'nama_bank' => env('HIMSI_IURAN_BANK'),
    'nomor_rekening' => env('HIMSI_IURAN_REKENING'),
    'nama_pemilik' => env('HIMSI_IURAN_ATAS_NAMA'),
];
