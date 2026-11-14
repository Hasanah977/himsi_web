<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Sedang Dalam Perbaikan — HIMSI</title>
    <link rel="icon" href="{{ asset('images/himsi/logo-himsi.png') }}">
    <link rel="stylesheet" href="{{ asset('css/maintenance.css') }}?v=20261111-2">
</head>
<body style="--maintenance-photo: url('{{ \App\Support\Appearance::background('maintenance_background') }}')">
    <header class="maintenance-brand">
        <img src="{{ asset('images/himsi/logo-himsi.png') }}" alt="Logo HIMSI" width="88" height="88">
        <div><strong>HIMSI</strong><span>UNSULBAR</span></div>
    </header>
    <main class="maintenance-card">
        <div class="maintenance-art" aria-hidden="true">
            <svg viewBox="0 0 440 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="screen" x1="130" y1="120" x2="350" y2="340" gradientUnits="userSpaceOnUse"><stop stop-color="#304c94"/><stop offset="1" stop-color="#09193e"/></linearGradient>
                    <linearGradient id="gold" x2="1" y2="1"><stop stop-color="#ffeb55"/><stop offset="1" stop-color="#efb600"/></linearGradient>
                    <pattern id="stripe" width="48" height="48" patternUnits="userSpaceOnUse" patternTransform="skewX(-25)"><path fill="#ffda00" d="M0 0h24v48H0z"/><path fill="#1b2e5c" d="M24 0h24v48H24z"/></pattern>
                    <filter id="shadow"><feDropShadow dx="0" dy="8" stdDeviation="6" flood-color="#132451" flood-opacity=".25"/></filter>
                </defs>
                <circle cx="220" cy="194" r="167" fill="#e6ebff"/>
                <g transform="translate(127 132)" fill="url(#gold)">
                    <path d="M-24-88h48l5 24 19 10 23-8 24 41-18 17v21l18 17-24 41-23-8-19 10-5 24h-48l-5-24-19-10-23 8-24-41 18-17v-21l-18-17 24-41 23 8 19-10z"/>
                    <circle r="38" fill="#e6ebff"/>
                </g>
                <g filter="url(#shadow)">
                    <path d="M133 135Q134 122 150 122H344Q359 122 357 139L337 309H111z" fill="#52679f" stroke="#8da0d3" stroke-width="5"/>
                    <path d="M148 143h190l-17 145H131z" fill="url(#screen)"/>
                    <path d="M111 309h226l37 29q3 10-12 13H77q-16-3-10-13z" fill="#8799c7"/>
                    <path d="M125 316h195l14 14H110z" fill="#263b6b"/>
                    <path d="M186 335h61l7 9h-75z" fill="#b9c6e9"/>
                </g>
                <g transform="translate(235 200)" fill="#f7f9ff">
                    <path d="M-9-43h18l4 12 8 4 12-3 9 16-9 9v10l9 9-9 16-12-3-8 4-4 12H-9l-4-12-8-4-12 3-9-16 9-9v-10l-9-9 9-16 12 3 8-4z"/>
                    <circle r="18" fill="#223968"/>
                </g>
                <path d="M153 253h163v29H153z" fill="url(#stripe)"/>
                <path d="M361 189c-18-7-34 9-31 26l-32 76q-3 11 8 15t17-6l29-77c17-5 25-24 12-37l-10 22-13-5z" fill="#bbc7e7" stroke="#8093c1" stroke-width="3"/>
                <path d="M38 339l28-94h18l29 94z" fill="url(#gold)"/>
                <path d="M54 284h42l6 20H48z" fill="#fff"/><rect x="26" y="339" width="99" height="14" rx="5" fill="#f3bd00"/>
                <path d="M282 345c0-60 83-70 91 0" fill="url(#gold)"/><path d="M321 296v44" stroke="#e8af00" stroke-width="9"/><rect x="266" y="341" width="120" height="17" rx="8" fill="#ffda00"/>
                <path d="M342 66l5-17m11 26 15-12m-10 27 19-3M44 217l-14-8" stroke="#ffcf00" stroke-width="7" stroke-linecap="round"/>
            </svg>
        </div>
        <section class="maintenance-copy">
            <span class="maintenance-label">⚒ &nbsp; Pemeliharaan Sistem</span>
            <h1>Sedang Dalam Perbaikan</h1>
            <div class="maintenance-accent" aria-hidden="true"></div>
            <p>Website sementara tidak dapat diakses. Kami sedang melakukan pemeliharaan agar layanan kembali berjalan dengan baik. Silakan kunjungi kembali nanti.</p>
            <a href="{{ route('login') }}"><span aria-hidden="true">↪</span> Login pengelola <span class="maintenance-arrow" aria-hidden="true">›</span></a>
        </section>
    </main>
</body>
</html>
