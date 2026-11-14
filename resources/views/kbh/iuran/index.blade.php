@extends($duesLayout ?? 'layouts.kbh')

@section('title', 'Iuran | KBH')
@section('page-title', 'Iuran HIMSI')
@section('page-subtitle', 'Bayar iuran dan pantau verifikasi Bendahara')

@section('content')
<form method="GET" class="d-flex gap-2 align-items-end flex-wrap mb-4"><div><label for="dues-year" class="form-label">Tahun iuran</label><input class="form-control" id="dues-year" type="number" name="tahun" min="2026" max="2100" value="{{ $tahun }}" required></div><button type="submit" class="btn btn-navy">Tampilkan</button></form>
@php
    $total = count($bulan) * $nominal;
    $lunas = $riwayat->where('status','lunas')->sum('nominal');
    $total = $riwayat->sum('nominal') + (count($bulan)-$riwayat->count())*$nominal;
@endphp
@php
    $currentPayment = $riwayat->get(now()->month);
@endphp
<section class="dues-status-card {{ $currentPayment?->status === 'lunas' ? 'paid' : '' }}"><span class="dues-status-icon"><i class="bi bi-{{ $currentPayment?->status === 'lunas' ? 'check-circle' : 'clock' }}"></i></span><div><p>Iuran {{ $bulan[now()->month] ?? ($bulan[1] ?? 'Bulan Ini') }} {{ $tahun }}</p><h2>{{ ['lunas'=>'Lunas','menunggu_verifikasi'=>'Menunggu Verifikasi','ditolak'=>'Bukti Ditolak'][$currentPayment?->status ?? ''] ?? 'Belum Dibayar' }}</h2><p>Tagihan: Rp {{ number_format($currentPayment?->nominal ?? $nominal,0,',','.') }}</p><p class="mb-0"><i class="bi bi-info-circle me-2"></i>Status akan menjadi <b>Lunas</b> hanya setelah diverifikasi oleh Bendahara.</p></div></section>
<div class="himsi-iuran-summary">
    <article class="himsi-iuran-stat"><i class="bi bi-coin dues-stat-icon"></i><span>Total Iuran {{ $tahun }}</span><strong>Rp {{ number_format($total, 0, ',', '.') }}</strong><small>{{ count($bulan) }} bulan × Rp {{ number_format($nominal, 0, ',', '.') }}</small></article>
    <article class="himsi-iuran-stat"><i class="bi bi-check-circle dues-stat-icon text-success"></i><span>Lunas</span><strong>Rp {{ number_format($lunas, 0, ',', '.') }}</strong><small>{{ $jumlahLunas }} bulan sudah diverifikasi</small></article>
    <article class="himsi-iuran-stat"><i class="bi bi-calendar4 dues-stat-icon"></i><span>Belum Lunas</span><strong>Rp {{ number_format($total - $lunas, 0, ',', '.') }}</strong><small>Termasuk bulan yang menunggu verifikasi atau belum tercatat</small></article>
</div>

<div class="himsi-iuran-info" role="note">
  <i class="bi bi-info-circle"></i>
  <div><strong>Bayar iuran menggunakan transfer, lalu unggah buktinya.</strong><p>Setelah diunggah, status menjadi <b>Menunggu Verifikasi</b>. Hanya Bendahara yang bisa menetapkan <b>Lunas</b>. Bulan yang belum mempunyai catatan tidak otomatis dianggap sudah lunas.</p></div>
</div>

<div class="row g-4"><div class="col-xl-8"><div class="content-card himsi-iuran-content">
  <div class="section-header">
    <div><span class="section-label">PEMBAYARAN ANGGOTA</span><h2>Riwayat Iuran {{ $tahun }}</h2></div>
    <strong>Rp {{ number_format($nominal, 0, ',', '.') }} / bulan</strong>
  </div>
  <div class="table-responsive">
    <table class="kbh-table himsi-iuran-table">
      <thead><tr><th>No.</th><th>Bulan</th><th>Nominal</th><th>Status</th><th>Bukti / Catatan</th></tr></thead>
      <tbody>
        @foreach($bulan as $angka => $nama)
          @php $pembayaran = $riwayat->get($angka); @endphp
          <tr>
            <td>{{ $angka }}</td>
            <td><strong>{{ $nama }}</strong></td>
            <td>Rp {{ number_format($pembayaran?->nominal ?? $nominal, 0, ',', '.') }}</td>
            <td>
              @if(!$pembayaran)
                <span class="himsi-iuran-status belum">Belum Ada Bukti</span>
              @elseif($pembayaran->status === 'menunggu_verifikasi')
                <span class="himsi-iuran-status pending">Menunggu Verifikasi</span>
              @elseif($pembayaran->status === 'lunas')
                <span class="himsi-iuran-status lunas"><i class="bi bi-check-circle"></i> Lunas</span>
              @else
                <span class="himsi-iuran-status ditolak">Ditolak</span>
              @endif
            </td>
            <td>
              @if($pembayaran)
                <a class="himsi-iuran-link" href="{{ route('iuran.bukti.lihat', $pembayaran) }}" target="_blank" rel="noopener" aria-label="Lihat bukti transfer" title="Lihat bukti transfer"><i class="bi bi-eye"></i> Lihat Bukti</a>
                <span class="himsi-iuran-sep">·</span>
                <a class="himsi-iuran-link" href="{{ route('iuran.bukti.unduh', $pembayaran) }}">Unduh</a>
                @if($pembayaran->catatan_anggota)<p class="himsi-iuran-note">{{ $pembayaran->catatan_anggota }}</p>@endif
                @if($pembayaran->status === 'ditolak' && $pembayaran->catatan_bendahara)
                  <p class="himsi-iuran-note"><b>Catatan Bendahara:</b> {{ $pembayaran->catatan_bendahara }}</p>
                @endif
              @else
                <span class="himsi-iuran-small">Belum ada bukti transfer</span>
              @endif
            </td>

          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
</div><aside class="col-xl-4">

@include('kbh.iuran.payment-info')
@php($availableMonths = collect($bulan)->filter(fn ($name, $number) => !$riwayat->has($number) || $riwayat->get($number)->status === 'ditolak'))
<button class="btn btn-warning w-100 fw-bold mb-4" type="button" data-bs-toggle="modal" data-bs-target="#duesUpload" @disabled($availableMonths->isEmpty())><i class="bi bi-upload me-2"></i>Bayar / Unggah Bukti</button>
</aside></div>
@if($availableMonths->isNotEmpty())
<div class="modal fade" id="duesUpload" tabindex="-1" aria-labelledby="duesUploadTitle" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-lg"><div class="modal-content"><div class="modal-header"><div><h2 class="modal-title fs-5" id="duesUploadTitle"><i class="bi bi-cloud-arrow-up me-2"></i>Unggah Bukti Pembayaran</h2><small class="text-secondary">Pilih bulan iuran dan unggah bukti transfer Anda.</small></div><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div><form method="POST" enctype="multipart/form-data" action="{{ route($duesPrefix.'.store',['bulan'=>$availableMonths->keys()->contains(now()->month) ? now()->month : $availableMonths->keys()->first()]) }}" data-dues-upload>@csrf<input type="hidden" name="tahun" value="{{ $tahun }}"><div class="modal-body"><div class="alert alert-warning small">Setelah dikirim, status tetap <b>Menunggu Verifikasi</b> hingga diperiksa Bendahara.</div><div class="row g-3 mb-3"><div class="col-sm-7"><label class="form-label" for="upload-month">Bulan</label><select class="form-select" id="upload-month" name="upload_month" data-dues-month>@foreach($availableMonths as $number=>$name)<option value="{{ $number }}" data-action="{{ route($duesPrefix.'.store',['bulan'=>$number]) }}" @selected((int) old('upload_month', $availableMonths->keys()->contains(now()->month) ? now()->month : $availableMonths->keys()->first())===$number)>{{ $name }} {{ $tahun }}</option>@endforeach</select></div><div class="col-sm-5"><label class="form-label">Nominal</label><div class="form-control bg-light fw-bold">Rp {{ number_format($nominal,0,',','.') }}</div></div></div><div class="dues-upload-grid"><div><label class="form-label fw-bold" for="dues-proof">Unggah Bukti Pembayaran *</label><label class="dues-dropzone" data-upload-zone><i class="bi bi-cloud-arrow-up"></i><strong>Pilih file atau tarik dan lepas di sini</strong><small>JPG, PNG, atau PDF · Maksimal 5 MB</small><input class="form-control" id="dues-proof" name="bukti_transfer" type="file" accept=".jpg,.jpeg,.png,.pdf" required data-upload-preview></label></div><div><strong class="form-label d-block">Pratinjau</strong><div class="dues-proof-preview" data-upload-result><i class="bi bi-file-earmark-image"></i><small>Belum ada berkas dipilih</small></div></div></div><label class="form-label mt-3" for="dues-note">Catatan (opsional)</label><textarea class="form-control" name="catatan_anggota" id="dues-note" rows="2" maxlength="200" placeholder="Contoh: Pembayaran iuran bulan ini.">{{ old('catatan_anggota') }}</textarea><label class="d-flex gap-2 small mt-3"><input class="form-check-input" type="checkbox" name="konfirmasi" value="1" required> Saya menyatakan pembayaran telah dilakukan dan bukti yang diunggah benar.</label></div><div class="modal-footer"><button class="btn btn-outline-navy" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-warning fw-bold"><i class="bi bi-send me-2"></i>Kirim Bukti</button></div></form></div></div></div>
@endif
@endsection
