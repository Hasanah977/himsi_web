@props(['segments', 'caption' => 'Total', 'center' => null])
@php
    $segments = collect($segments);
    $total = $segments->sum('value');
    $position = 0;
    $stops = [];
    foreach ($segments as $segment) {
        $end = $position + ($total ? $segment['value'] / $total * 100 : 0);
        $stops[] = $segment['color'].' '.$position.'% '.$end.'%';
        $position = $end;
    }
    $background = $total ? 'conic-gradient('.implode(',', $stops).')' : '#e3e7ef';
@endphp
<div class="rd-donut-layout">
    <div class="rd-donut" style="background:{{ $background }}" role="img" aria-label="{{ $caption }}: {{ $center ?? $total }}"><div><strong>{{ $center ?? $total }}</strong><span>{{ $caption }}</span></div></div>
    <ul class="rd-legend">@foreach($segments as $segment)<li><i style="background:{{ $segment['color'] }}" aria-hidden="true"></i><span>{{ $segment['label'] }}</span><b>{{ $segment['value'] }} <small>({{ $total ? round($segment['value'] / $total * 100) : 0 }}%)</small></b></li>@endforeach</ul>
</div>
@if(!$total)<p class="rd-empty-note">Belum ada data tercatat.</p>@endif
