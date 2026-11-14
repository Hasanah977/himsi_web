@extends('admin.layout')

@section('title', $schema['label'])

@section('content')
    <div class="admin-toolbar">
        <div>
            <h2>
                {{ $content->exists ? 'Edit' : 'Tambah' }}
                {{ $schema['label'] }}
            </h2>

            <p>
                @if($section === 'hero_slides' && !$content->exists)
                    Unggah foto lanskap untuk ditampilkan sebagai latar beranda.
                @else
                    Gambar boleh dikosongkan untuk mempertahankan gambar sebelumnya.
                @endif
            </p>
        </div>
    </div>

    <form
        class="admin-panel admin-form"
        method="POST"
        enctype="multipart/form-data"
        action="{{ $content->exists
            ? route('admin.content.update', [
                'section' => $section,
                'content' => $content,
            ])
            : route('admin.content.store', $section) }}"
    >
        @csrf

        @if($content->exists)
            @method('PUT')
        @endif

        @if(in_array($section, ['programs', 'announcements', 'achievements']))
            <div class="mb-4">
                <label class="form-label" for="slug">
                    Slug / alamat detail
                </label>

                <input
                    class="form-control @error('slug') is-invalid @enderror"
                    type="text"
                    id="slug"
                    name="slug"
                    value="{{ old('slug', $content->slug) }}"
                    placeholder="contoh: seminar-teknologi"
                    pattern="[a-z0-9]+(-[a-z0-9]+)*"
                    maxlength="150"
                    required
                >

                @error('slug')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror

                <small class="text-secondary">
                    Huruf kecil, angka, dan tanda hubung.
                    Mengubah slug mengubah URL detail.
                </small>
            </div>
        @endif

        @if($section === 'announcements')
            <div class="mb-4">
                <label class="form-label" for="department">Penerima pengumuman</label>
                <select class="form-select" id="department" name="department">
                    <option value="">Umum — seluruh HIMSI dan halaman publik</option>
                    @foreach(config('superadmin.departments') as $key => $label)
                        <option value="{{ $key }}" @selected(old('department', $content->department) === $key)>Internal Departemen {{ $label }}</option>
                    @endforeach
                </select>
                <small class="text-secondary">Pengumuman internal hanya tampil pada portal departemen tujuan.</small>
            </div>
        @endif

        @foreach($schema['fields'] as $key => $field)
            @php
                $type = $field[1];
                $storedValue = $content->payload[$key] ?? '';
                $value = is_array($storedValue)
                    ? implode("\n", $storedValue)
                    : $storedValue;

                $inputValue = old('data.'.$key, $value);
                $errorKey = $type === 'image'
                    ? 'uploads.'.$key
                    : 'data.'.$key;

                $dateValue = '';
                $unrecognizedDate = false;

                if ($type === 'date') {
                    $rawDate = trim((string) $inputValue);

                    $months = [
                        'januari' => 1,
                        'februari' => 2,
                        'maret' => 3,
                        'april' => 4,
                        'mei' => 5,
                        'juni' => 6,
                        'juli' => 7,
                        'agustus' => 8,
                        'september' => 9,
                        'oktober' => 10,
                        'november' => 11,
                        'desember' => 12,
                    ];

                    $year = 0;
                    $month = 0;
                    $day = 0;

                    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $rawDate, $parts)) {
                        $year = (int) $parts[1];
                        $month = (int) $parts[2];
                        $day = (int) $parts[3];
                    } elseif (preg_match(
                        '/^(\d{1,2})\s+([a-z]+)\s+(\d{4})$/i',
                        $rawDate,
                        $parts
                    )) {
                        $day = (int) $parts[1];
                        $month = $months[strtolower($parts[2])] ?? 0;
                        $year = (int) $parts[3];
                    }

                    if (
                        $year >= 1
                        && $year <= 9999
                        && checkdate($month, $day, $year)
                    ) {
                        $dateValue = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    } else {
                        $unrecognizedDate = $rawDate !== '';
                    }
                }
            @endphp

            <div class="mb-4">
                <label class="form-label" for="field-{{ $key }}">
                    {{ $field[0] }}
                </label>

                @if($type === 'image')
                    @if(! empty($storedValue))
                        <div class="mb-3">
                            <img
                                class="admin-image-preview"
                                src="{{ \App\Models\PublicContent::imageUrl($storedValue) }}"
                                alt="Gambar saat ini"
                            >
                        </div>
                    @endif

                    <input
                        class="form-control {{ $errors->has($errorKey) ? 'is-invalid' : '' }}"
                        type="file"
                        id="field-{{ $key }}"
                        name="uploads[{{ $key }}]"
                        accept="image/jpeg,image/png,image/webp"
                        @required($section === 'hero_slides' && empty($storedValue))
                    >

                    <small class="text-secondary">
                        JPG, PNG, atau WebP. Maksimal 3 MB.
                    </small>

                @elseif(in_array($type, ['textarea', 'lines']))
                    <textarea
                        class="form-control {{ $errors->has($errorKey) ? 'is-invalid' : '' }}"
                        id="field-{{ $key }}"
                        name="data[{{ $key }}]"
                        rows="{{ $type === 'lines' ? 7 : 4 }}"
                        @required(!($section === 'departments' && $key === 'members'))
                    >{{ $inputValue }}</textarea>

                @elseif($type === 'select')
                    @php
                        $options = $field[2];
                        $knownOption = in_array($inputValue, $options, true);
                    @endphp

                    <select
                        class="form-select {{ $errors->has($errorKey) ? 'is-invalid' : '' }}"
                        id="field-{{ $key }}"
                        name="data[{{ $key }}]"
                        required
                    >
                        <option
                            value=""
                            disabled
                            @selected(! $knownOption)
                        >
                            — Pilih {{ $field[0] }} —
                        </option>

                        @foreach($options as $option)
                            <option
                                value="{{ $option }}"
                                @selected($inputValue === $option)
                            >
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>

                    @if($inputValue !== '' && ! $knownOption)
                        <small class="text-warning d-block mt-2">
                            Nilai sebelumnya: {{ $inputValue }}.
                            Silakan pilih salah satu pilihan yang tersedia.
                        </small>
                    @endif

                @elseif($type === 'date')
                    <div class="input-group">
                        <span class="input-group-text" aria-hidden="true">
                            <i class="bi bi-calendar3"></i>
                        </span>

                        <input
                            class="form-control {{ $errors->has($errorKey) ? 'is-invalid' : '' }}"
                            type="date"
                            id="field-{{ $key }}"
                            name="data[{{ $key }}]"
                            value="{{ $dateValue }}"
                            min="0001-01-01"
                            max="9999-12-31"
                            aria-describedby="help-{{ $key }}"
                            required
                        >
                    </div>

                    <small
                        class="text-secondary d-block mt-2"
                        id="help-{{ $key }}"
                    >
                        Klik ikon kalender di kolom untuk memilih tanggal,
                        bulan, dan tahun.
                    </small>

                    @if($unrecognizedDate)
                        <small class="text-warning d-block mt-2">
                            Tanggal sebelumnya: {{ $inputValue }}.
                            Silakan pilih ulang melalui kalender.
                        </small>
                    @endif

                @else
                    <input
                        class="form-control {{ $errors->has($errorKey) ? 'is-invalid' : '' }}"
                        type="{{ $type === 'number' ? 'number' : 'text' }}"
                        id="field-{{ $key }}"
                        name="data[{{ $key }}]"
                        value="{{ $inputValue }}"
                        @if($type === 'number') min="0" @endif
                        required
                    >
                @endif

                @if($errors->has($errorKey))
                    <div class="text-danger small mt-2" role="alert">
                        {{ $errors->first($errorKey) }}
                    </div>
                @endif
            </div>
        @endforeach

        <div class="mb-4">
            <label class="form-label" for="position">
                Urutan tampilan
            </label>

            <input
                class="form-control @error('position') is-invalid @enderror"
                type="number"
                id="position"
                name="position"
                value="{{ old('position', $content->position ?? 0) }}"
                min="0"
                max="100000"
                required
            >

            @error('position')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        @if($section !== 'settings')
            <label class="d-flex align-items-center gap-2 mb-4">
                <input
                    class="form-check-input m-0"
                    type="checkbox"
                    name="is_published"
                    value="1"
                    @checked(old(
                        'is_published',
                        session()->hasOldInput() ? false : $content->is_published
                    ))
                >

                Tampilkan di website publik
            </label>
        @endif

        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-navy" type="submit">
                Simpan Perubahan
            </button>

            <a
                class="btn btn-outline-navy"
                href="{{ route('admin.dashboard') }}"
            >
                Kembali ke Dashboard
            </a>
        </div>
    </form>
@endsection
