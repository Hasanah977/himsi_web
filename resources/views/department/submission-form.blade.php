@extends('department.layout')
@section('title', config('department.types')[$type])
@section('content')
<div class="k-heading"><div><div class="k-eyebrow">DOKUMEN PROGRAM KERJA</div><h1>{{ config('department.types')[$type] }}</h1><p>{{ $program->title }}</p></div><a class="btn btn-outline-navy" href="{{ route('department.program.show', [$departmentKey, $program->id]) }}">Kembali ke Proker</a></div>
@php
    $stageAllowed = match($type) {
        'rpk' => $rpkUploadAllowed,
        'letter' => in_array($program->status, ['draft','revision','rejected']),
        'budget' => in_array($program->status, ['approved','running']),
        'report' => $program->status === 'running' && $program->tasks_count > 0 && $program->done_count === $program->tasks_count,
    };
    $editable = $stageAllowed && in_array($submission->status, ['draft','revision','rejected'], true);
@endphp
<section class="k-panel">
<div class="k-panel-head"><h2>Siapkan dokumen</h2>@if(config('department.templates.'.$type))<a class="btn btn-outline-navy btn-sm" href="{{ route('department.document.template', [$departmentKey, $type]) }}"><i class="bi bi-download" aria-hidden="true"></i> Unduh template {{ config('department.types')[$type] }}</a>@endif</div>
<p class="mb-0">{{ match($type) {'rpk' => 'Unggah PPRK setelah proposal dan RAB disetujui. Sekertaris, Bendahara, dan Ketua Umum memverifikasi, dilanjutkan Koordinator Pengawas, kemudian diarsipkan Sekertaris sebelum pelaksanaan.', 'letter' => 'Unggah proposal yang sudah disiapkan. Sekertaris memeriksa dan meneruskannya kepada Ketua Umum.', 'budget' => 'Unggah RAB untuk diperiksa Bendahara, disetujui Ketua Umum, lalu dicairkan Bendahara. Pelaksanaan proker terbuka setelah pencairan dan pengarsipan PPRK.', 'report' => 'LPJ tersedia setelah seluruh tugas selesai (100%). Ketua Umum dan Koordinator Pengawas memeriksa secara berurutan. Sekertaris mengarsipkan LPJ dan proker otomatis selesai.'} }}</p>
</section>
@if($submission->note)<div class="k-callout"><i class="bi bi-chat-left-text"></i><span><strong>Catatan pemeriksaan</strong><small>{{ $submission->note }}</small></span></div>@endif
@if(!$editable)
<section class="k-panel">@include('department.status', ['status'=>$submission->status,'kind'=>'submission'])
@if(!$stageAllowed)<p class="mt-3">{{ $type === 'report' ? 'Unggah LPJ terkunci sampai proker berjalan dan seluruh tugas mencapai 100% selesai.' : 'Pengajuan belum tersedia pada tahap proker ini.' }}</p>@else<p class="mt-3">Dokumen sedang ditinjau atau sudah disetujui. Unggah ulang tersedia bila diminta revisi atau ditolak.</p>@endif
@include('department.submission-details')</section>
@else
<form method="POST" enctype="multipart/form-data" class="k-panel" action="{{ route('department.submission.save', [$departmentKey, $program->id, $type]) }}">@csrf<input type="hidden" name="version" value="{{ $submission->version }}">
<h2 class="mb-4">Unggah {{ config('department.types')[$type] }}</h2>
<label class="form-label" for="document-file">Dokumen {{ config('department.types')[$type] }}</label>
<input class="form-control mb-2" type="file" id="document-file" name="file" accept=".pdf,.doc,.docx" @required(!$submission->file_path || in_array($submission->status,['revision','rejected']))>
<p class="small text-secondary mb-4">PDF atau Word (DOC/DOCX), maksimal {{ $type === 'report' ? 25 : 10 }} MB. Gunakan berkas terbaru saat mengirim perbaikan.</p>
@if($submission->file_path)<p>Berkas tersimpan: <a href="{{ route('department.submission.file',$submission->id) }}">{{ $submission->file_name }}</a></p>@endif
@if($type === 'budget')<label for="total" class="form-label">Total anggaran dalam dokumen (Rp)</label><input id="total" class="form-control mb-2" type="number" name="total" min="1" max="1000000000000" step="1" value="{{ old('total',$submission->total ?: '') }}" required><p class="small text-secondary mb-4">Isi sesuai total RAB tanpa titik pemisah. Bendahara memeriksa kesesuaiannya sebelum diteruskan kepada Ketua Umum.</p>@endif
<label class="form-label" for="summary">Catatan pengajuan <span class="text-secondary">(opsional)</span></label><textarea class="form-control" id="summary" name="summary" rows="3" maxlength="5000">{{ old('summary',$submission->payload['summary'] ?? '') }}</textarea>
<div class="o-actions"><button class="btn btn-outline-navy" name="action" value="draft" type="submit">Simpan Draft</button><button class="btn o-btn-yellow" name="action" value="submit" type="submit"><i class="bi bi-send"></i> Kirim ke {{ ['letter'=>'Sekertaris','budget'=>'Bendahara','report'=>'Ketua Umum','rpk'=>'Tiga Pengurus'][$type] }}</button></div>
</form>
@endif
@if($type === 'budget' && $submission->status === 'disbursed')
<section class="k-panel">
<h2 class="h5 mb-3">Pengembalian Dana RAB</h2>
<div class="row g-3 mb-4">@foreach(['Dana dicairkan' => $paid, 'Sudah dikembalikan' => $returned, 'Sisa dana yang dapat dikembalikan' => max(0, $paid - $returned)] as $label => $amount)<div class="col-md-4"><div class="border rounded-3 p-3"><span class="text-secondary">{{ $label }}</span><strong class="d-block fs-5 mt-1">Rp {{ number_format($amount, 0, ',', '.') }}</strong></div></div>@endforeach</div>
@if(auth()->user()->role === 'koordinator_departemen' && $paid > $returned)
<form method="POST" enctype="multipart/form-data" action="{{ route('department.budget.refund', [$departmentKey, $submission]) }}">@csrf
<input type="hidden" name="version" value="{{ $submission->version }}"><input type="hidden" name="reference" value="{{ old('reference', (string) Illuminate\Support\Str::uuid()) }}">
<div class="row g-4"><div class="col-md-6"><label for="return-amount" class="form-label">Dana yang dikembalikan (Rp)</label><input class="form-control" type="number" id="return-amount" name="amount" min="1" max="{{ $paid - $returned }}" step="1" value="{{ old('amount') }}" required></div>
<div class="col-md-6"><label for="return-date" class="form-label">Tanggal pengembalian</label><input class="form-control" type="date" id="return-date" name="occurred_on" min="{{ config('finance.start') }}" max="{{ min(now()->toDateString(), config('finance.end')) }}" value="{{ old('occurred_on', now()->toDateString()) }}" required></div>
<div class="col-md-6"><label for="return-proof" class="form-label">Bukti pengembalian</label><input class="form-control" type="file" id="return-proof" name="receipt" accept="application/pdf,image/jpeg,image/png,image/webp" required><small class="text-secondary">PDF, JPG, PNG, atau WebP · maksimal 5 MB.</small></div>
<div class="col-md-6"><label for="return-note" class="form-label">Catatan (opsional)</label><textarea class="form-control" id="return-note" name="note" rows="3" maxlength="2000">{{ old('note') }}</textarea></div></div>
<p class="text-secondary mt-4">Pengembalian tercatat pada kas dan Monitoring Anggaran Bendahara. Isi nominal pengembalian baru, bukan total kumulatif.</p><button class="btn btn-navy" type="submit">Simpan Pengembalian Dana</button></form>
@else<div class="alert alert-success mb-0">Seluruh dana sudah dikembalikan.</div>@endif
</section>
<section class="k-panel"><h2 class="h5 mb-3">Riwayat Dana</h2><div class="table-responsive"><table class="table"><thead><tr><th>Tanggal</th><th>Transaksi</th><th>Nominal</th><th>Bukti</th></tr></thead><tbody>@foreach($entries as $entry)<tr><td>{{ $entry->occurred_on->format('d M Y') }}</td><td>{{ $entry->category }}</td><td>Rp {{ number_format($entry->amount, 0, ',', '.') }}</td><td>@if($entry->receipt_path)<a href="{{ route('finance.receipt', $entry) }}" target="_blank" rel="noopener">Lihat bukti</a>@else—@endif</td></tr>@endforeach</tbody></table></div></section>
@endif
@endsection
