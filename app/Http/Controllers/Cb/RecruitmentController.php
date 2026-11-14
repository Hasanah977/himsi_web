<?php

namespace App\Http\Controllers\Cb;

use App\Http\Controllers\Controller;
use App\Models\HimsiRegistration;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RecruitmentController extends Controller
{
    private function disk(): FilesystemAdapter
    {
        return Storage::build(['driver' => 'local', 'root' => storage_path('app/private/himsi-registration'), 'throw' => true]);
    }

    public function index(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::in(array_keys(HimsiRegistration::labels()))], 'angkatan' => ['nullable', 'integer', 'between:2000,2100']]);
        $query = HimsiRegistration::with('user');
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('angkatan')) {
            $query->where('angkatan', $request->integer('angkatan'));
        }
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('nim', 'like', '%'.$request->input('q').'%')->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%'.$request->input('q').'%'));
            });
        }

        return view('cb.recruitment', [
            'registrations' => $query->latest()->paginate(15)->withQueryString(),
            'counts' => HimsiRegistration::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'years' => HimsiRegistration::distinct()->orderByDesc('angkatan')->pluck('angkatan'),
        ]);
    }

    public function show(HimsiRegistration $registration): View
    {
        $registration->load('user');
        $available = [];
        foreach (HimsiRegistration::documents() as $key => $label) {
            $path = $registration->{$key.'_path'};
            $available[$key] = $path && $this->disk()->exists($path);
        }

        return view('cb.review', compact('registration', 'available'));
    }

    public function review(Request $request, HimsiRegistration $registration): RedirectResponse
    {
        $rules = ['decision' => ['required', Rule::in(['approved', 'returned', 'rejected'])], 'note' => ['required', 'string', 'max:2000'], 'version' => ['required', 'integer', 'min:0']];
        foreach (HimsiRegistration::documents() as $key => $label) {
            $rules['reviews.'.$key] = ['required', Rule::in(['valid', 'invalid', 'clarify'])];
        }
        $data = $request->validate($rules);
        DB::transaction(function () use ($registration, $data) {
            $record = HimsiRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();
            $this->editable($record, (int) $data['version']);
            if ($data['decision'] === 'approved') {
                foreach (HimsiRegistration::documents() as $key => $label) {
                    $path = $record->{$key.'_path'};
                    if ($data['reviews'][$key] !== 'valid' || ! $path || ! $this->disk()->exists($path)) {
                        throw ValidationException::withMessages(['decision' => 'Kelulusan membutuhkan tiga berkas yang tersedia dan dinyatakan valid.']);
                    }
                }
                $user = User::whereKey($record->user_id)->lockForUpdate()->firstOrFail();
                if (! in_array($user->role, ['pending', 'kbh'], true)) {
                    throw ValidationException::withMessages(['decision' => 'Role akun sudah diatur oleh Super Admin. Hubungi Super Admin sebelum melanjutkan.']);
                }
                $user->forceFill(['role' => 'kbh', 'is_active' => true])->save();
            }
            $record->update(['status' => $data['decision'], 'document_reviews' => $data['reviews'], 'review_note' => $data['note'], 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_version' => $record->review_version + 1]);
            DashboardController::record('Verifikasi rekrutmen KBH', $record->user->name.' · '.$record->nim, HimsiRegistration::labels()[$data['decision']].' — '.$data['note']);
        });

        return back()->with('success', $data['decision'] === 'approved' ? 'Pendaftar lolos KBH. Akun diaktifkan dengan role KBH.' : 'Keputusan dan catatan pemeriksaan disimpan.');
    }

    private function editable(HimsiRegistration $registration, int $version): void
    {
        if (in_array($registration->status, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['review' => 'Keputusan akhir sudah disimpan. Data verifikasi dikunci.']);
        }
        if ($registration->review_version !== $version) {
            throw ValidationException::withMessages(['review' => 'Data sudah berubah. Muat ulang halaman sebelum melanjutkan.']);
        }
    }

    public function replace(Request $request, HimsiRegistration $registration, bool $applicant = false): RedirectResponse
    {
        $rules = ['version' => ['required', 'integer', 'min:0']];
        foreach (HimsiRegistration::documents() as $key => $label) {
            $rules[$key] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
        }
        $data = $request->validate($rules);
        $newPaths = [];
        $oldPaths = [];
        try {
            DB::transaction(function () use ($request, $registration, $data, $applicant, &$newPaths, &$oldPaths) {
                $record = HimsiRegistration::whereKey($registration->id)->lockForUpdate()->firstOrFail();
                if ($applicant) {
                    abort_unless($record->user_id === $request->user()->id && $record->status === 'returned', 403);
                }
                $this->editable($record, (int) $data['version']);
                foreach (HimsiRegistration::documents() as $key => $label) {
                    if ($request->hasFile($key)) {
                        $path = $this->disk()->putFile('documents', $request->file($key));
                        $newPaths[] = $path;
                        $oldPaths[] = $record->{$key.'_path'};
                        $record->{$key.'_path'} = $path;
                    }
                }
                if (! $newPaths) {
                    throw ValidationException::withMessages(['files' => 'Pilih minimal satu berkas pengganti.']);
                }
                $record->fill(['status' => 'pending', 'document_reviews' => null, 'review_note' => null, 'reviewed_by' => null, 'reviewed_at' => null, 'review_version' => $record->review_version + 1])->save();
                DashboardController::record('Berkas pendaftaran diperbarui', $record->user->name.' · '.$record->nim, 'Menunggu pemeriksaan ulang.');
            });
        } catch (\Throwable $e) {
            $this->disk()->delete($newPaths);
            throw $e;
        }
        $this->disk()->delete(array_values(array_filter($oldPaths)));

        return back()->with('success', 'Berkas diperbarui. Lakukan pemeriksaan ulang.');
    }

    public function document(HimsiRegistration $registration, string $document): StreamedResponse
    {
        abort_unless(array_key_exists($document, HimsiRegistration::documents()), 404);
        $path = $registration->{$document.'_path'};
        abort_unless($path && $this->disk()->exists($path), 404);

        return $this->disk()->response($path, $document.'.'.pathinfo($path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'], request()->boolean('download') ? 'attachment' : 'inline');
    }
}
