
@if($account->isStudentAccount())
<section class="admin-panel mt-4" id="jabatan">
<h2>Kelola Jabatan</h2>
<p>Akun KBH tetap tersedia. Satu mahasiswa memiliki satu jabatan aktif. Pilih “Tanpa jabatan” untuk mencabut akses pengurus.</p>
<p>Jabatan saat ini: <strong>{{ config('superadmin.roles.'.$account->assignedRole()) }}</strong></p>
<form method="POST" action="{{ route('admin.users.appointment', $account->encryptedAccountId()) }}" class="admin-form">
@csrf @method('PUT')
<input type="hidden" name="expected_role" value="{{ $account->assignedRole() }}">
<input type="hidden" name="expected_department" value="{{ $account->department }}">
<label class="form-label" for="appointment_role">Jabatan</label>
<select class="form-select mb-3" id="appointment_role" name="appointment_role" required>
<option value="kbh" @selected(old('appointment_role', $account->assignedRole()) === 'kbh')>Tanpa jabatan — KBH saja (cabut jabatan)</option>
@foreach(\App\Models\User::OFFICER_ROLES as $role)
<option value="{{ $role }}" @selected(old('appointment_role', $account->assignedRole()) === $role)>{{ config('superadmin.roles.'.$role) }}</option>
@endforeach
</select>
<label class="form-label" for="appointment_department">Departemen (wajib untuk koordinator / anggota)</label>
<select class="form-select mb-3" id="appointment_department" name="appointment_department">
<option value="">Tidak ada</option>
@foreach(config('superadmin.departments') as $key => $label)
<option value="{{ $key }}" @selected(old('appointment_department', $account->department) === $key)>{{ $label }}</option>
@endforeach
</select>
<label class="form-label" for="period">Periode kepengurusan</label>
<input class="form-control mb-3" id="period" name="period" maxlength="80" placeholder="Contoh: 2026/2027" value="{{ old('period', \App\Support\Governance::active()?->name) }}" required>
<label class="form-label" for="appointment_reason">Alasan penetapan / perubahan / pencabutan</label>
<textarea class="form-control mb-3" id="appointment_reason" name="reason" maxlength="1000" required>{{ old('reason') }}</textarea>
<button class="btn btn-navy">Simpan Jabatan</button>
</form>
</section>
@endif
@if($account->id !== auth()->id() && $account->assignedRole() !== 'super_admin')
<section class="admin-panel mt-4">
<h2>Status Akun</h2>
<p>Nonaktifkan akun untuk menghentikan seluruh akses login. Akun, jabatan, dan riwayat tetap tersimpan.</p>
<form method="POST" action="{{ route('admin.users.status', $account->encryptedAccountId()) }}" class="admin-form">
@csrf @method('PUT')
<input type="hidden" name="active" value="{{ $account->is_active ? '0' : '1' }}">
<label class="form-label" for="status_reason">Alasan perubahan status</label>
<textarea class="form-control mb-3" name="reason" id="status_reason" maxlength="1000" required>{{ old('reason') }}</textarea>
<button class="btn {{ $account->is_active ? 'btn-outline-danger' : 'btn-success' }}">{{ $account->is_active ? 'Nonaktifkan Akun' : 'Aktifkan Akun' }}</button>
</form>
</section>
@endif
<section class="admin-panel mt-4">
<h2>Riwayat Perubahan Akses</h2>
<p class="text-secondary">30 perubahan manual terakhir. Penetapan otomatis tetap tercatat pada hasil seleksi.</p>
@forelse($accountHistory as $entry)
<article class="border-bottom py-3">
<strong>{{ $entry->summary }}</strong>
<small class="d-block text-secondary">{{ $entry->created_at->timezone('Asia/Makassar')->format('d M Y H:i') }} WITA · {{ $entry->actor_name }}</small>
@if($entry->event === 'account_appointment')
<p class="mb-1">{{ config('superadmin.roles.'.data_get($entry->context, 'before.role')) }} → {{ config('superadmin.roles.'.data_get($entry->context, 'after.role')) }} · Periode {{ $entry->context['period'] }}
@if(data_get($entry->context, 'after.department')) · {{ config('superadmin.departments.'.data_get($entry->context, 'after.department')) }} @endif</p>
@else
<p class="mb-1">{{ $entry->context['after'] ? 'Akun diaktifkan' : 'Akun dinonaktifkan' }}</p>
@endif
<p class="mb-0">{{ $entry->context['reason'] }}</p>
</article>
@empty
<p>Belum ada perubahan manual yang tercatat.</p>
@endforelse
</section>
