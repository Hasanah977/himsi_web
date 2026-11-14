@php($siteIconSettings = request()->routeIs('admin.security.*') ? config('superadmin.defaults') : \App\Models\PublicContent::settings())
<link rel="icon" href="{{ \App\Models\PublicContent::imageUrl($siteIconSettings['logo'] ?? 'logo-himsi.png') }}">
<link rel="apple-touch-icon" href="{{ \App\Models\PublicContent::imageUrl($siteIconSettings['logo'] ?? 'logo-himsi.png') }}">
