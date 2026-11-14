@extends('administration.layout')
@section('title', 'Detail LPJ')
@section('content')
<a class="btn btn-outline-navy mb-4" href="{{ route('officer-reports.index') }}"><i class="bi bi-arrow-left me-2"></i>Kembali ke LPJ</a>
<section class="admin-panel">
    <h2>{{ $report->title }}</h2><p class="text-secondary">{{ $report->user->name }} · {{ config('superadmin.roles.'.$report->role) }} · {{ $report->period }}</p>
    <p><strong>Status:</strong> {{ \App\Models\OfficerReport::Statuses[$report->status] }}</p>
    <a class="btn btn-outline-navy" href="{{ route('officer-reports.file', $report) }}"><i class="bi bi-download me-2"></i>{{ $report->file_name }}</a>
    @if($report->note)<div class="alert alert-info mt-4 mb-0"><strong>Catatan Pengawas:</strong><p class="mb-0" style="white-space:pre-wrap">{{ $report->note }}</p></div>@endif
</section>
@if(auth()->user()->role === 'koordinator_pengawas' && $report->user_id !== auth()->id() && $report->status === 'pending_supervisor')
<form class="admin-panel" action="{{ route('officer-reports.review', $report) }}" method="POST">
    @csrf<input type="hidden" name="version" value="{{ $report->version }}">
    <h3 class="h5 mb-3">Verifikasi LPJ</h3>
    <label class="form-label" for="status">Keputusan</label><select class="form-select mb-3" name="status" id="status" required><option value="">Pilih keputusan</option>@foreach(['approved' => 'Setujui / Verifikasi', 'revision' => 'Minta Revisi', 'rejected' => 'Tolak'] as $value => $label)<option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>@endforeach</select>
    <label class="form-label" for="note">Catatan pemeriksaan</label><textarea class="form-control mb-3" name="note" id="note" rows="4" maxlength="3000" required>{{ old('note') }}</textarea><button class="btn btn-warning">Simpan Verifikasi</button>
</form>
@endif
@if($report->user_id === auth()->id() && in_array(auth()->user()->role, \App\Models\OfficerReport::SubmitterRoles, true) && in_array($report->status, ['revision', 'rejected'], true))
<form class="admin-panel" action="{{ route('officer-reports.update', $report) }}" method="POST" enctype="multipart/form-data">
    @csrf @method('PUT')<input type="hidden" name="version" value="{{ $report->version }}"><h3 class="h5 mb-3">Upload Revisi LPJ</h3><label class="form-label" for="file">Dokumen pengganti (PDF / Word, maksimal 25 MB)</label><input class="form-control mb-3" type="file" name="file" id="file" accept=".pdf,.doc,.docx" required><button class="btn btn-warning">Kirim Ulang untuk Verifikasi</button>
</form>
@endif
<section class="admin-panel"><h3 class="h5 mb-3">Riwayat LPJ</h3>@foreach(array_reverse($report->history ?? []) as $entry)<div class="border-bottom py-3"><strong>{{ $entry['action'] }}</strong><small class="d-block text-secondary">{{ $entry['actor'] }} · {{ \Illuminate\Support\Carbon::parse($entry['at'])->format('d M Y H:i') }}</small>@if($entry['note'])<p class="mb-0 mt-2">{{ $entry['note'] }}</p>@endif</div>@endforeach</section>
@endsection
