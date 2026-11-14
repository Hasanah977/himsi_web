@extends('department.layout')
@section('title', 'Rapat Himpunan')
@push('scripts')
@vite('resources/js/meetings.js')
@endpush
@section('content')
<div class="k-heading"><div><div class="k-eyebrow">{{ \App\Models\Meeting::CATEGORIES[$meeting->category] ?? ucfirst(str_replace('_', ' ', $meeting->category)) }}</div><h1>{{ $meeting->title }}</h1><p>{{ $meeting->scheduled_at->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i') }} WITA · {{ $meeting->location }}</p></div><a class="btn btn-outline-navy" href="{{ route('meetings.index') }}">Kembali</a></div>
<section class="k-panel"><div class="d-flex justify-content-between flex-wrap gap-3"><strong>{{ \App\Models\Meeting::STATUSES[$meeting->status] ?? ucfirst(str_replace('_', ' ', $meeting->status)) }}</strong><span>{{ $meeting->attendances->count() }} peserta diundang</span></div>
@if($canManage && $meeting->status === 'scheduled')
    <p class="mt-3">Buka absensi ketika peserta siap mengisi kehadiran. Kode QR dan kode kehadiran berganti setiap dua menit.</p>
    <form method="POST" action="{{ route('meetings.open', $meeting) }}">@csrf<button class="btn btn-navy">Mulai Absen</button></form>
@endif
@if($myAttendance)<p class="mt-3 mb-0">Kehadiran Anda: <strong>{{ \App\Models\MeetingAttendance::STATUSES[$myAttendance->status] ?? ucfirst(str_replace('_', ' ', $myAttendance->status)) }}</strong></p>@endif
</section>
@if($canManage && $meeting->status === 'open')
<section class="k-panel" data-meeting-live="{{ route('meetings.live', $meeting) }}">
    <h2 class="h5">Absensi Rapat</h2>
    <div class="row align-items-center g-4"><div class="col-md-5 text-center"><canvas id="meeting-qr" style="max-width:100%;height:auto" aria-label="Kode QR kehadiran rapat" hidden></canvas></div><div class="col-md-7">
        <p>Pindai kode QR melalui halaman rapat peserta atau masukkan kode berikut.</p><strong id="meeting-code" class="fs-2 font-monospace" style="letter-spacing:.12em">Memuat…</strong>
        <p id="meeting-countdown" class="mt-2" aria-live="polite"></p><p id="meeting-live-error" class="text-danger" role="status"></p>
        <form method="POST" action="{{ route('meetings.close', $meeting) }}" data-k-confirm="Tutup absensi rapat? Peserta yang belum mengisi akan dicatat tidak hadir.">@csrf<button class="btn btn-outline-danger">Tutup Absen</button></form>
    </div></div>
</section>
@endif
@if($meeting->status === 'open' && $myAttendance && $myAttendance->status !== 'present' && $myAttendance->source !== 'manual')
<section class="k-panel">
    <h2 class="h5 mb-3">Isi Kehadiran</h2>
    <form method="POST" action="{{ route('meetings.attend', $meeting) }}" id="meeting-attend-form">@csrf
        <input type="hidden" name="source" id="attendance-source" value="code">
        <label class="form-label" for="attendance-code">Kode kehadiran dari Sekertaris</label>
        <div class="d-flex gap-2 flex-wrap"><input class="form-control font-monospace text-uppercase" style="max-width:300px" id="attendance-code" name="code" pattern="[A-Fa-f0-9]{10}" minlength="10" maxlength="10" autocomplete="off" required><button class="btn btn-navy" type="submit">Catat Kehadiran</button><button class="btn btn-outline-navy" type="button" id="start-meeting-scanner"><i class="bi bi-qr-code-scan" aria-hidden="true"></i> Pindai QR</button><button class="btn btn-outline-secondary" type="button" id="stop-meeting-scanner" hidden>Tutup Kamera</button></div>
    </form>
    <div id="meeting-scanner" style="max-width:480px" class="mt-3"></div><p class="small text-secondary mt-2" id="meeting-scanner-message" role="status">Izinkan akses kamera saat memindai. Jika kamera tidak tersedia, masukkan kode kehadiran.</p>
</section>
@endif
<section class="k-panel">
    <h2 class="h5 mb-3">Daftar Kehadiran Peserta</h2>
    <div class="table-responsive"><table class="table k-table"><thead><tr><th>Peserta</th><th>Jabatan</th><th>Status</th><th>Waktu Hadir</th>@if($canManage && $meeting->status === 'open')<th>Absensi Manual</th>@endif</tr></thead><tbody>
    @foreach($meeting->attendances->sortBy('participant_name') as $attendance)
        <tr data-attendance-id="{{ $attendance->id }}"><td>{{ $attendance->participant_name }}</td><td>{{ config('superadmin.roles.'.$attendance->participant_role) }}<small class="d-block text-secondary">{{ config('superadmin.departments.'.$attendance->participant_department, '') }}</small></td><td data-attendance-status>{{ \App\Models\MeetingAttendance::STATUSES[$attendance->status] ?? ucfirst(str_replace('_', ' ', $attendance->status)) }}</td><td data-attendance-time>{{ $attendance->attended_at?->copy()->timezone('Asia/Makassar')->format('H:i:s') ?? '—' }}</td>
        @if($canManage && $meeting->status === 'open')<td><form method="POST" action="{{ route('meetings.manual', [$meeting, $attendance->id]) }}" class="d-flex gap-2" data-manual-attendance>@csrf<input type="hidden" name="version" value="{{ $attendance->version }}"><select class="form-select form-select-sm" name="status" aria-label="Kehadiran {{ $attendance->participant_name }}" required><option value="">Pilih status</option>@foreach(['present'=>'Hadir', 'excused'=>'Izin', 'absent'=>'Tidak Hadir'] as $value => $label)<option value="{{ $value }}" @selected($attendance->status === $value)>{{ $label }}</option>@endforeach</select><button class="btn btn-sm btn-outline-navy">Simpan</button></form></td>@endif</tr>
    @endforeach
    </tbody></table></div>
</section>
@if($canManage && $meeting->status === 'closed')
<form class="k-panel" method="POST" action="{{ route('meetings.minutes', $meeting) }}">@csrf<h2 class="h5 mb-3">Kesimpulan dan Notula Rapat</h2><label class="form-label" for="minutes">Catat hasil pembahasan, keputusan, dan tindak lanjut rapat</label><textarea class="form-control" name="minutes" id="minutes" rows="10" maxlength="30000" required>{{ old('minutes') }}</textarea><p class="small text-secondary mt-2">Notula yang disimpan akan menjadi riwayat rapat dan dapat dibaca seluruh peserta undangan.</p><button class="btn btn-navy mt-2">Simpan Notula dan Selesaikan Rapat</button></form>
@endif
@if($meeting->minutes)<section class="k-panel"><h2 class="h5 mb-3">Kesimpulan dan Notula Rapat</h2><div style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $meeting->minutes }}</div></section>@endif
@endsection
