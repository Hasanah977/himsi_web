@extends(in_array(auth()->user()->role, ['koordinator_departemen', 'anggota_departemen'], true) ? 'department.shell' : 'administration.shell')
@if(in_array(auth()->user()->role, ['koordinator_departemen', 'anggota_departemen'], true))
@push('styles')
<link rel="stylesheet" href="{{ asset('css/administration/theme.css') }}">
@endpush
@endif
