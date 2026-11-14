<?php

namespace App\Http\Controllers\Kpum;

use App\Http\Controllers\Controller;
use App\Models\KpumCandidate;
use App\Models\KpumElection;
use App\Models\NotifikasiHimsi;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function toggle(Request $request): RedirectResponse
    {
        $data = $request->validate(['open' => ['required', 'boolean']]);
        DB::transaction(function () use ($data): void {
            $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
            $election->requireDraft();
            $open = (bool) $data['open'];
            if ((bool) $election->registration_open === $open) {
                return;
            }
            $election->update(['registration_open' => $open]);
            $election->record($open ? 'Pendaftaran Ketua Umum dibuka' : 'Pendaftaran Ketua Umum ditutup');
            if ($open) {
                User::where('role', 'kbh')->where('is_active', true)->whereDoesntHave('registration', fn ($query) => $query->where('status', '!=', 'approved'))
                    ->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'Pendaftaran Ketua Umum dibuka', 'Daftarkan diri melalui menu Seleksi Pengurus. Siapkan visi, misi, foto, dan berkas persyaratan.', route('kbh.seleksi.index', [], false), 'seleksi'));
            }
        });

        return back()->with('success', $data['open'] ? 'Pendaftaran Ketua Umum dibuka untuk KBH.' : 'Pendaftaran Ketua Umum ditutup.');
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->assignedRole() === 'kbh', 403);
        $election = KpumElection::findOrFail(1);
        $election->requireDraft();
        abort_unless($election->registration_open, 403, 'Pendaftaran Ketua Umum belum dibuka.');
        $candidate = KpumCandidate::where('election_id', 1)->where('user_id', $request->user()->id)->first();
        abort_if($candidate && $candidate->status !== 'rejected', 403, 'Pendaftaran Anda sudah diterima.');

        return view('kbh.seleksi.chair-form', compact('election', 'candidate'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->assignedRole() === 'kbh', 403);
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'nim' => ['required', 'alpha_dash', 'max:40'],
            'angkatan' => ['required', 'integer', 'between:2000,'.now()->year],
            'ipk' => ['required', 'numeric', 'between:0,4'],
            'vision' => ['required', 'string', 'max:200'],
            'mission' => ['required', 'string', 'max:500'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
        foreach (KpumCandidate::documentLabels() as $key => $label) {
            $rules['documents.'.$key] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:3072'];
        }
        $data = $request->validate($rules);
        if ($request->user()->nim && $data['nim'] !== $request->user()->nim) {
            throw ValidationException::withMessages(['nim' => 'NIM harus sesuai akun KBH Anda.']);
        }
        $paths = [];
        $oldPaths = [];
        try {
            DB::transaction(function () use ($request, $data, &$paths, &$oldPaths): void {
                $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
                $election->requireDraft();
                if (! $election->registration_open) {
                    throw ValidationException::withMessages(['registration' => 'Pendaftaran Ketua Umum sudah ditutup.']);
                }
                $candidate = KpumCandidate::where('election_id', 1)->where('user_id', $request->user()->id)->first();
                if ($candidate && $candidate->status !== 'rejected') {
                    throw ValidationException::withMessages(['registration' => 'Anda sudah mendaftar. Tunggu hasil verifikasi KPUM.']);
                }
                if (KpumCandidate::where('election_id', 1)->where('nim', $data['nim'])->when($candidate, fn ($query) => $query->whereKeyNot($candidate->id))->exists()) {
                    throw ValidationException::withMessages(['nim' => 'NIM sudah terdaftar sebagai kandidat.']);
                }
                $oldPaths = $candidate ? array_filter(array_merge([$candidate->photo], array_values($candidate->documents ?? []))) : [];
                foreach (array_merge(['photo'], array_keys(KpumCandidate::documentLabels())) as $key) {
                    $path = $request->file($key === 'photo' ? 'photo' : 'documents.'.$key)->store('kpum', 'local');
                    if (! $path) {
                        throw ValidationException::withMessages(['photo' => 'Berkas gagal disimpan. Silakan coba lagi.']);
                    }
                    $paths[$key] = $path;
                }
                $candidate ??= new KpumCandidate;
                $candidate->fill(collect($data)->only(['name', 'nim', 'angkatan', 'ipk', 'vision', 'mission'])->all());
                $candidate->fill(['election_id' => 1, 'user_id' => $request->user()->id, 'number' => null, 'status' => 'pending', 'review_note' => null, 'photo' => $paths['photo'], 'documents' => array_diff_key($paths, ['photo' => true])])->save();
                $election->record('Pendaftaran mandiri kandidat', $candidate->name);
                User::where('role', 'panitia_kpum')->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'Pendaftaran Ketua Umum baru', $candidate->name.' mengirim berkas untuk diverifikasi.', route('kpum.candidates', [], false), 'kpum'));
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete(array_values($paths));
            throw $exception;
        }
        Storage::disk('local')->delete(array_values($oldPaths));

        return redirect()->route('kbh.seleksi.index')->with('success', 'Pendaftaran Ketua Umum terkirim. Hasil verifikasi akan masuk ke notifikasi Anda.');
    }
}
