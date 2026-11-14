@extends('admin.layout')
@section('title', 'Pusat Kendali Darurat')
@section('content')
<div class="admin-toolbar security-heading"><div><span class="security-eyebrow">KEAMANAN SISTEM</span><h1>Pusat Kendali Darurat</h1><p>Batasi akses, amankan data, dan telusuri aktivitas dari satu tempat.</p></div><a class="btn btn-outline-navy" href="{{ route('admin.security.logs', ['severity'=>'danger']) }}"><i class="bi bi-shield-exclamation me-2"></i>{{ $dangerCount }} peringatan · 24 jam</a></div>
<div class="security-grid">
    <section class="admin-panel security-control {{ $state['maintenance'] ? 'security-control-active' : '' }}">
        <div class="security-card-heading"><span class="security-icon"><i class="bi bi-power"></i></span><span class="badge {{ $state['maintenance'] ? 'bg-danger' : 'bg-success' }}">{{ $state['maintenance'] ? 'Publik ditutup' : 'Publik terbuka' }}</span></div>
        <h2>Emergency Panic Button</h2><p>Satu klik untuk menutup halaman publik dan menampilkan halaman perbaikan. Login serta panel internal tetap tersedia.</p>
        <form method="POST" action="{{ route('admin.security.maintenance') }}">@csrf<input type="hidden" name="enabled" value="{{ $state['maintenance'] ? '0' : '1' }}"><button class="btn {{ $state['maintenance'] ? 'btn-outline-navy' : 'btn-danger' }} w-100" type="submit"><i class="bi bi-{{ $state['maintenance'] ? 'globe2' : 'power' }} me-2"></i>{{ $state['maintenance'] ? 'Buka kembali halaman publik' : 'Tutup akses publik sekarang' }}</button></form>
    </section>
    <section class="admin-panel security-control {{ $state['frozen'] ? 'security-control-active' : '' }}">
        <div class="security-card-heading"><span class="security-icon"><i class="bi bi-person-lock"></i></span><span class="badge {{ $state['frozen'] ? 'bg-danger' : 'bg-success' }}">{{ $state['frozen'] ? 'Akses dibekukan' : 'Login terbuka' }}</span></div>
        <h2>Force Logout & Freeze Session</h2><p>Akhiri sesi dan bekukan login seluruh akun selain Super Admin. Setelah dibuka kembali, semua pengguna perlu login ulang.</p>
        <form method="POST" action="{{ route($state['frozen'] ? 'admin.security.unfreeze' : 'admin.security.freeze') }}">@csrf<button class="btn {{ $state['frozen'] ? 'btn-outline-navy' : 'btn-danger' }} w-100" type="submit"><i class="bi bi-{{ $state['frozen'] ? 'unlock' : 'lock' }} me-2"></i>{{ $state['frozen'] ? 'Buka kembali akses login' : 'Logout & bekukan semua role' }}</button></form>
    </section>
</div>
<section class="admin-panel">
    <div class="security-section-heading"><div><h2>Backup & Restore Database</h2><p>Cadangan lokal terenkripsi, tersimpan di luar folder publik.</p></div><form method="POST" action="{{ route('admin.security.backup') }}">@csrf<button type="submit" class="btn btn-navy"><i class="bi bi-database-add me-2"></i>Buat backup sekarang</button></form></div>
    <div class="security-note"><i class="bi bi-info-circle"></i><span>Backup mencakup data organisasi dalam database. Berkas unggahan dan kode tidak termasuk. Sesi, cache, antrean pekerjaan, serta log keamanan tidak dipulihkan. Simpan kunci aplikasi (.env) secara aman agar cadangan tetap dapat dibuka.</span></div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Waktu backup (WITA)</th><th>Jenis</th><th>Isi</th><th>Ukuran</th><th class="text-end">Aksi</th></tr></thead><tbody>
    @forelse($backups as $backup)
        <tr><td>{{ \Carbon\Carbon::parse($backup['created_at'])->timezone('Asia/Makassar')->format('d M Y · H:i:s') }}</td><td><span class="badge text-bg-light">{{ $backup['reason']==='before_restore' ? 'Pengaman sebelum restore' : 'Manual' }}</span></td><td>{{ $backup['tables'] }} tabel · {{ number_format($backup['rows']) }} baris</td><td>{{ number_format(($backup['bytes'] ?? 0)/1024, 1) }} KB</td><td><div class="d-flex gap-2 justify-content-end"><a class="btn btn-sm btn-outline-navy" href="{{ route('admin.security.download', $backup['id']) }}" aria-label="Unduh backup {{ $backup['created_at'] }}"><i class="bi bi-download"></i> Unduh</a><a class="btn btn-sm btn-outline-danger" href="{{ route('admin.security.restore.review', $backup['id']) }}">Restore</a></div></td></tr>
    @empty<tr><td colspan="5"><div class="workspace-empty"><i class="bi bi-database"></i><h3>Belum ada backup dari panel ini</h3><p>Buat cadangan pertama sebelum mulai mengisi data baru. Cadangan lama di luar panel tetap tersimpan terpisah.</p></div></td></tr>@endforelse
    </tbody></table></div>
</section>
<section class="admin-panel"><div class="security-section-heading"><div><h2>Peringatan terbaru</h2><p>Indikasi bahaya perlu diperiksa; penanda ini bukan bukti pasti peretasan.</p></div><a href="{{ route('admin.security.logs') }}" class="btn btn-outline-navy">Lihat log aktivitas</a></div>
@forelse($latestAlerts as $event)<div class="security-alert-row"><i class="bi bi-exclamation-triangle"></i><div><strong>{{ $event->summary }}</strong><p>{{ $event->actor_name ?? 'Pengunjung / akun tidak dikenal' }} · {{ $event->created_at->timezone('Asia/Makassar')->format('d M Y H:i:s') }}</p></div></div>@empty<p class="text-secondary mb-0">Belum ada peringatan bahaya yang tercatat.</p>@endforelse
</section>
@endsection
