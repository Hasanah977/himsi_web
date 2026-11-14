@extends('department.layout')
@section('title', 'Detail Pengumuman')
@section('content')
<div class="k-heading"><div><div class="k-eyebrow">{{ $content->payload['category'] }}</div><h1>{{ $content->payload['title'] }}</h1><p>{{ $content->payload['date'] ?? '' }} · {{ $content->department ? $departmentLabel : 'Umum HIMSI' }}</p></div><a class="btn btn-outline-navy" href="{{ route('department.member.announcements', $departmentKey) }}">Kembali</a></div>
<article class="k-panel"><p class="fw-semibold">{{ $content->payload['summary'] }}</p>@foreach((array) ($content->payload['body'] ?? []) as $paragraph)<p class="o-preline">{{ $paragraph }}</p>@endforeach</article>
@endsection
