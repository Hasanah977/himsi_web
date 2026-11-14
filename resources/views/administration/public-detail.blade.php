@extends('administration.layout')
@section('title', $item['title'])
@section('content')
<article class="admin-panel">@if(!empty($item['image']))<img class="w-100 rounded-3 mb-4" style="max-height:420px;object-fit:contain" src="{{ \App\Models\PublicContent::imageUrl($item['image']) }}" alt="{{ $item['title'] }}">@endif<small>{{ $item['date'] ?? '' }}</small><h2>{{ $item['title'] }}</h2>@foreach((array)($item['body'] ?? [$item['summary'] ?? '']) as $paragraph)<p>{{ $paragraph }}</p>@endforeach</article>
@endsection
