@include('components.public-banner', ['heading' => 'Profil Himpunan', 'description' => 'Mengenal lebih dekat perjalanan, nilai-nilai, dan visi misi yang menjadi fondasi HIMSI.'])
<section class="section section-soft"><div class="container"><div class="vision-grid">
    <article class="panel vision-card"><div class="vision-heading"><span class="value-icon"><i class="bi bi-bullseye"></i></span><h2>Visi</h2></div><p>{{ $settings['vision'] }}</p>@include('components.public-wave')</article>
    <article class="panel mission-card"><div class="vision-heading"><span class="value-icon"><i class="bi bi-flag"></i></span><h2>Misi</h2></div><ol class="mission-list">@foreach($settings['missions'] as $mission)<li>{{ $mission }}</li>@endforeach</ol></article>
</div></div></section>
<section class="section"><div class="container"><div class="section-center"><h2>Nilai-Nilai Inti</h2><p>Prinsip yang menjadi pedoman dalam berpikir, bersikap, dan bertindak.</p></div><div class="row g-4">@foreach($coreValues as [$icon, $name, $description])<div class="col-sm-6 col-lg-3"><article class="panel value-card h-100"><span class="value-icon"><i class="bi {{ $icon }}"></i></span><h3>{{ $name }}</h3><p>{{ $description }}</p></article></div>@endforeach</div></div></section>
<section class="section section-soft"><div class="container"><div class="section-heading"><div><h2>Pencapaian & Prestasi</h2><p>Ruang apresiasi atas karya, pembelajaran, dan kontribusi anggota HIMSI.</p></div><a class="btn btn-outline-navy" href="{{ route('achievements') }}">Lihat Semua Prestasi <i class="bi bi-arrow-right ms-2"></i></a></div><div class="achievement-grid">
@forelse(array_slice(\App\Models\PublicContent::items('achievements'), 0, 4) as $entry)
    @include('components.public-content-card', ['kind' => 'achievement'])
@empty
    <div class="panel empty-state"><i class="bi bi-trophy fs-1 text-warning"></i><h3>Terus bertumbuh, bersama HIMSI</h3><p>Pencapaian dan prestasi anggota akan ditampilkan di sini setelah dipublikasikan.</p></div>
@endforelse
</div></div></section>
