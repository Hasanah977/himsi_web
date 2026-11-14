@props(['items'])
@php($maximum = max(1, max($items ?: [0])))
<div {{ $attributes }}>
    @foreach($items as $label => $value)
        <div class="mb-4">
            <div class="d-flex justify-content-between gap-3 mb-2"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
            <div class="rounded-2 bg-light" style="height:24px" aria-hidden="true">
                <div class="rounded-2" style="height:100%;background:#283964;width:{{ $value / $maximum * 100 }}%"></div>
            </div>
        </div>
    @endforeach
    @if(!array_sum($items))<p class="text-secondary mb-0">Belum ada data. Grafik akan terisi saat aktivitas tercatat.</p>@endif
</div>
