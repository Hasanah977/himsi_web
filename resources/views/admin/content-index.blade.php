@extends('admin.layout')

@section('title', $schema['label'])

@section('content')
    <div class="admin-toolbar">
        <div>
            <h2>{{ $schema['label'] }}</h2>
            <p>Urutan lebih kecil ditampilkan lebih dahulu.</p>
            @if($section === 'quotes')
                <p>Kutipan yang tayang berganti otomatis setelah kutipan utama dari Beranda &amp; Profil, sesuai urutan di bawah.</p>
            @elseif($section === 'hero_slides')
                <p>Foto yang tayang akan berganti otomatis di belakang teks beranda. Jika belum ada slide yang tayang, foto galeri terbaru digunakan.</p>
            @elseif($section === 'achievements')
                <p>Foto, ringkasan, dan detail prestasi tampil pada halaman Profil Himpunan.</p>
            @endif
        </div>

        <a
            class="btn btn-navy"
            href="{{ route('admin.content.create', $section) }}"
        >
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Tambah Konten
        </a>
    </div>

    <div class="admin-panel">
        <div class="table-responsive">
            <table class="table align-middle admin-table">
                <thead>
                    <tr>
                        <th>Urutan</th>
                        <th>Judul / Nama</th>
                        <th>Status Publikasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($contents as $content)
                        <tr>
                            <td>{{ $content->position }}</td>

                            <td>
                                <strong>
                                    {{ $content->payload['title']
                                        ?? $content->payload['name']
                                        ?? 'Konten' }}
                                </strong>
                            </td>

                            <td>
                                <span class="badge {{ $content->is_published ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $content->is_published ? 'Tayang' : 'Draft' }}
                                </span>
                            </td>

                            <td>
                                <div class="d-flex gap-2">
                                    <a
                                        class="btn btn-sm btn-outline-navy"
                                        href="{{ route('admin.content.edit', [
                                            'section' => $section,
                                            'content' => $content,
                                        ]) }}"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.content.destroy', [
                                            'section' => $section,
                                            'content' => $content,
                                        ]) }}"
                                        data-confirm-delete
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            class="btn btn-sm btn-outline-danger"
                                            type="submit"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                Belum ada konten.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
