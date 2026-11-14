<section class="admin-panel chair-registration">
    <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
        <div><small class="text-secondary">PEMILIHAN HIMSI</small><h2 class="h4 mt-2">Pendaftaran Ketua Umum</h2><p>Lengkapi identitas, visi dan misi, serta berkas persyaratan untuk diperiksa panitia KPUM.</p></div>
        <span class="badge {{ $chairElection?->status === 'draft' && $chairElection?->registration_open ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $chairElection?->status === 'draft' && $chairElection?->registration_open ? 'Pendaftaran dibuka' : 'Pendaftaran ditutup' }}</span>
    </div>
    @if($chairCandidate)
        <div class="chair-status-grid mb-3"><div class="{{ $chairCandidate->status === 'approved' ? 'success' : 'pending' }}"><i class="bi bi-shield-check"></i><div><span>Status Verifikasi</span><strong>{{ ['approved'=>'Lolos Verifikasi','rejected'=>'Belum Lolos Verifikasi','pending'=>'Menunggu Verifikasi'][$chairCandidate->status] }}</strong></div></div><div><i class="bi bi-hash"></i><div><span>Nomor Urut</span><strong>{{ $chairCandidate->number ? str_pad($chairCandidate->number,2,'0',STR_PAD_LEFT) : 'Belum ditetapkan' }}</strong></div></div></div>
        @if($chairCandidate->review_note)<p class="text-secondary">{{ $chairCandidate->review_note }}</p>@endif
    @endif
    @if(auth()->user()->assignedRole() === 'kbh' && $chairElection?->status === 'draft' && $chairElection?->registration_open && (!$chairCandidate || $chairCandidate->status === 'rejected'))
        <a class="btn btn-navy" href="{{ route('kpum.registration.create') }}"><i class="bi bi-person-plus me-2"></i>{{ $chairCandidate ? 'Perbaiki Pendaftaran' : 'Daftar sebagai Ketua Umum' }}</a>
    @endif
</section>
