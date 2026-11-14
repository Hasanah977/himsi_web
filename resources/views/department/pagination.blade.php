<div class="k-pagination">
<span>{{ $items->total() }} data · Halaman {{ $items->currentPage() }} / {{ $items->lastPage() }}</span>
<div>@if($items->previousPageUrl())<a class="btn btn-sm btn-outline-navy" href="{{ $items->previousPageUrl() }}">Sebelumnya</a>@endif @if($items->nextPageUrl())<a class="btn btn-sm btn-outline-navy" href="{{ $items->nextPageUrl() }}">Berikutnya</a>@endif</div>
</div>
