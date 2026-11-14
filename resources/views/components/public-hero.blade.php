@php($heroSlides = \App\Models\PublicContent::heroSlides())
<section class="hero photo-hero" data-hero-carousel aria-roledescription="carousel" aria-label="Foto kegiatan HIMSI">
    <div class="hero-slides" aria-live="off">
        @foreach($heroSlides as $slide)
            <img class="hero-slide {{ $loop->first ? 'is-active' : '' }}" src="{{ $image($slide['image'] ?? '') }}" alt="{{ $slide['title'] ?? 'Kegiatan HIMSI' }}" @if(!$loop->first) loading="lazy" @else fetchpriority="high" @endif aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
        @endforeach
    </div>
    <div class="hero-shade"></div>
    <div class="container hero-copy"><span class="hero-chip">{{ $settings['hero_badge'] }}</span><h1>{{ $settings['hero_before'] }}<br><span>{{ $settings['hero_highlight'] }}</span> {{ $settings['hero_after'] }}</h1><p>{{ $settings['hero_description'] }}</p><a href="{{ route('profile') }}" class="btn btn-yellow">Kenal HIMSI Lebih Dekat <i class="bi bi-arrow-right ms-2"></i></a></div>
    @include('components.public-wave')
</section>
