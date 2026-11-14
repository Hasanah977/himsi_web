@extends('admin.layout')

@section('title', 'Kelola Akun & Role')

@section('content')
    <div class="admin-toolbar">
        <div>
            <h2>Akun Pengguna</h2>
            <p>Kelola akun KBH, jabatan pengurus, dan akses login. Riwayat tetap tersimpan.</p>
        </div>

        <a class="btn btn-navy" href="{{ route('admin.users.create') }}">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Tambah Akun
        </a>
    </div>

    <div class="admin-panel">
        <form action="{{ route('admin.users.index') }}" method="GET" class="admin-account-search" role="search">
            <div class="admin-account-search-field">
                <label for="account-search" class="form-label">Cari akun</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                    <input type="search" id="account-search" name="search" class="form-control" value="{{ $search }}" placeholder="Ketik nama atau email akun" maxlength="255">
                </div>
            </div>
            <button class="btn btn-navy" type="submit">Cari</button>
            @if($search !== '')<a class="btn btn-outline-navy" href="{{ route('admin.users.index') }}">Reset</a>@endif
        </form>
        <p class="small text-secondary mb-3" role="status">{{ $users->count() }} akun{{ $search !== '' ? ' ditemukan untuk “'.$search.'”' : ' terdaftar' }}.</p>
        <div class="table-responsive">
            <table class="table align-middle admin-table">
                <thead>
                    <tr>
                        <th>Nama / Email</th>
                        <th>Akun utama / Jabatan</th>
                        <th>Departemen</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $account)
                        <tr>
                            <td>
                                <strong>{{ $account->name }}</strong>
                                <small class="d-block text-secondary">
                                    {{ $account->email }}
                                </small>
                            </td>
                            <td>
                                @if($account->isStudentAccount())<strong>KBH</strong><small class="d-block text-secondary">{{ $account->assignedRole() === 'kbh' ? 'Belum memiliki jabatan' : config('superadmin.roles.'.$account->assignedRole()) }}</small>@else{{ config('superadmin.roles.'.$account->assignedRole()) }}@endif
                            </td>
                            <td>
                                {{ config('superadmin.departments')[$account->department] ?? '—' }}
                            </td>
                            <td>
                                <span class="badge {{ $account->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">
                                    {{ $account->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a
                                        class="btn btn-sm btn-outline-navy"
                                        href="{{ route('admin.users.edit', $account->encryptedAccountId()) }}"
                                    >
                                        Kelola Akun
                                    </a>


                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-5">{{ $search !== '' ? 'Tidak ada akun yang cocok. Coba nama atau email lain.' : 'Belum ada akun terdaftar.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
