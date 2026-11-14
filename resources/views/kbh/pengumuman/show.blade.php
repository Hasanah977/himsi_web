@extends('layouts.kbh')

@section('title', 'Detail Pengumuman | KBH')
@section('page-title', 'Detail Pengumuman')
@section('page-subtitle', 'Informasi lengkap pengumuman HIMSI')

@section('content')

<a href="{{ route('kbh.pengumuman.index') }}"
   class="back-link">
    Kembali ke Pengumuman
</a>


<article class="content-card article-detail">

    <span class="status-badge">
        Pengumuman
    </span>

    <h1>
        Pemberitahuan Administrasi Himpunan
    </h1>

    <div class="article-meta">
        29 September 2026 &bull; HIMSI
    </div>

    <p>
        Diberitahukan kepada seluruh KBH HIMSI bahwa
        Sistem Administrasi Himpunan telah tersedia
        untuk membantu proses pelayanan administrasi.
    </p>

    <p>
        Melalui sistem ini mahasiswa dapat melihat
        pengumuman, program kerja, mengikuti voting,
        seleksi pengurus, melihat informasi iuran,
        mengirim aspirasi, serta mengelola profil.
    </p>

</article>

@endsection

