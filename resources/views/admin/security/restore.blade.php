@extends('admin.layout')
@section('title', 'Konfirmasi Restore Database')
@section('content')
<div class="security-heading"><a href="{{ route('admin.security.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-2"></i>Kembali ke pusat kendali</a><h1 class="mt-3">Pulihkan database dari backup</h1><p>Periksa cadangan yang dipilih sebelum mengganti data saat ini.</p></div>
<section class="admin-panel security-restore-panel"><h2>Backup {{ \Carbon\Carbon::parse($backup['created_at'])->timezone('Asia/Makassar')->format('d M Y · H:i:s') }} WITA</h2><p class="mt-2">{{ $backup['tables'] }} tabel · {{ number_format($backup['rows']) }} baris data</p>
<div class="alert alert-warning">Perubahan data setelah waktu backup akan diganti. Sistem membuat cadangan pengaman terlebih dahulu, kemudian menutup akses publik dan membekukan akun lainnya. Akun Super Admin saat ini serta log keamanan tetap dipertahankan.</div>
<details class="mb-4"><summary class="fw-semibold">Lihat tabel dalam cadangan</summary><div class="table-responsive mt-3"><table class="table"><thead><tr><th>Tabel</th><th>Jumlah baris</th></tr></thead><tbody>@foreach($tables as $name=>$table)<tr><td>{{ $name }}</td><td>{{ number_format(count($table['rows'])) }}</td></tr>@endforeach</tbody></table></div></details>
<form method="POST" action="{{ route('admin.security.restore', $backup['id']) }}" autocomplete="off">@csrf
<label class="form-label" for="restore-password">Kata sandi Super Admin saat ini</label><input id="restore-password" type="password" name="password" autocomplete="current-password" required class="form-control mb-3">
<div class="form-check mb-4"><input class="form-check-input" type="checkbox" id="confirm-restore" name="confirm_restore" value="1" required><label class="form-check-label" for="confirm-restore">Saya memahami bahwa data database akan dikembalikan ke waktu backup ini.</label></div>
<div class="d-flex flex-wrap gap-2"><button class="btn btn-danger" type="submit"><i class="bi bi-arrow-counterclockwise me-2"></i>Pulihkan database</button><a href="{{ route('admin.security.index') }}" class="btn btn-outline-navy">Batal</a></div></form>
</section>
@endsection
