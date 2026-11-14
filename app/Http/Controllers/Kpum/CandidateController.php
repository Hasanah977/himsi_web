<?php

namespace App\Http\Controllers\Kpum;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kpum\CandidateRequest;
use App\Models\KpumCandidate;
use App\Models\KpumElection;
use App\Models\NotifikasiHimsi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateController extends Controller
{
    public function create(): View
    {
        return app(ElectionController::class)->candidates(request());
    }

    public function edit(KpumCandidate $candidate): View
    {
        abort_unless($candidate->election_id === 1, 404);
        $election = KpumElection::findOrFail(1);
        $election->requireDraft();

        return view('kpum.candidate-form', compact('election', 'candidate'));
    }

    public function store(CandidateRequest $request): RedirectResponse
    {
        abort(403, 'Kandidat mendaftar sendiri melalui akun KBH.');
    }

    public function update(CandidateRequest $request, KpumCandidate $candidate): RedirectResponse
    {
        abort_unless($candidate->election_id === 1, 404);

        return $this->save($request, $candidate);
    }

    private function save(CandidateRequest $request, KpumCandidate $candidate): RedirectResponse
    {
        $newFiles = [];
        $oldFiles = [];
        try {
            DB::transaction(function () use ($request, $candidate, &$newFiles, &$oldFiles) {
                $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
                $election->requireDraft();
                if ($candidate->exists) {
                    $candidate->refresh();
                }
                $candidate->fill($request->safe()->only(['name', 'nim', 'number', 'angkatan', 'ipk', 'vision', 'mission']));
                $candidate->election_id = $election->id;
                $documents = $candidate->documents ?? [];
                foreach (array_merge(['photo'], array_keys(KpumCandidate::documentLabels())) as $key) {
                    $input = $key === 'photo' ? 'photo' : 'documents.'.$key;
                    if ($request->hasFile($input)) {
                        $path = $request->file($input)->store('kpum', 'local');
                        if ($path === false) {
                            throw ValidationException::withMessages(['upload' => 'Berkas gagal disimpan. Periksa izin folder penyimpanan.']);
                        }
                        $newFiles[] = $path;
                        if ($key === 'photo') {
                            $oldFiles[] = $candidate->photo;
                            $candidate->photo = $path;
                        } else {
                            $oldFiles[] = $documents[$key] ?? null;
                            $documents[$key] = $path;
                        }
                    }
                }
                $candidate->documents = $documents;
                $candidate->status = 'pending';
                $candidate->review_note = null;
                $candidate->save();
                $election->record('Data kandidat disimpan', $candidate->name.' — perlu verifikasi');
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($newFiles);
            throw $exception;
        }
        Storage::disk('local')->delete(array_values(array_filter($oldFiles)));

        return redirect()->route('kpum.candidates')->with('success', 'Kandidat disimpan. Silakan periksa berkas dan lakukan verifikasi.');
    }

    public function review(Request $request, KpumCandidate $candidate): RedirectResponse
    {
        abort_unless($candidate->election_id === 1, 404);
        if (! $request->has('number') && $candidate->number) {
            $request->merge(['number' => $candidate->number]);
        }
        $data = $request->validate([
            'number' => ['required_if:status,approved', 'nullable', 'integer', 'between:1,99'],
            'status' => ['required', Rule::in(['approved', 'rejected'])],
            'note' => ['required', 'string', 'max:1000'],
            'checked' => ['required_if:status,approved', 'array'],
            'checked.*' => [Rule::in(array_keys(KpumCandidate::documentLabels()))],
        ]);
        DB::transaction(function () use ($data, $candidate) {
            $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
            $election->requireDraft();
            $candidate->refresh();
            if ($data['status'] === 'approved') {
                if (KpumCandidate::where('election_id', 1)->where('number', $data['number'])->whereKeyNot($candidate->id)->exists()) {
                    throw ValidationException::withMessages(['number' => 'Nomor urut sudah digunakan kandidat lain.']);
                }
                foreach (KpumCandidate::documentLabels() as $key => $label) {
                    if (empty($candidate->documents[$key]) || ! Storage::disk('local')->exists($candidate->documents[$key]) || ! in_array($key, $data['checked'] ?? [], true)) {
                        throw ValidationException::withMessages(['review' => 'Lengkapi dan periksa seluruh berkas sebelum menyetujui kandidat.']);
                    }
                }
                if ((float) $candidate->ipk < 3) {
                    throw ValidationException::withMessages(['review' => 'IPK kandidat harus minimal 3,00 sesuai persyaratan pada formulir.']);
                }
            }
            $candidate->update(['status' => $data['status'], 'review_note' => $data['note'], 'number' => $data['status'] === 'approved' ? $data['number'] : null]);
            if ($candidate->user_id) {
                NotifikasiHimsi::kirim($candidate->user_id, 'Hasil verifikasi calon Ketua Umum', ($data['status'] === 'approved' ? 'Selamat, Anda lolos verifikasi dengan nomor urut '.$candidate->number.'. ' : 'Anda belum lolos verifikasi. ').$data['note'], route('kbh.seleksi.index', [], false), 'seleksi');
            }
            $election->record('Verifikasi kandidat', $candidate->name.' — '.$candidate->label().': '.$data['note']);
        });

        return back()->with('success', 'Hasil verifikasi tersimpan.');
    }

    public function destroy(KpumCandidate $candidate): RedirectResponse
    {
        abort_unless($candidate->election_id === 1, 404);
        $paths = [];
        DB::transaction(function () use ($candidate, &$paths) {
            $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
            $election->requireDraft();
            $candidate->refresh();
            $paths = array_filter(array_merge([$candidate->photo], array_values($candidate->documents ?? [])));
            $election->record('Kandidat dihapus', $candidate->name);
            $candidate->delete();
        });
        Storage::disk('local')->delete(array_values($paths));

        return back()->with('success', 'Kandidat dihapus.');
    }

    public function document(KpumCandidate $candidate, string $document): StreamedResponse
    {
        abort_unless($candidate->election_id === 1 && array_key_exists($document, KpumCandidate::documentLabels()), 404);
        $path = $candidate->documents[$document] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $document.'.'.pathinfo($path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function photo(Request $request, KpumCandidate $candidate): StreamedResponse
    {
        $manager = $request->user()->isSuperAdmin() || $request->user()->role === 'panitia_kpum';
        $eligible = DB::table('kpum_electors')->where('election_id', $candidate->election_id)->where('user_id', $request->user()->id)->exists();
        abort_unless($candidate->election_id === 1 && ($manager || ($eligible && $candidate->status === 'approved')), 403);
        abort_unless($candidate->photo && Storage::disk('local')->exists($candidate->photo), 404);

        return Storage::disk('local')->response($candidate->photo, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
