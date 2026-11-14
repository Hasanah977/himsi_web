@extends($duesLayout ?? 'layouts.kbh')

@section('title', 'Bayar Iuran | KBH')
@section('page-title', 'Bayar Iuran')
@section('page-subtitle', 'Upload bukti transfer untuk diverifikasi Bendahara')

@section('content')
<div class="himsi-iuran-form-layout">
  <div class="content-card himsi-iuran-content">
    <a class="himsi-iuran-link" href="{{ route($duesPrefix.'.index',['tahun'=>$tahun]) }}"><i class="bi bi-arrow-left"></i> Kembali ke Iuran</a>
    <div class="section-header" style="margin-top:16px"><div><span class="section-label">PEMBAYARAN BULANAN</span><h2>{{ $namaBulan }} {{ $tahun }}</h2></div></div>
    <p class="himsi-iuran-amount">Rp {{ number_format($nominal, 0, ',', '.') }}</p>

    @if($errors->any())
      <div class="himsi-iuran-alert danger" role="alert"><strong>Periksa isian:</strong><ul>@foreach($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul></div>
    @endif

    @if($pembayaran && $pembayaran->status === 'lunas')
      <div class="himsi-iuran-alert success">Iuran bulan ini sudah diverifikasi Lunas oleh Bendahara. Tidak perlu membayar ulang.</div>
    @elseif($pembayaran && $pembayaran->status === 'menunggu_verifikasi')
      <div class="himsi-iuran-alert pending">Bukti transfer sudah diterima sistem dan sedang diperiksa Bendahara. Kamu tidak dapat mengirim ulang hingga ada keputusan.</div>
    @else
      @if($pembayaran && $pembayaran->status === 'ditolak')
        <div class="himsi-iuran-alert danger"><strong>Bukti sebelumnya ditolak.</strong><p>{{ $pembayaran->catatan_bendahara ?: 'Periksa pembayaran dan unggah bukti yang benar.' }}</p></div>
      @endif
      <form action="{{ route($duesPrefix.'.store', ['bulan'=>$bulan]) }}" method="POST" enctype="multipart/form-data">
        @csrf<input type="hidden" name="tahun" value="{{ $tahun }}">
        <div class="himsi-iuran-field">
          <label for="bukti_transfer">Bukti Transfer <span aria-hidden="true">*</span></label>
          <input id="bukti_transfer" name="bukti_transfer" type="file" accept=".jpg,.jpeg,.png,.pdf" required>
          <small>Format JPG, PNG, atau PDF. Maksimal 5 MB. File tersimpan privat dan dapat diperiksa Bendahara.</small>
        </div>
        <label class="himsi-iuran-check"><input type="checkbox" name="konfirmasi" value="1" required> <span>Saya menyatakan telah melakukan pembayaran iuran {{ $namaBulan }} {{ $tahun }} sebesar Rp {{ number_format($nominal, 0, ',', '.') }} dan bukti yang diunggah benar.</span></label>
        <button type="submit" class="himsi-iuran-submit"><i class="bi bi-cloud-arrow-up"></i> Kirim Bukti Pembayaran</button>
      </form>
    @endif
  </div>
  <aside>@include('kbh.iuran.payment-info')</aside>
</div>
@endsection
