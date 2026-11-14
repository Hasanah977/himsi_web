@php
    $colors = ['#183773', '#ffcf00', '#3976ec', '#339a51', '#8994a8'];
    $peak = max(1, $activitySeries->max('count'));
    $points = $activitySeries->map(fn ($point, $index) => (40 + $index * 520 / max(1, $days - 1)).','. (190 - $point['count'] * 155 / $peak))->implode(' ');
    $totalAccounts = array_sum($accountGroups);
    $stops = []; $position = 0;
    foreach (array_values($accountGroups) as $index => $count) {
        $next = $position + ($totalAccounts ? $count / $totalAccounts * 100 : 0);
        $stops[] = $colors[$index].' '.$position.'% '.$next.'%';
        $position = $next;
    }
    $programTotal = $programStatuses->sum();
@endphp
<section class="analytics-section" aria-labelledby="analytics-title">
    <header class="admin-section-heading"><h2 id="analytics-title">Analitik Sistem HIMSI</h2><p>Ringkasan data akun, konten, dan aktivitas organisasi.</p></header>
    <div class="analytics-grid">
        <article class="analytics-card">
            <header><h3>Aktivitas Sistem</h3><div class="btn-group" aria-label="Rentang aktivitas">@foreach([7,30] as $range)<a class="btn btn-sm {{ $days === $range ? 'btn-navy' : 'btn-outline-navy' }}" href="{{ route('admin.dashboard', ['days' => $range]) }}" @if($days === $range) aria-current="page" @endif>{{ $range }} Hari</a>@endforeach</div></header>
            <svg class="activity-chart" viewBox="0 0 600 230" role="img" aria-label="Aktivitas sistem selama {{ $days }} hari: {{ $activitySeries->sum('count') }} aktivitas">
                @foreach([0,1,2,3,4] as $tick)<line x1="40" x2="560" y1="{{ 190 - $tick * 38.75 }}" y2="{{ 190 - $tick * 38.75 }}" stroke="#e3e8f1"/><text x="30" y="{{ 194 - $tick * 38.75 }}" text-anchor="end">{{ round($peak * $tick / 4) }}</text>@endforeach
                <polygon points="40,190 {{ $points }} 560,190" fill="#3976ec20"/>
                <polyline points="{{ $points }}" fill="none" stroke="#183773" stroke-width="3" stroke-linejoin="round"/>
                @foreach($activitySeries as $point)<circle cx="{{ 40 + $loop->index * 520 / max(1, $days - 1) }}" cy="{{ 190 - $point['count'] * 155 / $peak }}" r="4" fill="white" stroke="#183773" stroke-width="2"><title>{{ $point['label'] }}: {{ $point['count'] }} aktivitas</title></circle><text class="activity-value" x="{{ 40 + $loop->index * 520 / max(1, $days - 1) }}" y="{{ 190 - $point['count'] * 155 / $peak - 12 - ($days === 30 && $loop->odd ? 10 : 0) }}" text-anchor="middle">{{ $point['count'] }}</text>@if($days === 7 || $loop->index % 5 === 0 || $loop->last)<text x="{{ 40 + $loop->index * 520 / max(1, $days - 1) }}" y="215" text-anchor="middle">{{ $point['label'] }}</text>@endif @endforeach
            </svg>
            <small class="text-secondary">Berdasarkan tindakan yang tercatat di log.</small>
        </article>
        <article class="analytics-card"><header><h3>Distribusi Akun</h3></header><div class="account-chart"><div class="account-donut" style="background:{{ $totalAccounts ? 'conic-gradient('.implode(',', $stops).')' : '#e5eaf2' }}" role="img" aria-label="Distribusi {{ $totalAccounts }} akun"><div><strong>{{ $totalAccounts }}</strong><span>Akun</span></div>
@php
    $accountPosition = 0;
@endphp
@foreach($accountGroups as $label => $count)
    @if($totalAccounts && $count)
        @php
            $share = $count / $totalAccounts;
            $angle = ($accountPosition + $share / 2) * 2 * M_PI - M_PI / 2;
            $accountPosition += $share;
        @endphp
        <span class="donut-percentage" style="left:{{ 50 + cos($angle) * 41 }}%;top:{{ 50 + sin($angle) * 41 }}%;color:{{ $loop->index === 1 ? '#10204b' : '#fff' }}" title="{{ $label }}: {{ $count }} akun">{{ round($share * 100) }}%</span>
    @endif
@endforeach
</div><ul class="analytics-legend">@foreach($accountGroups as $label => $count)<li><i style="background:{{ $colors[$loop->index] }}"></i><span>{{ $label }}</span><strong>{{ $count }}</strong></li>@endforeach</ul></div></article>
        <article class="analytics-card"><header><h3>Konten Publik Tayang</h3></header><div class="content-bars">@foreach($contentCounts as $label => $count)<div><span>{{ $label }}</span><div class="bar-track"><div style="width:{{ $count / max(1, max($contentCounts)) * 100 }}%;background:{{ $loop->odd ? '#ffcf00' : '#183773' }}"></div></div><strong>{{ $count }}</strong></div>@endforeach</div></article>
        <article class="analytics-card"><header><h3>Status Program Kerja</h3><span>{{ $programTotal }} program publik</span></header><div class="program-stack" role="img" aria-label="Distribusi status program publik">@foreach(['Akan Datang','Sedang Berjalan','Selesai'] as $status)@php($count = $programStatuses->get($status, 0))<div style="width:{{ $programTotal ? $count / $programTotal * 100 : 0 }}%;background:{{ ['#ffcf00','#3976ec','#339a51'][$loop->index] }}"><span>{{ $programTotal && $count ? round($count / $programTotal * 100).'%' : '' }}</span></div>@endforeach</div><ul class="analytics-legend program-legend">@foreach(['Akan Datang','Sedang Berjalan','Selesai'] as $status)<li><i style="background:{{ ['#ffcf00','#3976ec','#339a51'][$loop->index] }}"></i><span>{{ $status }}</span><strong>{{ $programStatuses->get($status, 0) }}</strong></li>@endforeach</ul>@if(!$programTotal)<p class="text-secondary small">Belum ada program publik yang ditayangkan.</p>@endif</article>
    </div>
</section>
