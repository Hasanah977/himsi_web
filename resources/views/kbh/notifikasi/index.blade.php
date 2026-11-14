@extends('layouts.kbh')

@section('title', 'Notifikasi | KBH')
@section('page-title', 'Notifikasi')
@section('page-subtitle', 'Pemberitahuan terbaru untuk akun Anda')

@section('notification-count', '3')

@section('content')

<div class="notification-list">

    <div class="notification-item unread">

        <div class="notification-icon">
            &#128226;
        </div>

        <div class="notification-content">

            <div class="notification-header">

                <h3>Pengumuman Baru</h3>

                <span>
                    Baru saja
                </span>

            </div>

            <p>
                Terdapat pengumuman terbaru dari HIMSI.
            </p>

        </div>

    </div>


    <div class="notification-item unread">

        <div class="notification-icon">
            &#128101;
        </div>

        <div class="notification-content">

            <div class="notification-header">

                <h3>Seleksi Pengurus</h3>

                <span>
                    1 jam lalu
                </span>

            </div>

            <p>
                Pendaftaran seleksi pengurus HIMSI
                telah dibuka.
            </p>

        </div>

    </div>


    <div class="notification-item unread">

        <div class="notification-icon">
            &#128499;
        </div>

        <div class="notification-content">

            <div class="notification-header">

                <h3>Voting Ketua Umum</h3>

                <span>
                    2 jam lalu
                </span>

            </div>

            <p>
                Periode voting Ketua Umum HIMSI
                telah dimulai.
            </p>

        </div>

    </div>

</div>

@endsection

