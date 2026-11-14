<?php

return [
    'roles' => [
        'pending' => 'Belum Ditentukan',
        'super_admin' => 'Super Admin',
        'ketua_umum' => 'Ketua Umum',
        'sekretaris_umum' => 'Sekertaris Umum',
        'bendahara_umum' => 'Bendahara Umum',
        'koordinator_departemen' => 'Koordinator Departemen',
        'anggota_departemen' => 'Anggota Departemen',
        'panitia_cb' => 'Panitia Character Building',
        'panitia_kpum' => 'Panitia KPUM',
        'kbh' => 'KBH',
        'koordinator_pengawas' => 'Koordinator Pengawas Himpunan',
    ],
    'departments' => [
        'akademik' => 'Akademik',
        'humas' => 'Humas',
        'advokasi' => 'Advokasi',
        'kaderisasi' => 'Kaderisasi',
        'dana_usaha' => 'Dana dan Usaha',
        'kesekretariatan' => 'Kesekretariatan',
    ],
    'modules' => [
        'kbh' => [
            0 => [
                'label' => 'Kelola Seleksi Pengurus',
                'route' => 'administration.selection',
            ],
            1 => [
                'label' => 'Dashboard',
                'route' => 'kbh.dashboard',
            ],
            2 => [
                'label' => 'Pengumuman',
                'route' => 'kbh.pengumuman.index',
            ],
            3 => [
                'label' => 'Program Kerja',
                'route' => 'kbh.program-kerja.index',
            ],
            5 => [
                'label' => 'Hasil Voting',
                'route' => 'kbh.voting.hasil',
            ],
            6 => [
                'label' => 'Seleksi Pengurus',
                'route' => 'kbh.seleksi.index',
            ],
            7 => [
                'label' => 'Iuran',
                'route' => 'kbh.iuran.index',
            ],
            8 => [
                'label' => 'Aspirasi',
                'route' => 'kbh.aspirasi.index',
            ],
            9 => [
                'label' => 'Profil',
                'route' => 'kbh.profil',
            ],
        ],
        'ketua_umum' => [
            0 => [
                'label' => 'Dashboard',
                'route' => 'ketua-umum.dashboard',
            ],
            1 => [
                'label' => 'Persetujuan Surat',
                'route' => 'ketua-umum.persetujuan-surat.index',
            ],
            2 => [
                'label' => 'Pengajuan Departemen',
                'route' => 'department.reviews',
            ],
            3 => [
                'label' => 'Monitoring Organisasi',
                'route' => 'ketua-umum.monitoring-organisasi',
            ],
            4 => [
                'label' => 'Eskalasi Pengawas',
                'route' => 'ketua-umum.eskalasi-pengawas.index',
            ],
            5 => [
                'label' => 'Tanda Tangan',
                'route' => 'ketua-umum.tanda-tangan',
            ],
        ],
        'sekretaris_umum' => [
            0 => [
                'label' => 'Dashboard',
                'route' => 'sekretaris.dashboard',
            ],
            1 => [
                'label' => 'Surat Masuk',
                'route' => 'sekretaris.surat-masuk.index',
            ],
            2 => [
                'label' => 'Surat Keluar',
                'route' => 'sekretaris.surat-keluar.index',
            ],
            3 => [
                'label' => 'Arsip Surat',
                'route' => 'sekretaris.arsip-surat.index',
            ],
            4 => [
                'label' => 'Pengajuan Departemen',
                'route' => 'department.reviews',
            ],
            5 => [
                'label' => 'Seleksi Pengurus',
                'route' => 'administration.selection',
            ],
            6 => [
                'label' => 'Permintaan Tanda Tangan',
                'route' => 'tanda-tangan.permintaan',
            ],
        ],
        'koordinator_pengawas' => [
            0 => [
                'label' => 'Dashboard',
                'route' => 'pengawas.dashboard',
            ],
            1 => [
                'label' => 'Program Departemen',
                'route' => 'administration.programs',
            ],
            2 => [
                'label' => 'Laporan Masuk',
                'route' => 'pengawas.laporan.index',
            ],
            3 => [
                'label' => 'Pengawasan Aktif',
                'route' => 'pengawas.aktif.index',
            ],
            4 => [
                'label' => 'Hasil Audit',
                'route' => 'pengawas.audit.index',
            ],
            5 => [
                'label' => 'Rekomendasi',
                'route' => 'pengawas.rekomendasi.index',
            ],
            6 => [
                'label' => 'Eskalasi Ketua',
                'route' => 'pengawas.eskalasi.index',
            ],
        ],
        'bendahara_umum' => [
            0 => [
                'label' => 'Dashboard Keuangan',
                'route' => 'finance.dashboard',
            ],
            1 => [
                'label' => 'Pengajuan Dana',
                'route' => 'finance.funding',
            ],
            2 => [
                'label' => 'Laporan Keuangan',
                'route' => 'finance.report',
            ],
            3 => [
                'label' => 'Monitoring Anggaran',
                'route' => 'finance.monitoring',
            ],
            4 => [
                'label' => 'Verifikasi Iuran',
                'route' => 'bendahara.iuran.index',
            ],
            5 => [
                'label' => 'Persuratan',
                'route' => 'tanda-tangan.permintaan',
            ],
        ],
        'seleksi_pengurus' => [
        ],
        'koordinator_departemen.akademik' => [
            0 => [
                'label' => 'Dashboard Departemen',
                'route' => 'department.dashboard',
                'parameters' => [
                    'department' => 'akademik',
                ],
            ],
        ],
        'anggota_departemen.akademik' => [
            0 => [
                'label' => 'Portal Anggota',
                'route' => 'department.member.dashboard',
                'parameters' => [
                    'department' => 'akademik',
                ],
            ],
        ],
        'koordinator_departemen.humas' => [
            0 => [
                'label' => 'Dashboard Departemen',
                'route' => 'department.dashboard',
                'parameters' => [
                    'department' => 'humas',
                ],
            ],
        ],
        'anggota_departemen.humas' => [
            0 => [
                'label' => 'Portal Anggota',
                'route' => 'department.member.dashboard',
                'parameters' => [
                    'department' => 'humas',
                ],
            ],
        ],
        'koordinator_departemen.advokasi' => [
            0 => [
                'label' => 'Dashboard Departemen',
                'route' => 'department.dashboard',
                'parameters' => [
                    'department' => 'advokasi',
                ],
            ],
        ],
        'anggota_departemen.advokasi' => [
            0 => [
                'label' => 'Portal Anggota',
                'route' => 'department.member.dashboard',
                'parameters' => [
                    'department' => 'advokasi',
                ],
            ],
        ],
        'koordinator_departemen.kaderisasi' => [
            0 => [
                'label' => 'Dashboard Departemen',
                'route' => 'department.dashboard',
                'parameters' => [
                    'department' => 'kaderisasi',
                ],
            ],
        ],
        'anggota_departemen.kaderisasi' => [
            0 => [
                'label' => 'Portal Anggota',
                'route' => 'department.member.dashboard',
                'parameters' => [
                    'department' => 'kaderisasi',
                ],
            ],
        ],
        'koordinator_departemen.dana_usaha' => [
            0 => [
                'label' => 'Dashboard Departemen',
                'route' => 'department.dashboard',
                'parameters' => [
                    'department' => 'dana_usaha',
                ],
            ],
        ],
        'anggota_departemen.dana_usaha' => [
            0 => [
                'label' => 'Portal Anggota',
                'route' => 'department.member.dashboard',
                'parameters' => [
                    'department' => 'dana_usaha',
                ],
            ],
        ],
        'koordinator_departemen.kesekretariatan' => [
            0 => [
                'label' => 'Dashboard Departemen',
                'route' => 'department.dashboard',
                'parameters' => [
                    'department' => 'kesekretariatan',
                ],
            ],
        ],
        'anggota_departemen.kesekretariatan' => [
            0 => [
                'label' => 'Portal Anggota',
                'route' => 'department.member.dashboard',
                'parameters' => [
                    'department' => 'kesekretariatan',
                ],
            ],
        ],
        'panitia_cb' => [
            0 => [
                'label' => 'Dashboard CB',
                'route' => 'cb.dashboard',
            ],
            1 => [
                'label' => 'Rekrutmen KBH',
                'route' => 'cb.recruitment',
            ],
            2 => [
                'label' => 'Peserta & Seleksi CB',
                'route' => 'cb.participants',
            ],
            3 => [
                'label' => 'Kegiatan, Dokumentasi & Laporan',
                'route' => 'cb.activities',
            ],
        ],
        'panitia_kpum' => [
            0 => [
                'label' => 'Dashboard KPUM',
                'route' => 'kpum.dashboard',
            ],
            1 => [
                'label' => 'Manajemen Kandidat',
                'route' => 'kpum.candidates',
            ],
            2 => [
                'label' => 'Kontrol Voting',
                'route' => 'kpum.control',
            ],
            3 => [
                'label' => 'Hasil & Audit',
                'route' => 'kpum.results',
            ],
            4 => [
                'label' => 'Pengumuman',
                'route' => 'kpum.announcement',
            ],
        ],
    ],
    'sections' => [
        'quotes' => [
            'label' => 'Kutipan Beranda',
            'icon' => 'bi-quote',
            'fields' => [
                'quote' => ['Isi kutipan', 'textarea'],
                'name' => ['Nama pemberi kutipan', 'text'],
                'role' => ['Jabatan pemberi kutipan', 'text'],
                'image' => ['Foto pemberi kutipan (opsional)', 'image'],
            ],
        ],
        'hero_slides' => [
            'label' => 'Slideshow Beranda', 'icon' => 'bi-images',
            'fields' => ['title' => ['Keterangan foto', 'text'], 'image' => ['Foto latar beranda — disarankan lanskap 1920 × 1080', 'image']],
        ],
        'achievements' => [
            'label' => 'Pencapaian & Prestasi', 'icon' => 'bi-trophy',
            'fields' => [
                'title' => ['Judul prestasi', 'text'], 'category' => ['Kategori prestasi', 'text'],
                'date' => ['Tanggal pencapaian', 'date'], 'summary' => ['Ringkasan', 'textarea'],
                'body' => ['Isi lengkap — satu paragraf per baris', 'lines'], 'image' => ['Foto prestasi', 'image'],
            ],
        ],
        'settings' => [
            'label' => 'Beranda & Profil',
            'icon' => 'bi-house-gear',
            'fields' => [
                'auth_background' => ['Foto latar portal anggota — lanskap (opsional)', 'image'],
                'sidebar_background' => ['Foto latar sidebar semua role — potret (opsional)', 'image'],
                'maintenance_background' => ['Foto latar Maintenance Mode — lanskap (opsional)', 'image'],
                'page_banner' => ['Foto latar Profil, Struktur, Program Kerja & Pengumuman — lanskap (opsional)', 'image'],
                'hero_badge' => [
                    0 => 'Label kecil beranda',
                    1 => 'text',
                ],
                'hero_before' => [
                    0 => 'Judul sebelum teks kuning',
                    1 => 'text',
                ],
                'hero_highlight' => [
                    0 => 'Judul berwarna kuning',
                    1 => 'text',
                ],
                'hero_after' => [
                    0 => 'Judul setelah teks kuning',
                    1 => 'text',
                ],
                'hero_description' => [
                    0 => 'Deskripsi beranda',
                    1 => 'textarea',
                ],
                'quote' => [
                    0 => 'Isi kutipan',
                    1 => 'textarea',
                ],
                'quote_name' => [
                    0 => 'Nama pemberi kutipan',
                    1 => 'text',
                ],
                'quote_role' => [
                    0 => 'Jabatan pemberi kutipan',
                    1 => 'text',
                ],
                'quote_image' => [
                    0 => 'Foto kutipan',
                    1 => 'image',
                ],
                'logo' => [
                    0 => 'Logo HIMSI',
                    1 => 'image',
                ],
                'vision' => [
                    0 => 'Visi',
                    1 => 'textarea',
                ],
                'missions' => [
                    0 => 'Misi — satu misi setiap baris',
                    1 => 'lines',
                ],
                'integrity' => [
                    0 => 'Deskripsi nilai Integritas',
                    1 => 'textarea',
                ],
                'collaboration' => [
                    0 => 'Deskripsi nilai Kolaborasi',
                    1 => 'textarea',
                ],
                'innovation' => [
                    0 => 'Deskripsi nilai Inovasi',
                    1 => 'textarea',
                ],
                'dedication' => [
                    0 => 'Deskripsi nilai Dedikasi',
                    1 => 'textarea',
                ],
                'footer_description' => [
                    0 => 'Deskripsi footer',
                    1 => 'textarea',
                ],
                'address' => [
                    0 => 'Alamat / identitas organisasi',
                    1 => 'textarea',
                ],
            ],
        ],
        'programs' => [
            'label' => 'Program Kerja Publik',
            'icon' => 'bi-calendar-event',
            'fields' => [
                'title' => [
                    0 => 'Judul',
                    1 => 'text',
                ],
                'category' => [
                    0 => 'Kategori / departemen',
                    1 => 'select',
                    2 => [
                        0 => 'Akademik',
                        1 => 'Humas',
                        2 => 'Advokasi',
                        3 => 'Kaderisasi',
                        4 => 'Dana dan Usaha',
                        5 => 'Kesekretariatan',
                    ],
                ],
                'status' => [
                    0 => 'Status',
                    1 => 'select',
                    2 => [
                        0 => 'Akan Datang',
                        1 => 'Sedang Berjalan',
                        2 => 'Selesai',
                    ],
                ],
                'date' => [
                    0 => 'Tanggal tampilan',
                    1 => 'date',
                ],
                'location' => [
                    0 => 'Lokasi',
                    1 => 'text',
                ],
                'summary' => [
                    0 => 'Ringkasan',
                    1 => 'textarea',
                ],
                'body' => [
                    0 => 'Isi lengkap — satu paragraf setiap baris',
                    1 => 'lines',
                ],
                'image' => [
                    0 => 'Foto / poster',
                    1 => 'image',
                ],
            ],
        ],
        'announcements' => [
            'label' => 'Pengumuman',
            'icon' => 'bi-megaphone',
            'fields' => [
                'title' => [
                    0 => 'Judul',
                    1 => 'text',
                ],
                'category' => [
                    0 => 'Kategori',
                    1 => 'select',
                    2 => [
                        0 => 'Akademik',
                        1 => 'Kegiatan',
                        2 => 'Penting',
                        3 => 'Info',
                        4 => 'Iuran',
                    ],
                ],
                'date' => [
                    0 => 'Tanggal tampilan',
                    1 => 'date',
                ],
                'summary' => [
                    0 => 'Ringkasan',
                    1 => 'textarea',
                ],
                'body' => [
                    0 => 'Isi lengkap — satu paragraf setiap baris',
                    1 => 'lines',
                ],
                'image' => [
                    0 => 'Foto / poster (opsional)',
                    1 => 'image',
                ],
            ],
        ],
        'gallery' => [
            'label' => 'Galeri Kegiatan',
            'icon' => 'bi-images',
            'fields' => [
                'title' => [
                    0 => 'Judul foto',
                    1 => 'text',
                ],
                'description' => [
                    0 => 'Keterangan foto',
                    1 => 'textarea',
                ],
                'image' => [
                    0 => 'Foto kegiatan',
                    1 => 'image',
                ],
            ],
        ],
        'leaders' => [
            'label' => 'Pengurus Harian',
            'icon' => 'bi-person-badge',
            'fields' => [
                'name' => [
                    0 => 'Nama',
                    1 => 'text',
                ],
                'role' => [
                    0 => 'Jabatan',
                    1 => 'text',
                ],
                'image' => [
                    0 => 'Foto pengurus',
                    1 => 'image',
                ],
            ],
        ],
        'departments' => [
            'label' => 'Struktur Departemen',
            'icon' => 'bi-diagram-3',
            'fields' => [
                'members' => ['Daftar anggota — satu nama setiap baris (opsional)', 'lines'],
                'name' => [
                    0 => 'Nama departemen',
                    1 => 'text',
                ],
                'leader' => [
                    0 => 'Nama ketua divisi / koordinator',
                    1 => 'text',
                ],
                'description' => [
                    0 => 'Deskripsi departemen',
                    1 => 'textarea',
                ],
                'image' => [
                    0 => 'Foto koordinator',
                    1 => 'image',
                ],
            ],
        ],
    ],
    'defaults' => [
        'hero_badge' => 'Ruang bertumbuh mahasiswa Sistem Informasi',
        'hero_before' => 'Sinergi Inovasi untuk',
        'hero_highlight' => 'Masa Depan',
        'hero_after' => 'Teknologi',
        'hero_description' => 'Himpunan Mahasiswa Sistem Informasi (HIMSI) adalah ruang bagi mahasiswa untuk berkolaborasi, berkreasi, dan mengembangkan potensi diri dalam bidang teknologi informasi dan bisnis.',
        'quote' => 'HIMSI menjadi ruang untuk belajar, berkolaborasi, dan mengembangkan ide yang memberikan dampak positif bagi almamater dan masyarakat.',
        'quote_name' => 'Waffiq Adkhilniy MR',
        'quote_role' => 'Bendahara Umum HIMSI',
        'quote_image' => 'bendahara.png',
        'logo' => 'logo-himsi.png',
        'vision' => 'Menjadikan HIMSI FT-UNSULBAR sebagai himpunan mahasiswa yang unggul dalam pengembangan akademik, pembinaan kader, dan penguatan organisasi, serta mampu menciptakan lingkungan yang kolaboratif, inovatif, dan berkelanjutan bagi mahasiswa Sistem Informasi.',
        'missions' => [
            'Menyelenggarakan program pengembangan akademik dan keterampilan yang mendukung peningkatan kompetensi mahasiswa Sistem Informasi.',
            'Membangun sistem kaderisasi yang berkelanjutan dengan menjunjung tinggi nilai-nilai organisasi dan budaya KMFT FT-UNSULBAR.',
            'Meningkatkan kualitas sumber daya mahasiswa melalui pengembangan hard skill dan soft skill yang relevan dengan bidang Sistem Informasi.',
            'Menumbuhkan rasa kekeluargaan, solidaritas, dan kepedulian antar mahasiswa Sistem Informasi untuk menciptakan lingkungan yang harmonis dan produktif.',
            'Menjadi wadah aspirasi mahasiswa yang terbuka, responsif, dan bertanggung jawab dalam memperjuangkan kepentingan bersama.',
        ],
        'integrity' => 'Menjunjung tinggi kejujuran, etika, dan tanggung jawab.',
        'collaboration' => 'Bekerja bersama dalam semangat kekeluargaan.',
        'innovation' => 'Mengembangkan ide dan solusi yang relevan.',
        'dedication' => 'Memberikan kontribusi terbaik bagi organisasi dan masyarakat.',
        'footer_description' => 'Himpunan Mahasiswa Sistem Informasi adalah wadah bagi mahasiswa untuk mengembangkan potensi di bidang teknologi informasi dan bisnis.',
        'address' => 'Himpunan Mahasiswa Sistem Informasi — Universitas Sulawesi Barat',
    ],
];
