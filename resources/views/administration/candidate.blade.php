@extends('administration.layout')
@section('title', $candidate->name)
@section('content')
<article class="admin-panel"><h2>{{ $candidate->name }}</h2><h3 class="h5">Visi</h3><p class="text-break" style="white-space:pre-line">{{ $candidate->vision }}</p><h3 class="h5">Misi</h3><p style="white-space:pre-line">{{ $candidate->mission }}</p><a href="{{ route('kbh.voting.index') }}" class="btn btn-outline-navy">Kembali ke Voting</a></article>
@endsection
