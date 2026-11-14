<?php

return [
    'period' => '2026/2027',
    'lpj_template' => 'templates/lpj-himsi.docx',
    // Map only confirmed finance columns. Both department and program linkage are required.
    /*
    Isi pemetaan setelah tabel modul keuangan dikonfirmasi oleh kelompok Bendahara.
    Semua baris wajib memiliki relasi eksplisit ke ops_programs.id dan kode departemen.
    Bentuk pemetaan:
    'expenses' => [
        'table' => 'NAMA_TABEL_TRANSAKSI',
        'program' => 'KOLOM_ID_OPS_PROGRAM',
        'department' => 'KOLOM_KODE_DEPARTEMEN',
        'amount' => 'KOLOM_NOMINAL',
        'date' => 'KOLOM_TANGGAL',
        'description' => 'KOLOM_URAIAN',
        'status_column' => 'KOLOM_STATUS', // opsional, gunakan bila ada transaksi draft
        'status_value' => 'NILAI_STATUS_FINAL',
    ],
    Pemetaan dibaca saja; modul ini tidak membuat transaksi atau mencairkan dana.
    Jangan menghubungkan ID proker dari tabel lain tanpa pemetaan ID yang benar.
    */
    'expenses' => null,
    'program_statuses' => ['draft' => 'Draft', 'pending' => 'Pending Approval', 'revision' => 'Perlu Revisi', 'approved' => 'Disetujui', 'running' => 'Berjalan', 'completed' => 'Selesai', 'rejected' => 'Ditolak'],
    'submission_statuses' => ['pending_officers' => 'Menunggu Verifikasi Tiga Pengurus', 'pending_supervisor' => 'Review Koordinator Pengawas', 'pending_archive' => 'Menunggu Arsip Sekertaris', 'archived' => 'Diarsipkan', 'ready_funding' => 'Siap Dicairkan', 'disbursed' => 'Sudah Dicairkan', 'draft' => 'Draft', 'pending_secretary' => 'Review Sekertaris', 'pending_chair' => 'Review Ketua Umum', 'pending_treasurer' => 'Review Bendahara', 'approved' => 'Disetujui', 'sent' => 'Dikirim', 'revision' => 'Perlu Revisi', 'rejected' => 'Ditolak'],
    'task_statuses' => ['assigned' => 'Belum Dikerjakan', 'working' => 'Dikerjakan', 'submitted' => 'Menunggu Review', 'revision' => 'Perlu Revisi', 'done' => 'Selesai'],
    'pprk_statuses' => ['draft' => 'Belum diajukan', 'pending_officers' => 'Review tiga pengurus', 'pending_supervisor' => 'Review Koordinator Pengawas', 'pending_archive' => 'Disetujui · menunggu arsip', 'archived' => 'Diarsipkan', 'rejected' => 'Perlu perbaikan', 'supervisor_rejected' => 'Ditolak Pengawas'],
    'templates' => ['letter' => ['path' => 'templates/proposal-himsi.pdf', 'name' => 'Pedoman-Proposal-HIMSI.pdf'], 'budget' => ['path' => 'templates/rab-himsi.docx', 'name' => 'Template-RAB-HIMSI.docx'], 'report' => ['path' => 'templates/lpj-himsi.docx', 'name' => 'Template-LPJ-HIMSI.docx']],
    'types' => ['letter' => 'Surat Proposal', 'budget' => 'RAB', 'rpk' => 'PPRK', 'report' => 'LPJ'],
];
