@extends(in_array(request()->attributes->get('administration_preview_role', auth()->user()->role), ['ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'koordinator_pengawas', 'kbh'], true) ? 'administration.shell' : 'department.shell')
@section('workspace-class', 'kpum-page department-page')
@if(in_array(request()->attributes->get('administration_preview_role', auth()->user()->role), ['ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'koordinator_pengawas', 'kbh'], true))
@push('styles')
<link rel="stylesheet" href="{{ asset('css/himsi-kpum.css') }}">
<link rel="stylesheet" href="{{ asset('css/himsi-department.css') }}">
<link rel="stylesheet" href="{{ asset('css/forum-messages.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('js/himsi-department.js') }}" defer></script>
<script src="{{ asset('js/himsi-member.js') }}" defer></script>
@endpush

@endif
