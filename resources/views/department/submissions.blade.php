@extends('department.layout')
@section('title', ['letter'=>'Surat Proposal','budget'=>'RAB & Anggaran','report'=>'LPJ','rpk'=>'PPRK'][$type])
@section('content')
<div class="k-heading">
<div>
<div class="k-eyebrow">{{ $departmentLabel }}</div>
<h1>{{ ['letter'=>'Surat Proposal','budget'=>'RAB & Anggaran','report'=>'Laporan Pertanggungjawaban','rpk'=>'PPRK'][$type] }}</h1>
<p>{{ ['rpk'=>'Verifikasi PPRK sebelum pelaksanaan program kerja.','letter'=>'Pantau surat dan persetujuan kegiatan departemen.','budget'=>'Bandingkan rencana biaya dengan pengeluaran yang tercatat.','report'=>'Unggah LPJ setelah progres 100%. Setelah persetujuan Ketua Umum, dokumen diverifikasi Koordinator Pengawas Himpunan dan diarsipkan Sekertaris.'][$type] }}</p>
</div>
<button class="btn o-btn-yellow" type="button" data-bs-toggle="modal" data-bs-target="#chooseProgram">
<i class="bi bi-plus-lg" aria-hidden="true">
</i>{{ $type==='report' ? ' Upload LPJ' : ' Buat Pengajuan' }}</button>
</div>
@if($type==='letter')<div class="o-stats o-five">@foreach(['Total Surat'=>$counts->sum(), 'Disetujui'=>$counts['approved']??0, 'Dikirim'=>$counts['sent']??0, 'Sedang Diproses'=>($counts['pending_secretary']??0)+($counts['pending_chair']??0), 'Ditolak'=>$counts['rejected']??0] as $label=>$value)<article class="o-stat">
<div>
<small>{{ $label }}</small>
<strong>{{ $value }}</strong>
</div>
</article>@endforeach</div>@endif
@if($type==='budget')<div class="o-stats o-three">@foreach(['approved'=>'Total Anggaran Disetujui','pending_treasurer'=>'Menunggu Review','revision'=>'Perlu Revisi'] as $status=>$label)<article class="o-stat">
<div>
<small>{{ $label }}</small>
<strong class="o-money">Rp {{ number_format($totals[$status],0,',','.') }}</strong>
</div>
</article>@endforeach</div>
<div class="k-tabs">
<a class="{{ request('tab')!=='expenses' ? 'active':'' }}" href="{{ route('department.submissions',[$departmentKey,'budget']) }}">RAB</a>
<a class="{{ request('tab')==='expenses' ? 'active':'' }}" href="{{ route('department.submissions',[$departmentKey,'budget','tab'=>'expenses']) }}">Pengeluaran Anggaran</a>
</div>@endif
<div class="mb-4"><a class="btn btn-outline-navy" href="{{ route('department.document.template',[$departmentKey,$type]) }}"><i class="bi bi-download"></i> Unduh template {{ config('department.types')[$type] }}</a></div>
@if($type==='budget')
<div class="o-split">
<aside class="k-panel o-selector">
<h2 class="mb-3">Program Kerja</h2>@forelse($programs as $program)<a class="{{ $selected?->id === $program->id ? 'active':'' }}" href="{{ route('department.submissions',[$departmentKey,'budget','program'=>$program->id,'tab'=>request('tab','rab')]) }}">
<i class="bi bi-calendar-event" aria-hidden="true">
</i>
<span>{{ $program->title }}<small>{{ $program->period }}</small>
</span>
</a>@empty<p>Belum ada proker.</p>@endforelse</aside>
<section class="k-panel">
<div class="k-panel-head">
<h2>{{ $selected?->title ?? 'Pilih Program Kerja' }}</h2>
</div>
@if(request('tab')==='expenses')
@if(!$expenseData['connected'])<div class="k-empty">
<i class="bi bi-link-45deg" aria-hidden="true">
</i>
<h2>Data pengeluaran belum terhubung</h2>
<p>Transaksi akan tampil setelah sumber data keuangan dihubungkan ke proker dan departemen ini.</p>
</div>@else<p>Pengeluaran aktual: <strong>Rp {{ number_format($expenseData['total'],0,',','.') }}</strong>
</p>
<div class="table-responsive">
<table class="table k-table">
<thead>
<tr>
<th>Tanggal</th>
<th>Uraian</th>
<th>Nominal</th>
</tr>
</thead>
<tbody>@forelse($expenseData['rows'] as $expense)<tr>
<td>{{ $expense->date }}</td>
<td>{{ $expense->description }}</td>
<td>Rp {{ number_format($expense->amount,0,',','.') }}</td>
</tr>@empty<tr>
<td colspan="3">Belum ada pengeluaran tercatat untuk proker ini.</td>
</tr>@endforelse</tbody>
</table>
</div>@include('department.pagination',['items'=>$expenseData['rows']])@endif
@else @if($selectedBudget)@include('department.status',['status'=>$selectedBudget->status,'kind'=>'submission'])<div class="mt-3">@include('department.submission-details',['submission'=>$selectedBudget])</div>@if($selectedBudget->note)<p class="mt-3">Catatan Bendahara: {{ $selectedBudget->note }}</p>@endif @else<p>RAB belum diunggah.</p>@endif @if($selected)<a class="btn btn-navy mt-3" href="{{ route('department.submission.edit',[$departmentKey,$selected->id,'budget']) }}">Lihat / Kelola RAB</a>@endif @endif
</section>
</div>
@else
<form class="o-filters" method="GET">
<label class="visually-hidden" for="status">Filter status</label>
<select class="form-select" id="status" name="status">
<option value="">Semua Status</option>@foreach(config('department.submission_statuses') as $status=>$label)<option value="{{ $status }}" @selected(request('status')===$status)>{{ $label }}</option>@endforeach</select>
<button class="btn btn-navy" type="submit">Terapkan</button>
</form>
<section class="k-panel">
<div class="table-responsive">
<table class="table k-table">
<thead>
<tr>
<th>Program Kerja</th>
<th>Dokumen</th>
<th>Status</th>
<th>Diperbarui</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>@forelse($items as $item)<tr>
<td>
<strong>{{ $item->program->title }}</strong>
</td>
<td>{{ $item->file_name ?? 'Dokumen belum diunggah' }}</td>
<td>@include('department.status',['status'=>$item->status,'kind'=>'submission'])</td>
<td class="text-nowrap">{{ $item->updated_at->timezone('Asia/Makassar')->format('d M Y') }}</td>
<td>
<a class="btn btn-sm btn-outline-navy" href="{{ route('department.submission.edit',[$departmentKey,$item->program_id,$type]) }}">Lihat</a>
</td>
</tr>@empty<tr>
<td colspan="5">
<div class="k-empty">
<i class="bi bi-inbox" aria-hidden="true">
</i>
<p>Belum ada pengajuan sesuai filter.</p>
</div>
</td>
</tr>@endforelse</tbody>
</table>
</div>@include('department.pagination',['items'=>$items])</section>
@endif
<div class="modal fade" id="chooseProgram" tabindex="-1" aria-labelledby="chooseProgramTitle" aria-hidden="true">
<div class="modal-dialog modal-dialog-centered">
<div class="modal-content">
<div class="modal-header">
<h2 id="chooseProgramTitle" class="modal-title fs-5">Pilih Program Kerja</h2>
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup">
</button>
</div>
<form data-navigate>
<div class="modal-body">
<label class="form-label" for="program-url">Program kerja terkait</label>
<select class="form-select" id="program-url" data-navigate-url required>
<option value="">Pilih proker</option>@foreach($programs as $program)<option value="{{ route('department.submission.edit',[$departmentKey,$program->id,$type]) }}">{{ $program->title }}</option>@endforeach</select>
<small>Pengajuan mengikuti tahap persetujuan proker.</small>
</div>
<div class="modal-footer">
<button class="btn btn-navy" type="submit">Lanjutkan</button>
</div>
</form>
</div>
</div>
</div>
@endsection
