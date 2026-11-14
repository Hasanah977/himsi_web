<?php

return [
    'menus' => [
        'kbh' => [
            0 => [
                0 => 'Dashboard',
                1 => 'kbh.dashboard',
                2 => 'grid',
            ],
            4 => [
                0 => 'Voting',
                1 => 'kbh.voting.hasil',
                2 => 'bar-chart',
            ],
            5 => [
                0 => 'Seleksi Pengurus',
                1 => 'kbh.seleksi.index',
                2 => 'people',
            ],
            6 => [
                0 => 'Iuran',
                1 => 'kbh.iuran.index',
                2 => 'wallet2',
            ],
            7 => [
                0 => 'Aspirasi',
                1 => 'kbh.aspirasi.index',
                2 => 'chat-dots',
            ],
            8 => [
                0 => 'Forum KBH',
                1 => 'kbh.forum',
                2 => 'chat-dots',
            ],
            'archive' => ['Arsip', 'member.archive', 'archive'],
        ],
        'ketua_umum' => [
            'lpj' => ['LPJ', 'officer-reports.index', 'file-earmark-text'],
            'recruitment' => ['Open Rekrutmen', 'recruitment.index', 'person-plus'],
            'pprk' => ['Pengajuan RPK', 'department.pprk', 'file-earmark-check'],
            0 => [
                0 => 'Dashboard',
                1 => 'ketua-umum.dashboard',
                2 => 'grid',
            ],
            1 => [
                0 => 'Persetujuan Surat',
                1 => 'ketua-umum.persetujuan-surat.index',
                2 => 'pen',
            ],
            2 => [
                0 => 'Pengajuan Departemen',
                1 => 'department.reviews',
                2 => 'inbox',
            ],
            3 => [
                0 => 'Monitoring Organisasi',
                1 => 'ketua-umum.monitoring-organisasi',
                2 => 'graph-up',
            ],
            4 => [
                0 => 'Eskalasi Pengawas',
                1 => 'ketua-umum.eskalasi-pengawas.index',
                2 => 'exclamation-triangle',
            ],
            5 => [
                0 => 'Tanda Tangan',
                1 => 'ketua-umum.tanda-tangan',
                2 => 'pencil',
            ],
            6 => [
                0 => 'Laporan Keuangan',
                1 => 'finance.shared',
                2 => 'cash-stack',
            ],
        ],
        'sekretaris_umum' => [
            'lpj' => ['LPJ', 'officer-reports.index', 'file-earmark-text'],
            'pprk' => ['Pengajuan RPK', 'department.pprk', 'file-earmark-check'],
            0 => [
                0 => 'Dashboard',
                1 => 'sekretaris.dashboard',
                2 => 'grid',
            ],
            1 => [
                0 => 'Surat Masuk',
                1 => 'sekretaris.surat-masuk.index',
                2 => 'envelope',
            ],
            2 => [
                0 => 'Surat Keluar',
                1 => 'sekretaris.surat-keluar.index',
                2 => 'send',
            ],
            3 => [
                0 => 'Arsip',
                1 => 'sekretaris.arsip-surat.index',
                2 => 'archive',
            ],
            4 => [
                0 => 'Pengajuan Departemen',
                1 => 'department.reviews',
                2 => 'inbox',
            ],
            5 => [
                0 => 'Open Rekrutmen',
                1 => 'recruitment.index',
                2 => 'people',
            ],
            6 => [
                0 => 'Permintaan Tanda Tangan',
                1 => 'tanda-tangan.permintaan',
                2 => 'pen',
            ],
        ],
        'koordinator_pengawas' => [
            'rpk' => ['Verifikasi PPRK', 'department.reviews', 'file-earmark-check', ['type' => 'rpk']],
            'lpj' => ['Verifikasi LPJ', 'officer-reports.index', 'file-earmark-text'],
            'department-lpj' => ['LPJ Departemen', 'department.reviews', 'inbox'],
            'pprk' => ['Pengajuan RPK', 'department.pprk', 'file-earmark-check'],
            0 => [
                0 => 'Dashboard',
                1 => 'pengawas.dashboard',
                2 => 'grid',
            ],
            1 => [
                0 => 'Program Departemen',
                1 => 'administration.programs',
                2 => 'calendar-event',
            ],
            2 => [
                0 => 'Laporan Masuk',
                1 => 'pengawas.laporan.index',
                2 => 'inbox',
            ],
            3 => [
                0 => 'Pengawasan Aktif',
                1 => 'pengawas.aktif.index',
                2 => 'eye',
            ],
            4 => [
                0 => 'Hasil Audit',
                1 => 'pengawas.audit.index',
                2 => 'clipboard-check',
            ],
            5 => [
                0 => 'Rekomendasi',
                1 => 'pengawas.rekomendasi.index',
                2 => 'chat-square-text',
            ],
            6 => [
                0 => 'Eskalasi Ketua',
                1 => 'pengawas.eskalasi.index',
                2 => 'arrow-up-right',
            ],
            7 => [
                0 => 'Laporan Keuangan',
                1 => 'finance.shared',
                2 => 'cash-stack',
            ],
        ],
        'bendahara_umum' => [
            'rpk' => ['Verifikasi PPRK', 'department.reviews', 'file-earmark-check', ['type' => 'rpk']],
            'lpj' => ['LPJ', 'officer-reports.index', 'file-earmark-text'],
            'recruitment' => ['Open Rekrutmen', 'recruitment.index', 'person-plus'],
            'pprk' => ['Pengajuan RPK', 'department.pprk', 'file-earmark-check'],
            0 => [
                0 => 'Dashboard Keuangan',
                1 => 'finance.dashboard',
                2 => 'grid',
            ],
            1 => [
                0 => 'Pemasukan',
                1 => 'finance.entries',
                2 => 'arrow-down-left',
                3 => 'income',
            ],
            2 => [
                0 => 'Pengeluaran',
                1 => 'finance.entries',
                2 => 'arrow-up-right',
                3 => 'expense',
            ],
            3 => [
                0 => 'Penagihan Iuran',
                1 => 'finance.billing',
                2 => 'wallet2',
            ],
            5 => [
                0 => 'Pengajuan Dana',
                1 => 'finance.funding',
                2 => 'inbox',
            ],
            6 => [
                0 => 'Laporan Keuangan',
                1 => 'finance.report',
                2 => 'file-earmark-bar-graph',
            ],
            8 => [
                0 => 'Monitoring Anggaran',
                1 => 'finance.monitoring',
                2 => 'pie-chart',
            ],
            9 => [
                0 => 'Log Aktivitas',
                1 => 'finance.logs',
                2 => 'clock-history',
            ],
        ],
        'koordinator_departemen' => [
            ['Dashboard', 'dashboard', 'grid'],
            ['Pengajuan RPK', 'department.pprk', 'file-earmark-check'],
        ],
        'super_admin' => [
            0 => [
                0 => 'Super Admin',
                1 => 'admin.dashboard',
                2 => 'grid',
            ],
            1 => [
                0 => 'Open Rekrutmen',
                1 => 'recruitment.index',
                2 => 'people',
            ],
        ],
        'anggota_departemen' => [
            0 => [
                0 => 'Dashboard',
                1 => 'dashboard',
                2 => 'grid',
            ],
        ],
    ],
];
