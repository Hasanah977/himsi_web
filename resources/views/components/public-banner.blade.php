<header class="page-hero revised-banner">
    <img class="page-banner-photo" src="{{ $image(!empty($settings['page_banner']) && $settings['page_banner'] !== 'placeholder.svg' ? $settings['page_banner'] : 'public-banner-default.png') }}" alt="" fetchpriority="high">
    <div class="container">@isset($eyebrow)<span class="eyebrow text-warning">{{ $eyebrow }}</span>@endisset<h1>@isset($icon)<i class="bi bi-{{ $icon }} banner-icon" aria-hidden="true"></i>@endisset{{ $heading }}</h1><p>{{ $description }}</p></div>
    @include('components.public-wave')
</header>
