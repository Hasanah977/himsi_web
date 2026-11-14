@props(['name', 'role', 'department' => null])
<div {{ $attributes->merge(['class' => 'role-celebration text-center']) }}>
    <div class="celebration-confetti" aria-hidden="true">@for($i = 0; $i < 24; $i++)<span style="--i:{{ $i }};--color:{{ ['#f3c522', '#7c9cde', '#33aaa4', '#db7b99'][$i % 4] }}"></span>@endfor</div>
    <x-member-art kind="trophy" class="celebration-trophy" />
    <h1>Selamat, {{ $name }}!</h1>
    <p class="celebration-role">{{ $role === 'KBH' ? 'Selamat bergabung sebagai' : 'Anda dipercaya sebagai' }} <strong>{{ $role }} HIMSI</strong>@if($department)<br><strong>Departemen {{ $department }}</strong>@endif.</p>
    <p class="celebration-message">{{ $role === 'KBH' ? 'Setiap langkah kecil membawa perubahan. Mari belajar, bertumbuh, dan berkarya bersama keluarga besar HIMSI.' : 'Jalankan amanah dengan hati, tumbuh bersama tim, dan hadirkan karya yang berarti bagi keluarga besar HIMSI.' }}</p>
</div>
