@extends('department.layout')
@section('title', $mine ? 'Tugas Saya' : (request()->routeIs('department.tasks') ? 'Distribusi Tugas' : 'Monitoring Anggota'))
@section('content')
@php($listRoute = $mine ? 'department.my-tasks' : (request()->routeIs('department.tasks') ? 'department.tasks':'department.monitoring'))
<div class="k-heading">
<div>
<div class="k-eyebrow">{{ $departmentLabel }}</div>
<h1>{{ $mine ? 'Tugas Saya' : (request()->routeIs('department.tasks') ? 'Distribusi Tugas' : 'Monitoring Tugas & Validasi') }}</h1>
<p>{{ $mine ? 'Kerjakan tugas, unggah hasil, dan tindak lanjuti feedback koordinator.' : 'Pantau pekerjaan anggota dan berikan validasi atas hasil yang dikirim.' }}</p>
</div>@if(!$mine)<a class="btn o-btn-yellow" href="{{ route('department.task.create',$departmentKey) }}">
<i class="bi bi-plus-lg" aria-hidden="true">
</i> Assign Tugas Baru</a>@endif</div>
<div class="k-tabs">
<a class="{{ !request('status')?'active':'' }}" href="{{ route($listRoute,$departmentKey) }}">Semua Tugas <b>{{ $counts->sum() }}</b>
</a>@foreach(config('department.task_statuses') as $key=>$label)<a class="{{ request('status')===$key?'active':'' }}" href="{{ route($listRoute,[$departmentKey,'status'=>$key]) }}">{{ $label }} <b>{{ $counts[$key]??0 }}</b>
</a>@endforeach</div>
<div class="o-split o-task-split">
<div>
<div class="o-task-list">@forelse($tasks as $task)<a class="o-task-card {{ $selected?->id===$task->id?'active':'' }}" href="{{ route($listRoute,[$departmentKey,'status'=>request('status'),'task'=>$task->id]).'#task-detail' }}">
<div class="o-task-person">
<span class="k-avatar">{{ mb_substr($task->assignee?->name??'?',0,1) }}</span>
<strong>{{ $task->assignee?->name }}</strong>@include('department.status',['status'=>$task->status,'kind'=>'task'])</div>
<h2>{{ $task->title }}</h2>
<p>{{ $task->program->title }}</p>
<progress class="k-progress" value="{{ $task->progress }}" max="100" aria-label="Progres {{ $task->title }}"></progress><small>{{ $task->progress }}% dikerjakan</small>
<small>
<i class="bi bi-calendar3" aria-hidden="true">
</i> {{ $task->deadline->format('d M Y') }} @if($task->file_path)<span>
<i class="bi bi-paperclip" aria-hidden="true">
</i> Lampiran tersedia</span>@endif</small>
</a>@empty<div class="k-panel k-empty">
<i class="bi bi-list-task" aria-hidden="true">
</i>
<p>Belum ada tugas sesuai filter.</p>
</div>@endforelse</div>@include('department.pagination',['items'=>$tasks])</div>
<section class="k-panel o-task-detail" id="task-detail">@if(!$selected)<div class="k-empty">
<i class="bi bi-clipboard-check" aria-hidden="true">
</i>
<h2>Pilih Tugas</h2>
<p>Pilih salah satu tugas untuk melihat detail dan hasil pekerjaan.</p>
</div>@else<div class="k-panel-head">
<h2>{{ $selected->title }}</h2>@include('department.status',['status'=>$selected->status,'kind'=>'task'])</div>
<dl class="o-dl">
<dt>Anggota</dt>
<dd>{{ $selected->assignee?->name }}</dd>
<dt>Pemberi tugas</dt><dd>{{ $selected->creator?->name }}</dd>
<dt>Proker</dt>
<dd><a href="{{ route($mine ? 'department.member.program' : 'department.program.show', [$departmentKey, $selected->program_id]) }}">{{ $selected->program->title }}</a></dd>
<dt>Progres</dt><dd>{{ $selected->progress }}%</dd>
@if($selected->completed_at)<dt>Selesai divalidasi</dt><dd>{{ $selected->completed_at->copy()->timezone('Asia/Makassar')->format('d M Y H:i') }}</dd>@endif
<dt>Deadline</dt>
<dd>{{ $selected->deadline->format('d M Y') }}</dd>
</dl>
<h3>Instruksi</h3>
<p class="o-preline">{{ $selected->description }}</p>@if($selected->result)<h3>Hasil pekerjaan</h3>
<p class="o-preline">{{ $selected->result }}</p>@endif @if($selected->file_path)<a class="btn btn-outline-navy mb-3" href="{{ route('department.task.file',[$departmentKey,$selected->id]) }}">
<i class="bi bi-download" aria-hidden="true">
</i> Unduh hasil kerja</a>@endif @if($selected->feedback)<div class="k-callout">
<span>
<strong>Feedback Koordinator</strong>
<small>{{ $selected->feedback }}</small>
</span>
</div>@endif
@if($mine && $selected->assigned_to === auth()->id() && in_array($selected->status,['assigned','working','revision']))<form method="POST" enctype="multipart/form-data" action="{{ route('department.task.submit',[$departmentKey,$selected->id]) }}">@csrf<input type="hidden" name="version" value="{{ $selected->version }}">
<label class="form-label" for="progress">Progres pekerjaan (%)</label>
<input class="form-control mb-3" id="progress" name="progress" type="number" min="0" max="100" value="{{ old('progress', $selected->progress) }}" required>
<label class="form-label" for="result">Penjelasan hasil pekerjaan</label>
<textarea class="form-control mb-3" id="result" name="result" rows="5" maxlength="8000">{{ old('result',$selected->result) }}</textarea>
<label class="form-label" for="file">Lampiran hasil (opsional)</label>
<input class="form-control" type="file" id="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp">
<small>Dokumen atau gambar · maksimal 10 MB.</small>
<div class="o-actions">
<button class="btn btn-outline-navy" type="submit" name="action" value="start">Tandai Dikerjakan</button>
<button class="btn btn-outline-navy" type="submit" name="action" value="progress">Simpan Progres</button>
<button class="btn o-btn-yellow" type="submit" name="action" value="submit">Kirim untuk Review</button>
</div>
</form>
@elseif(!$mine && $selected->status==='submitted')<form method="POST" action="{{ route('department.task.review',[$departmentKey,$selected->id]) }}" data-k-confirm="Simpan hasil validasi tugas anggota ini?">@csrf<input type="hidden" name="version" value="{{ $selected->version }}">
<label class="form-label" for="feedback">Feedback / catatan</label>
<textarea class="form-control" id="feedback" name="feedback" rows="4" maxlength="3000" required>{{ old('feedback') }}</textarea>
<div class="o-actions">
<button class="btn btn-outline-navy" type="submit" name="decision" value="revision">Minta Revisi</button>
<button class="btn o-btn-yellow" type="submit" name="decision" value="done">Validasi Selesai</button>
</div>
</form>@endif @endif</section>
</div>
@endsection
