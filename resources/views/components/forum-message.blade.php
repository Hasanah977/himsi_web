@props(['message', 'fileUrl', 'editUrl', 'deleteUrl'])
@php
    $mine = $message->user_id === auth()->id();
    $fileSize = $message->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($message->file_path) ? \Illuminate\Support\Facades\Storage::disk('local')->size($message->file_path) : null;
    $extension = strtolower(pathinfo($message->file_name ?? '', PATHINFO_EXTENSION));
@endphp
<article class="forum-message {{ $mine ? 'mine' : '' }}">
    <span class="forum-avatar">{{ mb_strtoupper(mb_substr($message->sender?->name ?? '?',0,1)) }}</span>
    <div class="forum-message-content"><header><strong>{{ $message->sender?->name ?? 'Akun dihapus' }}</strong><span class="forum-role">{{ config('superadmin.roles.'.$message->sender?->role,'Anggota HIMSI') }}</span><time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->copy()->timezone('Asia/Makassar')->format('H:i') }}</time></header><div class="forum-bubble">
        @if($message->body)<p>{{ $message->body }}</p>@endif
        @if($message->file_path)<a class="forum-file" href="{{ $fileUrl }}"><span class="file-type {{ $extension === 'pdf' ? 'pdf' : (in_array($extension,['xls','xlsx']) ? 'sheet' : '') }}">{{ strtoupper($extension ?: 'FILE') }}</span><span><strong>{{ $message->file_name }}</strong>@if($fileSize !== null)<small>{{ $fileSize >= 1048576 ? number_format($fileSize / 1048576,1,',','.').' MB' : number_format($fileSize / 1024,0,',','.').' KB' }}</small>@endif</span><i class="bi bi-download ms-auto"></i></a>@endif
        @if($message->updated_at->gt($message->created_at))<small class="text-secondary">Diedit</small>@endif
        @if($message->user_id === auth()->id())
        <details class="forum-message-actions">
            <summary aria-label="Opsi pesan">Opsi pesan <i class="bi bi-three-dots"></i></summary>
            <form method="POST" action="{{ $editUrl }}" class="mt-2">@csrf @method('PUT')
                <label class="visually-hidden" for="edit-message-{{ $message->id }}">Edit pesan</label>
                <textarea id="edit-message-{{ $message->id }}" class="form-control" name="body" rows="3" maxlength="10000" @required(!$message->file_path)>{{ $message->body }}</textarea>
                <button class="btn btn-sm btn-light mt-2" type="submit">Simpan edit</button>
            </form>
            <form method="POST" action="{{ $deleteUrl }}" class="mt-2" data-confirm-message="Hapus pesan ini beserta lampirannya?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" type="submit">Hapus pesan</button></form>
        </details>
        @endif
    </div></div>
</article>
