@extends('administration.layout')
@section('title', 'Administrasi HIMSI')
@push('styles')
<style>body{font-family:Arial,sans-serif;background:#f3f6fc;color:#172454;margin:0}main{max-width:960px;margin:24px auto;padding:0 16px}.box{background:white;padding:24px;border-radius:14px;margin:18px 0;box-shadow:0 3px 14px #101b4c10}.btn{display:inline-block;background:#facc15;padding:12px 17px;border:0;border-radius:8px;font-weight:bold;color:#14204d;text-decoration:none;cursor:pointer}.green{background:#198754;color:white}.red{background:#c93042;color:white}.field{display:block;width:100%;min-height:100px;padding:9px;box-sizing:border-box;border:1px solid #ccd5e3;border-radius:8px}.alert{padding:14px;border-radius:8px;background:#e1f3ff;line-height:1.5}.errors{background:#ffe5e5;color:#a12b2b;padding:12px;border-radius:8px}iframe{width:100%;height:55vh;border:1px solid #dbe2ed;border-radius:10px}</style>
@endpush
@section('content')

<a href="{{ route('tanda-tangan.permintaan') }}">← Kembali ke Permintaan</a><h1>Persetujuan Tanda Tangan HIMSI</h1>
@if(session('success'))<div class="alert">{{ session('success') }}</div>@endif
@if($errors->any())<div class="errors">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="box"><p><strong>Perihal:</strong> {{ $suratKeluar->perihal }}</p><p><strong>Nomor:</strong> {{ $suratKeluar->nomor_surat }}</p><p><strong>Status izin Anda:</strong> {{ ucfirst($izin->status) }}</p>
@if($suratKeluar->file_surat && strtolower(pathinfo($suratKeluar->file_surat,PATHINFO_EXTENSION))==='pdf')
<iframe src="{{ route('surat-keluar.dokumen.lihat',$suratKeluar) }}" title="PDF asli"></iframe>
@else<p>Dokumen Word atau gambar tidak perlu dikonversi untuk persetujuan. <a href="{{ route('surat-keluar.dokumen.unduh',$suratKeluar) }}">Unduh dokumen asli</a>.</p>@endif
</div>
@if($izin->status === 'menunggu')
<div class="box">
<h2>Upload & Atur Posisi TTD Anda</h2>
@if($suratKeluar->file_surat && strtolower(pathinfo($suratKeluar->file_surat,PATHINFO_EXTENSION)) === 'pdf')
<p class="alert">Klik tombol berikut untuk memilih gambar tanda tangan Anda dari File Explorer, menggesernya di atas pratinjau PDF, mengatur ukuran, kemudian menyimpan posisi.</p>
<a class="btn" href="{{ route('tanda-tangan.surat.editor',$suratKeluar) }}">Upload, Geser & Simpan TTD Saya</a>
@else<p class="alert">Untuk dokumen non-PDF, persetujuan tetap dapat diberikan tanpa konversi; gambar TTD tidak ditempel otomatis.</p>@endif
</div>
<div class="box"><h2>Keputusan Anda</h2><form method="POST" action="{{ route('tanda-tangan.persetujuan.putuskan',$suratKeluar) }}">@csrf
<label for="catatan">Catatan (wajib jika menolak)</label><textarea id="catatan" name="catatan" class="field" maxlength="2000">{{ old('catatan') }}</textarea>
<p><label><input type="checkbox" name="konfirmasi" value="1"> Jika saya menyetujui, saya mengizinkan tanda tangan milik saya yang tersimpan pada surat ini ditempel setelah seluruh persetujuan lengkap.</label></p>
<button type="submit" class="btn green" name="keputusan" value="disetujui" data-confirm="Setujui dan izinkan tanda tangan untuk surat ini?">Setujui & Tanda Tangani</button>
<button type="submit" class="btn red" name="keputusan" value="ditolak" data-confirm="Tolak permintaan ini?">Tolak</button>
</form></div>
@endif

@endsection
@push('scripts')

@endpush
