@extends('layouts.kbh')

@section('title', 'Program Kerja | KBH')
@section('page-title', 'Program Kerja')
@section('page-subtitle', 'Program dan kegiatan HIMSI')

@section('content')

<div class="page-heading">

    <div>
        <span class="section-label">KEGIATAN HIMSI</span>
        <h2>Program Kerja</h2>

        <p>
            Informasi program kerja yang dilaksanakan
            oleh HIMSI.
        </p>
    </div>

</div>


<div class="program-grid">

    <article class="program-card">

        <div class="program-image">

            <div class="image-placeholder">
                HIMSI FEST
            </div>

        </div>

        <div class="program-body">

            <span class="status-badge">
                Akan Datang
            </span>

            <h3>
                HIMSI Fest 2026
            </h3>

            <p>
                Kegiatan tahunan HIMSI sebagai wadah
                kreativitas dan kebersamaan mahasiswa.
            </p>

            <a href="{{ route('kbh.program-kerja.show', 1) }}"
               class="text-link">
                Lihat Detail
            </a>

        </div>

    </article>


    <article class="program-card">

        <div class="program-image">

            <div class="image-placeholder">
                HIMSI
            </div>

        </div>

        <div class="program-body">

            <span class="status-badge">
                Program
            </span>

            <h3>
                Pengembangan Kompetensi Mahasiswa
            </h3>

            <p>
                Program peningkatan kemampuan mahasiswa
                Sistem Informasi.
            </p>

            <a href="{{ route('kbh.program-kerja.show', 2) }}"
               class="text-link">
                Lihat Detail
            </a>

        </div>

    </article>

</div>

@endsection
