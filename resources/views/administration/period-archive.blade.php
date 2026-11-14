@extends(match(request()->attributes->get('administration_preview_role', auth()->user()->role)) {
    'super_admin' => 'admin.layout',
    'panitia_cb' => 'cb.layout',
    'panitia_kpum' => 'kpum.layout',
    'koordinator_departemen', 'anggota_departemen' => 'department.layout',
    default => 'administration.layout',
})
@section('title','Arsip Kepengurusan')
@section('content')
<div class="admin-panel"><h2>Arsip Kepengurusan</h2><p>Dokumen periode lama hanya dapat dilihat. Tindak lanjut dicatat terpisah pada periode aktif.</p>
<form method="GET" class="d-flex gap-2 mb-4 flex-wrap"><select class="form-select w-auto" name="type">
@foreach(\App\Support\Governance::SOURCES as $key => $source)
@php($allowed = auth()->user()->isSuperAdmin() || auth()->user()->role === 'ketua_umum' || in_array($key, match(auth()->user()->role) { 'sekretaris_umum'=>['surat-masuk','surat-keluar','pengajuan','pprk','laporan'], 'bendahara_umum'=>['keuangan','pengajuan','pprk','laporan'], default=>['program','pengajuan','tugas','pprk'] }))
@if($allowed)<option value="{{ $key }}" @selected($type===$key)>{{ $source[1] }}</option>@endif
@endforeach</select><select class="form-select w-auto" name="period">@foreach($periods as $item)<option value="{{ $item->id }}" @selected($period?->id===$item->id)>{{ $item->name }}</option>@endforeach</select><button class="btn btn-navy">Tampilkan</button></form>
@forelse($records as $record)<article class="border rounded p-3 mb-3"><h3 class="fs-5">{{ $record->title ?? $record->perihal ?? $record->name ?? $record->description ?? ('Dokumen #'.$record->id) }}</h3><span class="badge text-bg-secondary">Hanya baca</span>
<details class="my-3"><summary>Detail arsip</summary><dl>@foreach($record->getAttributes() as $key=>$value)<dt>{{ str_replace('_',' ',$key) }}</dt><dd class="text-break">{{ $value ?? '—' }} @if($value && preg_match('/^(file.*|.*_path|lampiran)$/', $key) && !in_array($key,['file_name']))<a href="{{ route('governance.file',[$type,$record->id,$key]) }}" class="btn btn-sm btn-outline-navy">Unduh berkas</a>@endif</dd>@endforeach</dl></details>
@foreach($followups->get($record->id, collect()) as $note)<div class="alert alert-light">{{ $note->note }}<small class="d-block">Periode #{{ $note->period_id }} · Pembuat #{{ $note->author_id }} · {{ $note->created_at }}</small></div>@endforeach
@if(\App\Support\Governance::active() && !auth()->user()->isSuperAdmin())<form method="POST" action="{{ route('governance.followup',[$type,$record->id]) }}">@csrf<label class="form-label">Catatan tindak lanjut periode aktif</label><textarea class="form-control mb-2" name="note" required maxlength="5000"></textarea><button class="btn btn-outline-navy">Simpan Catatan Baru</button></form>@endif
</article>@empty<p>Belum ada arsip untuk periode ini.</p>@endforelse
{{ $records->links() }}</div>@endsection