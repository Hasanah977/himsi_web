<div class="d-flex flex-wrap gap-2 mt-3">
    @if(in_array($program->status, ['draft', 'revision', 'rejected'], true))
        <a class="btn btn-outline-navy" href="{{ route('department.program.edit', [$departmentKey, $program->id]) }}"><i class="bi bi-pencil-square" aria-hidden="true"></i> Edit</a>
    @endif
    @if($program->status === 'draft' && $program->submissions->isEmpty() && $program->tasks_count === 0)
        <form method="POST" action="{{ route('department.program.destroy', [$departmentKey, $program->id]) }}" data-confirm-delete data-confirm="Hapus draft {{ $program->title }}? Data yang dihapus tidak dapat dikembalikan.">
            @csrf
            @method('DELETE')
            <input type="hidden" name="version" value="{{ $program->version }}">
            <button class="btn btn-outline-danger" type="submit"><i class="bi bi-trash3" aria-hidden="true"></i> Hapus</button>
        </form>
    @endif
</div>
