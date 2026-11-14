@props(['title', 'icon' => 'bar-chart', 'href' => null, 'linkLabel' => 'Lihat semua'])
<section {{ $attributes->class(['rd-panel']) }}>
    <div class="rd-panel-heading"><h2><i class="bi bi-{{ $icon }}" aria-hidden="true"></i>{{ $title }}</h2>@if($href)<a href="{{ $href }}">{{ $linkLabel }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>@endif</div>
    {{ $slot }}
</section>
