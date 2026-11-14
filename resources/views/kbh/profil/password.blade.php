@extends('layouts.kbh')

@section('title', 'Ubah Password | KBH')
@section('page-title', 'Ubah Password')
@section('page-subtitle', 'Perbarui keamanan akun Anda')

@section('content')

<div class="form-card">

    <form
        action="{{ route('kbh.profil.password.update') }}"
        method="POST"
    >

        @csrf
        @method('PUT')


        <div class="form-group">

            <label>Password Lama</label>

            <input
                type="password"
                name="password_lama"
                class="form-control"
                required
            >

        </div>


        <div class="form-group">

            <label>Password Baru</label>

            <input
                type="password"
                name="password"
                class="form-control"
                required
            >

        </div>


        <div class="form-group">

            <label>Konfirmasi Password Baru</label>

            <input
                type="password"
                name="password_confirmation"
                class="form-control"
                required
            >

        </div>


        <div class="button-group">

            <button
                type="submit"
                class="btn-primary"
            >
                Ubah Password
            </button>

            <a href="{{ route('kbh.profil') }}"
               class="btn-secondary">
                Batal
            </a>

        </div>

    </form>

</div>

@endsection
