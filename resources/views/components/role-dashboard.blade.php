@props(['eyebrow', 'title', 'description', 'variant' => 'photo'])
@push('styles')
<link rel="stylesheet" href="{{ asset('css/himsi-dashboard-panels.css') }}?v=20261111-white-headings">
@endpush
<div class="rd-dashboard" data-dashboard="{{ $attributes->get('name', '') }}">
    <header class="rd-hero rd-hero-{{ $variant }}" style="--rd-photo:url('{{ \App\Support\Appearance::background('sidebar_background') }}')">
        @if($variant === 'election')<div class="rd-ballot"><i class="bi bi-box-seam" aria-hidden="true"></i><b>HIMSI</b></div>@endif
        <div class="rd-hero-copy"><span>{{ $eyebrow }}</span><h1>{{ $title }}</h1><p>{{ $description }}</p></div>
    </header>
    {{ $slot }}
</div>
