@php
    $isProgram = $page === 'programs';
    $isAchievement = $page === 'achievements';
    $listRoute = $page;
    $filters = $isProgram ? ['Semua', 'Akan Datang', 'Sedang Berjalan', 'Selesai'] : ($isAchievement ? ['Semua'] : ['Semua', 'Akademik', 'Kegiatan', 'Penting', 'Info', 'Iuran']);
    $selectedFilter = request('filter') ?: 'Semua';
@endphp
@include('components.public-banner', ['heading' => $isProgram ? 'Program Kerja HIMSI' : ($isAchievement ? 'Pencapaian & Prestasi' : 'Pusat Informasi & Pengumuman'), 'description' => $isProgram ? 'Kegiatan dan inisiatif untuk pengembangan kompetensi mahasiswa.' : ($isAchievement ? 'Karya, pembelajaran, dan kontribusi keluarga HIMSI.' : 'Informasi terbaru terkait akademik, kegiatan himpunan, dan berita penting lainnya.'), 'icon' => null])
<section class="section section-soft public-list-section" data-public-listing><div class="container">
@if($isProgram)
    @php
        $counts = ['Akan Datang' => $programs->where('status', 'Akan Datang')->count(), 'Sedang Berjalan' => $programs->where('status', 'Sedang Berjalan')->count(), 'Selesai' => $programs->where('status', 'Selesai')->count()];
        $total = $programs->count();
        $upcomingShare = $total ? $counts['Akan Datang'] / $total * 100 : 0;
        $runningShare = $total ? $counts['Sedang Berjalan'] / $total * 100 : 0;
    @endphp
    <div class="program-overview" data-list-summary="{{ implode(',', $counts) }}"><div><span class="eyebrow">— Ringkasan Program Kerja</span><h2>Gambaran Umum dan Status Pelaksanaan</h2><p>Ringkasan jumlah program kerja HIMSI beserta distribusi status pelaksanaannya pada periode berjalan.</p></div><div class="program-summary-grid">@foreach(['Total Program'=>[$total,'stack'], 'Akan Datang'=>[$counts['Akan Datang'],'calendar-event'], 'Sedang Berjalan'=>[$counts['Sedang Berjalan'],'play-circle'], 'Selesai'=>[$counts['Selesai'],'check-circle']] as $label=>[$count,$symbol])<div class="summary-tile"><span class="summary-icon" data-status="{{ $label }}"><i class="bi bi-{{ $symbol }}"></i></span><div><small>{{ $label }}</small><strong data-count-up="{{ $count }}" aria-label="{{ $count }}">{{ $count }}</strong></div></div>@endforeach</div><div class="panel distribution"><h3>Distribusi Status Program Kerja</h3><div class="distribution-body"><div class="program-donut" data-donut-values="{{ json_encode(array_values($counts)) }}" style="--upcoming:{{ $upcomingShare }}%;--running:{{ $upcomingShare + $runningShare }}%;{{ !$total ? 'background:#e8edf6;' : '' }}" role="img" aria-label="{{ $total }} total program; {{ $counts['Akan Datang'] }} akan datang; {{ $counts['Sedang Berjalan'] }} sedang berjalan; {{ $counts['Selesai'] }} selesai"><div><strong data-count-up="{{ $total }}" aria-label="{{ $total }}">{{ $total }}</strong><span>Total<br>Program</span></div></div><ul>@foreach($counts as $label=>$count)<li><span class="legend-dot" data-status="{{ $label }}"></span><div><strong>{{ $label }}</strong><small>{{ $count }} Program ({{ $total ? number_format($count / $total * 100, 1) : 0 }}%)</small></div></li>@endforeach</ul></div></div></div>
@endif
<div class="panel filter-panel" data-list-controls><nav class="filter-tabs" aria-label="Filter daftar">@foreach($filters as $filter)<a class="{{ $selectedFilter === $filter ? 'active' : '' }}" href="{{ route($listRoute, ['filter'=>$filter, 'q'=>request('q')]) }}" @if($selectedFilter === $filter) aria-current="page" @endif>{{ $filter }}</a>@endforeach</nav><form class="search-form" action="{{ route($listRoute) }}" method="GET" role="search"><input type="hidden" name="filter" value="{{ $selectedFilter }}"><label class="visually-hidden" for="public-search">Cari informasi</label><input class="form-control" type="search" id="public-search" name="q" value="{{ request('q') }}" placeholder="Cari informasi…" maxlength="120"><button class="btn btn-navy">Cari</button></form></div>
<p class="visually-hidden" data-list-status role="status" aria-live="polite"></p>
<div data-list-results data-result-count="{{ $items->total() }}" tabindex="-1" aria-label="Hasil pencarian">
@if($items->isEmpty())<div class="panel empty-state"><i class="bi bi-search fs-2"></i><h2>Informasi tidak ditemukan</h2><p>Coba kata kunci atau kategori lain.</p><a class="btn btn-outline-navy" href="{{ route($listRoute) }}">Reset Pencarian</a></div>
@else
    @php($showFeatured = !$isProgram && !$isAchievement && $items->currentPage() === 1 && !request('q') && $selectedFilter === 'Semua')
    @if($showFeatured)
        @php($featured = $items->first())
        <article class="panel announcement-feature"><div class="announcement-feature-image"><img src="{{ $image($featured['image'] ?? '') }}" alt="" loading="lazy"></div><div><span class="tag featured-tag">{{ $featured['category'] }}</span><div class="news-meta"><i class="bi bi-calendar3"></i> {{ $featured['date'] }}</div><h2>{{ $featured['title'] }}</h2><p>{{ $featured['summary'] }}</p><a class="btn btn-navy" href="{{ route('announcement.detail', $featured['slug']) }}">Baca selengkapnya <i class="bi bi-arrow-right ms-2"></i></a></div></article>
    @endif
    @if(!$isProgram && !$isAchievement)<div class="section-heading announcement-list-heading"><h2>Daftar Pengumuman</h2><small>Menampilkan {{ $items->firstItem() }} – {{ $items->lastItem() }} dari {{ $items->total() }} pengumuman</small></div>@endif
    <div class="{{ $isProgram || $isAchievement ? 'program-grid' : 'notice-list' }}">
    @foreach($items as $entry)
        @if($isProgram || $isAchievement)@include('components.public-content-card', ['kind' => $isAchievement ? 'achievement' : 'program'])
        @elseif(!$showFeatured || !$loop->first)<article class="panel announcement-row"><a class="announcement-thumbnail" href="{{ route('announcement.detail', $entry['slug']) }}" tabindex="-1" aria-hidden="true"><img src="{{ $image($entry['image'] ?? '') }}" alt="" loading="lazy"></a><div class="announcement-copy"><div class="notice-meta"><span class="tag" data-category="{{ $entry['category'] }}">{{ $entry['category'] }}</span><small><i class="bi bi-calendar3"></i> {{ $entry['date'] }}</small></div><h3><a href="{{ route('announcement.detail', $entry['slug']) }}">{{ $entry['title'] }}</a></h3><p>{{ $entry['summary'] }}</p><a class="btn btn-navy" href="{{ route('announcement.detail', $entry['slug']) }}">Baca selengkapnya <i class="bi bi-arrow-right ms-2"></i></a></div></article>@endif
    @endforeach
    </div>
    <div class="public-pagination">{{ $items->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
@endif
</div>
</div></section>
