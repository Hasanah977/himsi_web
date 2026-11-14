@extends('department.layout')
@section('title', 'Rapat Himpunan')
@section('content')
<div class="k-heading"><div><div class="k-eyebrow">KOORDINASI HIMSI</div><h1>Rapat Himpunan</h1><p>Undangan, kehadiran, dan riwayat hasil rapat dalam satu tempat.</p></div></div>
@if($canManage)
<section class="k-panel">
    <h2 class="h5 mb-3">Buat Undangan Rapat</h2>
    <form method="POST" action="{{ route('meetings.store') }}">@csrf
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="title">Judul atau agenda rapat</label><input class="form-control" id="title" name="title" maxlength="160" value="{{ old('title') }}" required></div>
            <div class="col-md-6"><label class="form-label" for="category">Kategori rapat</label><select class="form-select" id="category" name="category" required>@foreach(\App\Models\Meeting::CATEGORIES as $value => $label)<option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label" for="scheduled_at">Tanggal dan waktu (WITA)</label><input class="form-control" type="datetime-local" id="scheduled_at" name="scheduled_at" value="{{ old('scheduled_at') }}" required></div>
            <div class="col-md-6"><label class="form-label" for="location">Tempat rapat</label><input class="form-control" id="location" name="location" maxlength="200" value="{{ old('location') }}" required></div>
        </div>
        <p class="small text-secondary mt-3" id="meeting-recipients">Bulanan dan triwulan: seluruh pengurus. Pengurus inti: Ketua Umum, Bendahara, Sekertaris, dan seluruh Koordinator Departemen. Presidium: Ketua Umum, Bendahara, dan Sekertaris.</p>
        <button class="btn btn-navy" type="submit"><i class="bi bi-send" aria-hidden="true"></i> Kirimkan Undangan Rapat</button>
    </form>
</section>
@endif
<section class="k-panel">
    <div class="k-panel-head"><h2>Undangan dan Riwayat Rapat</h2>@if($canManage)<a class="btn btn-outline-navy" href="{{ route('meetings.export', request()->only(['category', 'from', 'to'])) }}"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Ekspor Rekap PDF</a>@endif</div>
    <form class="row g-3 mb-4" method="GET" action="{{ route('meetings.index') }}">
        <div class="col-md-4"><label class="form-label" for="filter-category">Kategori</label><select id="filter-category" name="category" class="form-select"><option value="">Semua kategori</option>@foreach(\App\Models\Meeting::CATEGORIES as $value => $label)<option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="from">Dari tanggal</label><input class="form-control" type="date" id="from" name="from" value="{{ request('from') }}"></div>
        <div class="col-md-3"><label class="form-label" for="to">Sampai tanggal</label><input class="form-control" type="date" id="to" name="to" value="{{ request('to') }}"></div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-navy w-100">Tampilkan</button></div>
    </form>
    <div class="table-responsive"><table class="table k-table"><thead><tr><th>Agenda</th><th>Kategori</th><th>Jadwal (WITA)</th><th>Status</th><th>Kehadiran</th><th></th></tr></thead><tbody>
    @forelse($meetings as $meeting)
        <tr><td>{{ $meeting->title }}<small class="d-block text-secondary">{{ $meeting->location }}</small></td><td>{{ \App\Models\Meeting::CATEGORIES[$meeting->category] ?? ucfirst(str_replace('_', ' ', $meeting->category)) }}</td><td>{{ $meeting->scheduled_at->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') }}</td><td>{{ \App\Models\Meeting::STATUSES[$meeting->status] ?? ucfirst(str_replace('_', ' ', $meeting->status)) }}</td><td>{{ $meeting->present_count }} / {{ $meeting->attendances_count }} hadir</td><td><a class="btn btn-outline-navy btn-sm" href="{{ route('meetings.show', $meeting) }}">Buka Rapat</a></td></tr>
    @empty<tr><td colspan="6" class="text-center py-5">Belum ada rapat yang sesuai dengan filter.</td></tr>@endforelse
    </tbody></table></div>
    {{ $meetings->links() }}
</section>
@endsection
