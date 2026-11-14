@extends('layouts.kbh')

@section('title', 'Detail Program Kerja | KBH')
@section('page-title', 'Detail Program Kerja')
@section('page-subtitle', 'Informasi lengkap kegiatan HIMSI')

@section('content')

<a href="{{ route('kbh.program-kerja.index') }}"
   class="back-link">
    â† Kembali ke Program Kerja
</a>


<article class="content-card article-detail">

    <span class="status-badge">
        Program Kerja
    </span>

    <h1>
        HIMSI Fest 2026
    </h1>

    <div class="article-meta">
        HIMSI Universitas Sulawesi Barat
    </div>

    <div class="detail-information">

        <div>
            <span>Tanggal</span>
            <strong>10 Oktober 2026</strong>
        </div>

        <div>
            <span>Lokasi</span>
            <strong>Universitas Sulawesi Barat</strong>
        </div>

        <div>
            <span>Status</span>
            <strong>Akan Datang</strong>
        </div>

    </div>

    <p>
        HIMSI Fest merupakan salah satu program kerja
        yang bertujuan meningkatkan kreativitas,
        kebersamaan, dan partisipasi mahasiswa.
    </p>

</article>

@endsection
