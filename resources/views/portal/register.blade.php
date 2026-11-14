@extends('portal.layout')
@section('portal-title', 'Daftar Anggota')
@section('portal-body-class', 'hp-registration-page')
@section('portal-content')
@php($portalLogo = \App\Models\PublicContent::imageUrl(\App\Models\PublicContent::settings()['logo'] ?? 'logo-himsi.png'))
    <header class="hp-register-brand"><img src="{{ $portalLogo }}" alt="Logo HIMSI"><div><strong>HIMSI</strong><small>UNSULBAR — FT</small></div><div><strong>Portal Keanggotaan HIMSI</strong><small>Himpunan Mahasiswa Sistem Informasi Universitas Sulawesi Barat</small></div></header>
    <header class="hp-heading hp-register-heading"><h1><i class="bi bi-person-plus"></i> Pendaftaran Anggota HIMSI</h1><p>Daftarkan diri Anda sebagai anggota Himpunan Mahasiswa Sistem Informasi (HIMSI).</p></header>
    @include('portal.steps', ['step'=>1])
    <form id="hp-registration" class="hp-card" action="{{ route('registration.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="hp-draft"><span class="hp-section-icon"><i class="bi bi-floppy"></i></span><div><strong>Simpan Sementara</strong><small>Data diri tersimpan sementara di tab ini. Password dan berkas perlu diisi kembali.</small></div><span id="hp-draft-status" role="status">Belum ada perubahan</span></div>
        <section data-hp-panel="1">
            <div class="hp-form-section"><header><span class="hp-section-icon"><i class="bi bi-person"></i></span><div><h1 tabindex="-1">A. Informasi Pribadi</h1><p>Lengkapi informasi dasar Anda sebagai anggota HIMSI.</p></div></header>
            <div class="hp-grid">
                <div><label for="hp-name">Nama Lengkap *</label><div class="hp-input-icon"><i class="bi bi-person"></i><input id="hp-name" name="name" value="{{ old('name') }}" maxlength="255" placeholder="Nama lengkap sesuai KTM" autocomplete="name" required></div></div>
                <div><label for="hp-nim">NIM *</label><div class="hp-input-icon"><i class="bi bi-person-vcard"></i><input id="hp-nim" name="nim" value="{{ old('nim') }}" maxlength="30" pattern="[a-zA-Z0-9.\-]+" placeholder="Masukkan NIM" required></div></div>
                <div><label for="hp-year">Angkatan *</label><div class="hp-input-icon"><i class="bi bi-calendar3"></i><input id="hp-year" name="angkatan" type="number" value="{{ old('angkatan') }}" min="2000" max="{{ date('Y') }}" placeholder="Contoh: 2024" required></div></div>
                <div><label for="hp-study">Program Studi</label><div class="hp-input-icon"><i class="bi bi-mortarboard"></i><input id="hp-study" value="Sistem Informasi" readonly></div></div>
                <div><label for="hp-email">Email Aktif *</label><div class="hp-input-icon"><i class="bi bi-envelope"></i><input id="hp-email" name="email" type="email" value="{{ old('email') }}" maxlength="255" placeholder="Email yang aktif digunakan" autocomplete="email" required></div></div>
                <div><label for="hp-phone">No. HP Aktif *</label><div class="hp-input-icon"><i class="bi bi-telephone"></i><input id="hp-phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="16" pattern="\+?[0-9]{9,15}" placeholder="08xxxxxxxxxx" autocomplete="tel" required></div></div>
                <div><label for="hp-new-password">Password Baru *</label><div class="hp-password hp-input-icon"><i class="bi bi-lock"></i><input id="hp-new-password" name="password" type="password" minlength="8" maxlength="128" placeholder="Minimal 8 karakter" autocomplete="new-password" required><button type="button" data-hp-password="hp-new-password" aria-label="Tampilkan password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
                <div><label for="hp-confirm-password">Konfirmasi Password *</label><div class="hp-password hp-input-icon"><i class="bi bi-lock"></i><input id="hp-confirm-password" name="password_confirmation" type="password" minlength="8" maxlength="128" placeholder="Ulangi password" autocomplete="new-password" required><button type="button" data-hp-password="hp-confirm-password" aria-label="Tampilkan password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
            </div></div>
            @include('components.member-personal-fields')
            <div class="hp-actions"><p class="hp-muted"><i class="bi bi-info-circle"></i> Pastikan data sesuai dengan dokumen resmi yang diunggah.</p><button class="hp-button" type="button" id="hp-next">Lanjut ke Upload Berkas <i class="bi bi-arrow-right" aria-hidden="true"></i></button></div>
        </section>
        <section data-hp-panel="2" hidden>
            <h1 tabindex="-1">Langkah 2 — Unggah Berkas</h1>
            <p class="hp-muted">Unggah dokumen untuk proses verifikasi keanggotaan.</p>
            @foreach([
                'certificate'=>['Sertifikat Character Building (CB)', 'Pastikan nama pada sertifikat sesuai data pendaftaran.'],
                'active_letter'=>['Surat Aktif Kuliah', 'Gunakan surat dari fakultas yang berlaku pada semester berjalan.'],
                'student_card'=>['KTM (Kartu Tanda Mahasiswa)', 'Gunakan foto atau scan KTM dengan tulisan yang jelas.'],
            ] as $key=>$document)
                <div class="hp-upload-field">
                    <label for="hp-file-{{ $key }}">Berkas {{ $loop->iteration }} — {{ $document[0] }} *</label>
                    <div class="hp-dropzone">
                        <i class="bi bi-upload" aria-hidden="true"></i>
                        <span data-hp-filename>Tarik berkas ke sini atau klik untuk memilih</span>
                        <small>JPG, PNG, PDF • Maksimal 5 MB</small>
                        <input type="file" id="hp-file-{{ $key }}" name="{{ $key }}" accept=".jpg,.jpeg,.png,.pdf" required>
                    </div>
                    <small class="hp-muted">{{ $document[1] }}</small>
                </div>
            @endforeach
            <p class="hp-upload-count" id="hp-upload-count" role="status" aria-live="polite">0 dari 3 berkas dipilih</p>
            <p class="hp-muted">Berkas akan diunggah setelah tombol Kirim Pendaftaran ditekan.</p>
            <div class="hp-actions"><button class="hp-button hp-secondary" type="button" id="hp-previous"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali</button><button class="hp-button" type="button" id="hp-review" disabled>Konfirmasi Pendaftaran <i class="bi bi-arrow-right"></i></button></div>
        </section>
        <section data-hp-panel="3" hidden><h1 tabindex="-1">Konfirmasi Pendaftaran</h1><p class="hp-muted">Periksa data dan berkas Anda sebelum mengirim.</p><dl id="hp-review-data" class="hp-review-data"></dl><label class="hp-check"><input type="checkbox" id="hp-confirm-data" required> Saya memastikan data dan berkas yang dikirim sudah benar.</label><div class="hp-actions"><button class="hp-button hp-secondary" type="button" id="hp-back-upload">Kembali ke Berkas</button><button class="hp-button" type="submit" id="hp-send">Kirim Pendaftaran <i class="bi bi-send"></i></button></div></section>
        <noscript><p>Aktifkan JavaScript untuk melanjutkan pendaftaran.</p></noscript>
    </form>
    <p class="hp-footer-link">Sudah punya akun? <a href="{{ route('login') }}">Kembali ke Login</a></p>
@endsection
