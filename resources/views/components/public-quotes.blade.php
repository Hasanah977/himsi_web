@php
    $quoteSlides = array_merge([
        [
            'quote' => $settings['quote'],
            'name' => $settings['quote_name'],
            'role' => $settings['quote_role'],
            'image' => $settings['quote_image'],
        ],
    ], \App\Models\PublicContent::items('quotes'));
@endphp
<section class="public-quotes" data-quote-carousel aria-label="Kutipan keluarga HIMSI" aria-roledescription="carousel" tabindex="0">
    <div class="container">
        <div class="quote-slides" aria-live="off">
            @foreach($quoteSlides as $quoteSlide)
                <figure class="quote-slide {{ $loop->first ? 'is-active' : '' }}" data-quote-slide role="group" aria-roledescription="slide" aria-label="Kutipan {{ $loop->iteration }} dari {{ $loop->count }}" aria-hidden="{{ $loop->first ? 'false' : 'true' }}">
                    <div class="quote-portrait">
                        <span class="quote-orbit" aria-hidden="true"></span>
                        <svg class="quote-spark" viewBox="0 0 58 58" fill="none" aria-hidden="true"><path d="m15 23 7-18M28 31l18-15M35 44l18-3" stroke="currentColor" stroke-width="6" stroke-linecap="round"/></svg>
                        <div class="quote-portrait-frame">
                            <img src="{{ $image(($quoteSlide['image'] ?? 'placeholder.svg') === 'placeholder.svg' ? $settings['logo'] : $quoteSlide['image']) }}" alt="{{ $quoteSlide['name'] }}" loading="lazy" width="320" height="320">
                        </div>
                    </div>
                    <figcaption class="quote-copy">
                        <span class="quote-symbol" aria-hidden="true">“</span>
                        <blockquote>{{ $quoteSlide['quote'] }}</blockquote>
                        <span class="quote-rule" aria-hidden="true"></span>
                        <strong>{{ $quoteSlide['name'] }}</strong>
                        <small>{{ $quoteSlide['role'] }}</small>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
