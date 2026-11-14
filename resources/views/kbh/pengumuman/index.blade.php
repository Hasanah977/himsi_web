@extends('layouts.kbh')

@section('title', 'Pengumuman | KBH')
@section('page-title', 'Pengumuman')
@section('page-subtitle', 'Informasi dan pengumuman terbaru HIMSI')

@section('content')

<div class="page-heading">

    <div>
        <span class="section-label">INFORMASI HIMSI</span>
        <h2>Daftar Pengumuman</h2>
        <p>
            Temukan informasi akademik dan kegiatan
            kemahasiswaan HIMSI.
        </p>
    </div>

</div>


<div class="news-list">

    <article class="news-card">

        <div class="news-date">
            <strong>29</strong>
            <span>SEP 2026</span>
        </div>

        <div class="news-content">

            <span class="status-badge">
                Terbaru
            </span>

            <h3>
                Pemberitahuan Administrasi Himpunan
            </h3>

            <p>
                Informasi mengenai layanan administrasi
                dan kegiatan Himpunan Mahasiswa Sistem Informasi.
            </p>

            <a href="{{ route('kbh.pengumuman.show', 1) }}"
               class="text-link">
                Baca Selengkapnya
            </a>

        </div>

    </article>


    <article class="news-card">

        <div class="news-date">
            <strong>25</strong>
            <span>SEP 2026</span>
        </div>

        <div class="news-content">

            <span class="status-badge">
                HIMSI
            </span>

            <h3>
                Informasi Kegiatan Mahasiswa
            </h3>

            <p>
                Informasi terbaru mengenai agenda dan
                kegiatan organisasi HIMSI.
            </p>

            <a href="{{ route('kbh.pengumuman.show', 2) }}"
               class="text-link">
                Baca Selengkapnya
            </a>

        </div>

    </article>

</div>

@endsection



