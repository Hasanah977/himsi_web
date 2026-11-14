@extends('department.layout')
@section('title', 'Tinjauan Pengajuan')
@section('content')
<div class="k-heading">
<div>
<div class="k-eyebrow">{{ config('superadmin.roles')[auth()->user()->role] }}</div>
<h1>Tinjauan Pengajuan Departemen</h1>
<p>Periksa dokumen dan berikan keputusan sesuai kewenangan akun.</p>
</div>
</div>
<form class="k-panel d-flex flex-wrap gap-2 align-items-end" method="GET">@if(request('type'))<input type="hidden" name="type" value="{{ request('type') }}">@endif<div><label for="status" class="form-label">Status dokumen</label><select name="status" id="status" class="form-select"><option value="">Semua status</option>@foreach(config('department.submission_statuses') as $value=>$label)@if($value !== 'draft')<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endif @endforeach</select></div><button class="btn btn-navy">Tampilkan</button>@if(auth()->user()->role==='sekretaris_umum')<a class="btn btn-outline-navy" href="{{ route('department.reviews',['status'=>'archived']) }}">Arsip LPJ dan PPRK</a>@endif</form>
<section class="k-panel">
<div class="table-responsive">
<table class="table k-table">
<thead>
<tr>
<th>Pengajuan</th>
<th>Program Kerja</th>
<th>Departemen</th>
<th>Status</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>@forelse($items as $item)<tr>
<td>{{ config('department.types')[$item->type] }}</td>
<td>{{ $item->program->title }}</td>
<td>{{ config('superadmin.departments')[$item->program->department] }}</td>
<td>@include('department.status',['status'=>$item->status,'kind'=>'submission'])</td>
<td>
<a class="btn btn-sm btn-navy" href="{{ route('department.review',$item->id) }}">Periksa</a>
</td>
</tr>@empty<tr>
<td colspan="5">
<div class="k-empty">
<i class="bi bi-inbox" aria-hidden="true">
</i>
<p>Belum ada pengajuan.</p>
</div>
</td>
</tr>@endforelse</tbody>
</table>
</div>@include('department.pagination',['items'=>$items])</section>
@endsection
