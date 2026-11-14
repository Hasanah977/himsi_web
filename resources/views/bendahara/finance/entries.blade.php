@extends('administration.layout')
@section('title', 'Transaksi Keuangan')
@push('styles')<link rel="stylesheet" href="{{ asset('css/himsi-finance.css') }}">@endpush
@section('content')
<div class="finance-page">
<div class="finance-heading"><div><span class="finance-eyebrow text-secondary">BUKU KAS HIMSI</span><h2>{{ $kind==='income'?'Pemasukan':'Pengeluaran' }}</h2><p>{{ $kind==='income'?'Dana masuk dari sponsor, donasi, usaha, iuran dan pengembalian.':'Pencairan kegiatan dan kebutuhan organisasi yang tidak terduga.' }}</p></div>@if(auth()->user()->role==='bendahara_umum')<button type="button" class="btn btn-navy" data-bs-toggle="collapse" data-bs-target="#newTransaction" aria-expanded="false" aria-controls="newTransaction"><i class="bi bi-plus-lg"></i> {{ $kind==='income'?'Catat Pemasukan':'Pengeluaran Tidak Terduga' }}</button>@endif</div>
@include('bendahara.finance.stats')
@if(auth()->user()->role==='bendahara_umum')<div class="collapse {{ $errors->any()?'show':'' }}" id="newTransaction"><form class="finance-panel mb-4" method="POST" enctype="multipart/form-data" action="{{ route('finance.store',$kind) }}" data-confirm="Simpan transaksi ini ke buku kas HIMSI?">@csrf<input type="hidden" name="reference" value="{{ old('reference', (string) Illuminate\Support\Str::uuid()) }}"><h2>Catat {{ $kind==='income'?'Pemasukan':'Pengeluaran Tidak Terduga' }}</h2><div class="row g-3 mt-1">
<div class="col-md-6"><label for="occurred_on" class="form-label">Tanggal transaksi</label><input class="form-control" type="date" id="occurred_on" name="occurred_on" value="{{ old('occurred_on', date('Y-m-d')) }}" min="{{ config('finance.start') }}" max="{{ min(date('Y-m-d'),config('finance.end')) }}" required></div>
<div class="col-md-6"><label for="amount" class="form-label">Nominal (Rp)</label><input class="form-control" id="amount" name="amount" type="number" min="1" max="1000000000000" step="1" value="{{ old('amount') }}" required></div>
<div class="col-md-6"><label for="category" class="form-label">Kategori</label><select class="form-select" id="category" name="category" required><option value="">Pilih kategori</option>@foreach(config('finance.categories.'.$kind) as $category)<option @selected(old('category')===$category)>{{ $category }}</option>@endforeach</select></div>
<div class="col-md-6"><label for="method" class="form-label">Metode</label><select class="form-select" id="method" name="method"><option>Transfer</option><option @selected(old('method')==='Tunai')>Tunai</option></select></div>
<div class="col-12"><label for="description" class="form-label">Keterangan transaksi</label><textarea class="form-control" id="description" name="description" maxlength="2000" rows="3" required>{{ old('description') }}</textarea></div>
<div class="col-12"><label for="receipt" class="form-label">Bukti (opsional, JPG/PNG/WebP/PDF, maksimal 5 MB)</label><input class="form-control" id="receipt" name="receipt" type="file" accept="image/jpeg,image/png,image/webp,application/pdf"></div>
<div class="col-12"><button class="btn btn-navy" type="submit">Simpan Transaksi</button></div></div></form></div>@endif
<section class="finance-panel">@include('bendahara.finance.filters')<div class="d-flex gap-2 mb-3">@foreach(['xlsx'=>'Excel','pdf'=>'PDF'] as $format=>$label)<a class="btn btn-sm btn-outline-navy" href="{{ route('finance.export',['format'=>$format,'kind'=>$kind]+request()->only('month','q')) }}">Ekspor {{ $label }}</a>@endforeach</div>@include('bendahara.finance.table')</section>
</div>
@endsection
