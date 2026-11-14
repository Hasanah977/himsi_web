@extends(auth()->user()->isSuperAdmin() ? 'admin.layout' : 'layouts.kbh')
@section('title', 'Forum KBH')
@section('content')
<div class="member-page-heading"><div><h2>Forum KBH</h2><p>Tempat berbagi kabar, bertanya, dan berdiskusi bersama keluarga HIMSI.</p></div>@if(auth()->user()->isSuperAdmin())<button class="btn btn-navy" data-bs-toggle="modal" data-bs-target="#newKbhGroup">Buat Grup KBH</button>@endif</div>
@include('components.forum-workspace', ['forumIsKbh'=>true])
@if(auth()->user()->isSuperAdmin())
<div class="modal fade" id="newKbhGroup" tabindex="-1" aria-labelledby="newGroupTitle" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('kbh.forum.store') }}">@csrf<div class="modal-header"><h2 class="modal-title fs-5" id="newGroupTitle">Buat Grup KBH</h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Tutup"></button></div><div class="modal-body"><label class="form-label" for="group-name">Nama grup</label><input id="group-name" class="form-control mb-3" name="name" maxlength="120" required><label class="form-label" for="group-description">Deskripsi</label><textarea id="group-description" class="form-control" name="description" maxlength="1000"></textarea></div><div class="modal-footer"><button class="btn btn-navy">Buat Grup</button></div></form></div></div></div>
@endif
@endsection
