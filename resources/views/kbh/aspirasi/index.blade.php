@extends('layouts.kbh')
@section('title', 'Aspirasi')
@section('content')
<div class="member-page-heading"><div><h2>Aspirasi & masukan</h2><p>Sampaikan ide, saran, atau kendala agar HIMSI dapat menjadi lebih baik.</p></div></div>
<div class="member-two-column">
    <section class="member-section">
        <div class="member-section-heading"><h2>Tulis aspirasi</h2><span class="member-muted">Semua kolom wajib, kecuali lampiran</span></div>
        <form action="{{ route('kbh.aspirasi.store') }}" method="POST" enctype="multipart/form-data">@csrf
            <div class="member-form-row"><label class="form-label" for="judul">Apa yang ingin Anda sampaikan?</label><input id="judul" class="form-control" name="judul" maxlength="150" placeholder="Contoh: Usulan kelas persiapan magang" required value="{{ old('judul') }}">@error('judul')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <div class="member-form-row"><label class="form-label" for="isi">Ceritakan lebih lanjut</label><textarea id="isi" class="form-control" rows="6" name="isi" maxlength="10000" placeholder="Jelaskan ide atau kendala Anda. Sertakan saran penyelesaian jika ada." required>{{ old('isi') }}</textarea>@error('isi')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <div class="member-form-row"><label class="form-label" for="lampiran">Lampiran <span class="member-muted">· opsional</span></label><div class="member-upload"><input id="lampiran" class="form-control" name="lampiran" type="file" accept=".doc,.docx,.pdf,.jpg,.jpeg,.png"><small><i class="bi bi-paperclip"></i> Gambar, PDF, atau Word. Maksimal 10 MB.</small></div>@error('lampiran')<small class="text-danger">{{ $message }}</small>@enderror</div>
            <div class="member-form-footer"><p>Aspirasi dikirim ke Koordinator Departemen Humas untuk ditinjau.</p><button class="btn btn-navy" type="submit">Kirim aspirasi <i class="bi bi-arrow-right ms-2"></i></button></div>
        </form>
    </section>
    <aside class="member-section"><div class="member-section-heading"><h2>Setelah Anda mengirim</h2></div><ol class="member-guide"><li><strong>Aspirasi diterima</strong>Kiriman Anda tersimpan dan masuk ke halaman Humas.</li><li><strong>Ditinjau oleh Humas</strong>Humas membaca aspirasi dan memberikan tanggapan.</li><li><strong>Pantau tindak lanjut</strong>Lihat status dan balasan di riwayat aspirasi di bawah.</li></ol><div class="member-recipient"><i class="bi bi-chat-square-text"></i><div>Ditujukan kepada<strong>Koordinator Departemen Humas</strong></div></div></aside>
</div>
<section class="member-section" id="riwayat-aspirasi">
    <div class="member-section-heading"><h2>Riwayat aspirasi</h2><span class="member-muted">{{ $riwayat->total() }} aspirasi</span></div>
    @forelse($riwayat as $aspirasi)
    <details class="aspiration-item"><summary><div><strong>{{ $aspirasi->judul }}</strong><small>{{ $aspirasi->created_at->timezone('Asia/Makassar')->format('d M Y · H:i') }}</small></div><span class="d-flex align-items-center gap-3"><span class="member-status {{ $aspirasi->status }}">{{ ['terkirim'=>'Terkirim','diproses'=>'Sedang ditinjau','selesai'=>'Selesai'][$aspirasi->status] ?? ucfirst($aspirasi->status) }}</span><i class="bi bi-chevron-down small"></i></span></summary>
        <p class="aspiration-body">{{ $aspirasi->isi }}</p>
        @if($aspirasi->lampiran_path)<div class="d-flex align-items-center gap-2 flex-wrap"><span class="member-muted"><i class="bi bi-paperclip"></i> {{ $aspirasi->lampiran_nama }}</span><a class="btn btn-sm btn-outline-navy" href="{{ route('kbh.aspirasi.lampiran',[$aspirasi,'lihat']) }}" target="_blank" rel="noopener">Lihat</a><a class="btn btn-sm btn-outline-navy" href="{{ route('kbh.aspirasi.lampiran',[$aspirasi,'unduh']) }}">Unduh</a></div>@endif
        @if($aspirasi->response)<div class="aspiration-reply"><strong><i class="bi bi-reply"></i> Tanggapan Humas</strong><p>{{ $aspirasi->response }}</p></div>@else<p class="member-muted mt-3">Belum ada tanggapan. Humas akan meninjau aspirasi Anda.</p>@endif
    </details>
    @empty
    <div class="member-empty"><i class="bi bi-inbox"></i><h3>Belum ada aspirasi</h3><p>Ide dan masukan yang Anda kirim akan muncul di sini.</p></div>
    @endforelse
    @if($riwayat->hasPages())<div class="mt-4">{{ $riwayat->links() }}</div>@endif
</section>
@endsection
