<div class="o-steps" aria-label="Tahap pengajuan proker">
    @foreach(['Info Proker', 'Surat Proposal', 'Status Pengajuan', 'Unggah RAB'] as $index => $label)
        @php($urls = isset($program) && $program->exists ? [route('department.program.edit', [$departmentKey, $program->id]), route('department.submission.edit', [$departmentKey, $program->id, 'letter']), route('department.program.show', [$departmentKey, $program->id]), route('department.submission.edit', [$departmentKey, $program->id, 'budget'])] : [])
        @if($urls && $index !== 0)<a class="{{ ($step ?? 0) === $index ? 'active' : '' }}" href="{{ $urls[$index] }}">
<b>{{ $index + 1 }}</b>{{ $label }}</a>@else<span class="{{ ($step ?? 0) === $index ? 'active' : '' }}">
<b>{{ $index + 1 }}</b>{{ $label }}</span>@endif
    @endforeach
</div>