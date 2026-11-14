@props(['items', 'icons' => ['clipboard-check', 'file-earmark-check', 'layers', 'exclamation-triangle']])
<div class="rd-stats">
    @foreach($items as $label => $value)
        <article class="rd-stat rd-tone-{{ $loop->index % 4 }}"><span class="rd-icon"><i class="bi bi-{{ $icons[$loop->index % count($icons)] }}" aria-hidden="true"></i></span><div><span>{{ $label }}</span><strong>{{ $value }}</strong></div></article>
    @endforeach
</div>
