@extends('administration.layout')
@section('title', 'Administrasi HIMSI')
@push('styles')
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f6fc;color:#122253;font:15px/1.6 Arial,Helvetica,sans-serif}
.bh-nav{background:#111c4b;color:white;padding:17px max(20px,calc((100% - 1250px)/2));display:flex;gap:18px;align-items:center;justify-content:space-between;flex-wrap:wrap}
.bh-nav strong{font-size:19px}.bh-nav small{display:block;font-weight:normal;color:#cdd6ec}.bh-nav button{border:1px solid #ced6ef;color:white;background:transparent;padding:8px 16px;border-radius:8px;cursor:pointer}
.bh-container{max-width:1250px;margin:28px auto;padding:0 18px}.bh-card{padding:22px;border-radius:16px;background:white;box-shadow:0 2px 12px rgba(20,34,66,.08);margin-bottom:20px}
.bh-tabs{display:flex;flex-wrap:wrap;gap:10px;margin:16px 0}.bh-tabs a{padding:9px 15px;background:#f0f3fa;color:#142458;border-radius:8px;text-decoration:none;font-weight:700}.bh-tabs a.active{background:#ffce19}
.bh-table{width:100%;border-collapse:collapse;min-width:850px}.bh-table th,.bh-table td{text-align:left;padding:12px;vertical-align:top;border-bottom:1px solid #e5e9f2}.bh-table th{background:#f7f9fc;font-size:13px}.bh-table input,.bh-table textarea{font:inherit;width:100%;min-width:170px;border:1px solid #ccd4e4;border-radius:7px;padding:7px}.bh-table button{padding:8px 12px;border:0;border-radius:8px;font-weight:700;cursor:pointer}.bh-ok{background:#daf8e7;color:#146534}.bh-no{background:#ffe4e6;color:#a12232}.bh-table a{color:#0b59ce}
.bh-table form{display:flex;gap:7px;flex-direction:column}.bh-table button{background:#17275e;color:white}.bh-errors{background:#ffe4e6;padding:14px;border-radius:8px;color:#9a2235}.bh-success{background:#d7f9e8;padding:14px;border-radius:8px;color:#14532d}
@media(max-width:767px){.bh-container{margin:15px auto;padding:0 10px}.bh-card{padding:16px}.bh-nav{padding:14px}}
</style>
@endpush
@section('content')

 <div class="bh-card"><h1 style="margin:0">Pembayaran Iuran Anggota</h1><p style="color:#63728c">Verifikasi bukti transfer sebelum status anggota menjadi Lunas.</p><p><b>{{ $menunggu }}</b> bukti menunggu pemeriksaan</p>
  <nav class="bh-tabs" aria-label="Filter status">
    @foreach(['menunggu_verifikasi'=>'Menunggu', 'lunas'=>'Lunas', 'ditolak'=>'Ditolak', 'semua'=>'Semua'] as $kode=>$label)
      <a class="{{ $status === $kode ? 'active' : '' }}" href="{{ route('bendahara.iuran.index', ['status'=>$kode]) }}">{{ $label }}</a>
    @endforeach
  </nav>
  @if(session('success'))<div class="bh-success">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="bh-errors"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
  <div style="overflow-x:auto"><table class="bh-table"><thead><tr><th>Anggota</th><th>Periode</th><th>Nominal</th><th>Bukti</th><th>Status</th><th>Keputusan</th></tr></thead><tbody>
    @forelse($riwayat as $bayar)
      <tr>
        <td><b>{{ $bayar->anggota?->nama_lengkap ?? 'Anggota tidak ditemukan' }}</b><br><small>{{ $bayar->anggota?->nim ?? '-' }}</small></td>
        <td>{{ $bayar->bulan }}/{{ $bayar->tahun }}<br><small>{{ $bayar->created_at->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') }}</small></td>
        <td>Rp {{ number_format($bayar->nominal,0,',','.') }}</td>
        <td><a href="{{ route('iuran.bukti.lihat', $bayar) }}" target="_blank" rel="noopener">Lihat</a> · <a href="{{ route('iuran.bukti.unduh', $bayar) }}">Unduh</a></td>
        <td><span class="himsi-iuran-status {{ $bayar->status === 'lunas' ? 'lunas' : ($bayar->status === 'ditolak' ? 'ditolak' : 'pending') }}">{{ str_replace('_',' ',ucfirst($bayar->status)) }}</span>
           @if($bayar->catatan_bendahara)<p style="max-width:200px;overflow-wrap:anywhere">{{ $bayar->catatan_bendahara }}</p>@endif
        </td>
        <td>
          @if($bayar->status === 'menunggu_verifikasi')
           <form method="POST" action="{{ route('bendahara.iuran.putuskan', $bayar) }}">
            @csrf
            <label>Catatan untuk penolakan<textarea name="catatan" rows="2" maxlength="2000" placeholder="Wajib jika ditolak"></textarea></label>
            <button name="keputusan" value="lunas" type="submit" data-confirm="Sudah periksa bukti transfer dan ingin menetapkan Lunas?">Verifikasi Lunas</button>
            <button name="keputusan" value="ditolak" type="submit" style="background:#ae2638" data-confirm="Tolak bukti transfer ini?">Tolak Bukti</button>
           </form>
          @else
           <small>Sudah diputuskan</small>
          @endif
        </td>
      </tr>
    @empty<tr><td colspan="6">Tidak ada pembayaran untuk filter ini.</td></tr>@endforelse
  </tbody></table></div>
  <div style="margin-top:18px">{{ $riwayat->links() }}</div>
 </div>
 <p style="color:#66758e;font-size:13px">Halaman ini merupakan verifikasi dalam aplikasi Administrasi Himpunan. Sinkronisasi dengan aplikasi Keuangan HIMSI yang terpisah memerlukan integrasi lanjutan.</p>

@endsection
@push('scripts')

@endpush
