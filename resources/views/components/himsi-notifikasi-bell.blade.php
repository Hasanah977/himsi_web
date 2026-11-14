@auth
@php
    $himsiNotif = \App\Models\NotifikasiHimsi::query()->where('user_id',auth()->id())->whereNull('dibaca_pada');
    $himsiJumlah = (clone $himsiNotif)->count();
    $himsiTerbaru = (clone $himsiNotif)->latest()->limit(7)->get();
@endphp
<style>
.himsi-bell-wrap{position:relative;flex:none;z-index:30}.himsi-bell-btn{width:45px;height:45px;border:0!important;background:transparent!important;color:#172454!important;display:grid;place-items:center;position:relative;cursor:pointer;border-radius:50%;font-size:25px}.himsi-bell-btn:hover{background:#edf2ff!important}.himsi-bell-count{position:absolute;top:0;right:-3px;min-width:20px;height:20px;padding:0 4px;border-radius:30px;background:#e11d48;color:white;font:700 11px/20px Arial;text-align:center;border:2px solid white;box-sizing:content-box}.himsi-bell-panel{display:none;position:absolute;right:0;top:53px;width:min(370px,calc(100vw - 28px));background:white;color:#172454;border-radius:12px;box-shadow:0 12px 40px #14213a35;border:1px solid #e2e8f0;z-index:5000;padding:12px;max-height:70vh;overflow:auto}.himsi-bell-panel.is-open{display:block}.himsi-bell-panel form{margin:0;border-bottom:1px solid #edf0f5}.himsi-bell-panel form:last-child{border-bottom:0}.himsi-bell-item{width:100%;display:block;text-align:left;background:white;color:#172454;border:0;padding:11px 8px;cursor:pointer;border-radius:7px}.himsi-bell-item:hover{background:#f5f7fc}.himsi-bell-item strong{display:block;font-size:13px}.himsi-bell-item small{display:block;color:#64748b;font-size:12px;font-weight:400;white-space:normal}
</style>
<div class="himsi-bell-wrap">
  <button type="button" class="himsi-bell-btn" aria-label="Notifikasi belum dibaca: {{ $himsiJumlah }}" aria-expanded="false" onclick="const p=this.nextElementSibling;const open=p.classList.toggle('is-open');this.setAttribute('aria-expanded',open);">
    <i class="bi bi-bell-fill" aria-hidden="true"></i>
    @if($himsiJumlah>0)<span class="himsi-bell-count">{{ $himsiJumlah > 99 ? '99+' : $himsiJumlah }}</span>@endif
  </button>
  <div class="himsi-bell-panel" role="region" aria-label="Daftar notifikasi">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px"><strong style="font-size:15px">Notifikasi</strong><small style="color:#64748b">{{ $himsiJumlah }} belum dibaca</small></div>
    @forelse($himsiTerbaru as $notif)
      <form action="{{ route('notifikasi-himsi.baca',$notif) }}" method="POST">@csrf
        <button class="himsi-bell-item" type="submit" title="Tandai dibaca dan buka">
          <strong>{{ $notif->judul }}</strong><small>{{ $notif->pesan }}</small>
          <small>{{ $notif->created_at->diffForHumans() }}</small>
        </button>
      </form>
    @empty
      <p style="font-size:13px;color:#64748b;margin:10px">Tidak ada notifikasi baru.</p>
    @endforelse
  </div>
</div>
@endauth
