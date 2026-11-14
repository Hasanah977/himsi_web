@props(['application'])
@php
    $reviewed = in_array($application->status,['lulus_berkas','wawancara','dinilai','lolos'],true) || ($application->status === 'tidak_lolos' && $application->score !== null);
    $final = in_array($application->status,['lolos','tidak_lolos'],true);
@endphp
<ol class="selection-timeline">
    <li class="done"><time>{{ $application->created_at->copy()->timezone('Asia/Makassar')->format('d M Y · H:i') }} WITA</time><h3>Pendaftaran Dikirim</h3><p>Berkas pendaftaran berhasil diterima sistem.</p></li>
    <li class="{{ $reviewed ? 'done' : (!$final ? 'current' : '') }}">@if($application->reviewed_at)<time>{{ $application->reviewed_at->copy()->timezone('Asia/Makassar')->format('d M Y · H:i') }} WITA</time>@endif<h3>Verifikasi Berkas</h3><p>{{ $reviewed ? 'Berkas Anda telah lolos verifikasi panitia.' : ($final ? 'Berkas belum memenuhi persyaratan.' : 'Menunggu pemeriksaan berkas oleh panitia.') }}</p></li>
    <li class="{{ in_array($application->status,['dinilai','lolos']) ? 'done' : ($reviewed && !$final ? 'current' : '') }}"><h3>Tahap Seleksi</h3><p>@if($application->interview_at)Wawancara: {{ $application->interview_at->copy()->timezone('Asia/Makassar')->format('d M Y · H:i') }} WITA · {{ $application->interview_location }}@else{{ $final && !$reviewed ? 'Tidak dilanjutkan ke tahap wawancara.' : 'Jadwal wawancara akan ditampilkan setelah ditetapkan panitia.' }}@endif</p></li>
    <li class="{{ $final ? 'done' : '' }}"><h3>Pengumuman Hasil</h3><p>{{ $final ? ($application->status === 'lolos' ? 'Selamat, Anda terpilih sebagai pengurus HIMSI.' : 'Anda belum terpilih pada periode ini.') : 'Hasil akhir akan ditampilkan setelah dipublikasikan oleh panitia.' }}</p></li>
</ol>
