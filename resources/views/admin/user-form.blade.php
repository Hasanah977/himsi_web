@extends('admin.layout')

@section('title', $account->exists ? 'Edit Akun' : 'Tambah Akun')

@section('content')
    <form
        class="admin-panel admin-form"
        method="POST"
        action="{{ $account->exists
            ? route('admin.users.update', $account->encryptedAccountId())
            : route('admin.users.store') }}"
    >
        @csrf

        @if($account->exists)
            @method('PUT')
        @endif

        <div class="mb-4">
            <label class="form-label" for="name">Nama lengkap</label>
            <input
                class="form-control"
                id="name"
                name="name"
                value="{{ old('name', $account->name) }}"
                required
            >
        </div>

        <div class="mb-4">
            <label class="form-label" for="email">Email</label>
            <input
                class="form-control"
                type="email"
                id="email"
                name="email"
                value="{{ old('email', $account->email) }}"
                required
            >
        </div>

        @if($account->exists && $account->isStudentAccount())
        <div class="alert alert-info">Akun utama: <strong>KBH</strong>. Jabatan diatur melalui bagian Kelola Jabatan di bawah.</div>
        <input type="hidden" name="role" value="{{ $account->assignedRole() }}">
        <input type="hidden" name="department" value="{{ $account->department }}">
        @else
        <div class="mb-4">
            <label class="form-label" for="role">Jenis akun</label>
            <select class="form-select" id="role" name="role" required>
                @foreach(array_diff_key(config('superadmin.roles'), array_flip(\App\Models\User::OFFICER_ROLES)) as $key => $label)
                    <option
                        value="{{ $key }}"
                        @selected(old('role', $account->assignedRole()) === $key)
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-4" id="departmentField">
            <label class="form-label" for="department">Departemen</label>
            <select class="form-select" id="department" name="department">
                <option value="">Pilih departemen</option>

                @foreach(config('superadmin.departments') as $key => $label)
                    <option
                        value="{{ $key }}"
                        @selected(old('department', $account->department) === $key)
                    >
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        @endif
        <div class="mb-4">
            <label class="form-label" for="password">
                {{ $account->exists ? 'Password baru — kosongkan jika tidak diubah' : 'Password' }}
            </label>

            <input
                class="form-control"
                type="password"
                id="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                @required(! $account->exists)
            >
        </div>

        <div class="mb-4">
            <label class="form-label" for="password_confirmation">
                Ulangi password
            </label>
            <input
                class="form-control"
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                minlength="8"
                autocomplete="new-password"
                @required(! $account->exists)
            >
        </div>

        @if($account->exists)
        <input type="hidden" name="is_active" value="{{ (int) $account->is_active }}">
        <p>Status login: <strong>{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</strong>. Perubahan status tersedia di bawah.</p>
        @else
        <label class="d-flex align-items-center gap-2 mb-4">
            <input
                class="form-check-input m-0"
                type="checkbox"
                name="is_active"
                value="1"
                @checked(old('is_active', session()->hasOldInput() ? false : $account->is_active))
            >
            Akun aktif
        </label>
        @endif

        <div class="d-flex gap-2">
            <button class="btn btn-navy" type="submit">Simpan Akun</button>
            <a class="btn btn-outline-navy" href="{{ route('admin.users.index') }}">
                Batal
            </a>
        </div>
    </form>
    @if($account->exists)
        @include('admin.account-access')
    @endif
@endsection