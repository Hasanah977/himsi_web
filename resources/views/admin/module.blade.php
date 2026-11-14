@extends('admin.layout')

@section('title', $moduleTitle)

@section('content')
    <div class="admin-panel">
        <h2>{{ $moduleTitle }}</h2>

        @if(empty($links))
            <div class="admin-module-empty">
                <i class="bi bi-puzzle" aria-hidden="true"></i>
                <h3>Modul belum terhubung</h3>
                <p>
                    Fitur role ini dikerjakan kelompok penanggung jawab.
                    Menu akan diaktifkan setelah route modul tersedia.
                </p>
            </div>
        @else
            <div class="admin-shortcut-grid mt-4">
                @foreach($links as $link)
                    @if(\Illuminate\Support\Facades\Route::has($link['route']))
                        <a class="admin-shortcut" href="{{ route($link['route'], $link['parameters'] ?? []) }}">
                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                            <strong>{{ $link['label'] }}</strong>
                            <span>Buka modul →</span>
                        </a>
                    @else
                        <div class="admin-shortcut">
                            <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                            <strong>{{ $link['label'] }}</strong>
                            <span>Route belum tersedia</span>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
@endsection