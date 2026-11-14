<?php

namespace App\Http\Controllers;

use App\Models\HimsiRegistration;
use App\Models\KbhIuranPembayaran;
use App\Models\KbhPendaftaranSeleksi;
use App\Models\KpumCandidate;
use App\Models\MemberDocument;
use App\Support\RecruitmentWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberArchiveController extends Controller
{
    private function authorizeMember(Request $request): void
    {
        abort_unless($request->user()->isStudentAccount(), 403);
    }

    private function entries(Request $request): Collection
    {
        $user = $request->user();
        $entries = collect();
        $append = function (string $source, int $record, string $field, string $category, string $title, ?string $path, string $name, string $origin, mixed $date) use ($entries): void {
            if ($path) {
                $entries->push(compact('source', 'record', 'field', 'category', 'title', 'path', 'name', 'origin', 'date'));
            }
        };
        foreach (MemberDocument::where('user_id', $user->id)->latest()->get() as $document) {
            $append('upload', $document->id, 'file', $document->category, $document->title, $document->path, $document->original_name, 'Arsip pribadi', $document->created_at);
        }
        if ($registration = $user->registration) {
            foreach (HimsiRegistration::documents() as $field => $label) {
                $category = ['student_card' => 'KTM', 'active_letter' => 'Surat Aktif Kuliah', 'certificate' => 'Sertifikat'][$field];
                $path = $registration->{$field.'_path'};
                $append('registration', $registration->id, $field, $category, $label, $path, $field.'.'.pathinfo($path ?? '', PATHINFO_EXTENSION), 'Pendaftaran anggota', $registration->created_at);
            }
        }
        foreach (KbhPendaftaranSeleksi::with('period')->where('user_id', $user->id)->get() as $application) {
            $documents = array_filter(array_merge(['khs' => $application->khs_path, 'certificate' => $application->sertifikat_path], $application->documents ?? []));
            $labels = RecruitmentWorkflow::documents($application->period?->batch ?? 2) + ['certificate' => 'Sertifikat'];
            foreach ($documents as $field => $path) {
                $category = match ($field) {
                    'ktm' => 'KTM', 'khs', 'transcript' => 'KHS', default => 'Sertifikat'
                };
                $append('selection', $application->id, $field, $category, $labels[$field] ?? $field, $path, $field.'.'.pathinfo($path, PATHINFO_EXTENSION), 'Seleksi · '.($application->period?->nama ?? 'Pengurus'), $application->created_at);
            }
        }
        foreach (KpumCandidate::where('user_id', $user->id)->get() as $candidate) {
            foreach ($candidate->documents ?? [] as $field => $path) {
                $append('candidate', $candidate->id, $field, str_contains($field, 'ktm') ? 'KTM' : 'Lainnya', ucwords(str_replace('_', ' ', $field)), $path, $field.'.'.pathinfo($path, PATHINFO_EXTENSION), 'Pendaftaran Ketua Umum', $candidate->created_at);
            }
        }
        foreach (KbhIuranPembayaran::where('user_id', $user->id)->whereNotNull('bukti_path')->get() as $payment) {
            $append('dues', $payment->id, 'proof', 'Bukti Iuran', 'Iuran '.$payment->bulan.'/'.$payment->tahun, $payment->bukti_path, $payment->bukti_nama ?? 'bukti.'.pathinfo($payment->bukti_path, PATHINFO_EXTENSION), 'Pembayaran iuran', $payment->created_at);
        }

        return $entries->sortByDesc('date')->values();
    }

    public function index(Request $request): View
    {
        $this->authorizeMember($request);
        $data = $request->validate(['category' => ['nullable', Rule::in(MemberDocument::CATEGORIES)], 'q' => ['nullable', 'string', 'max:120'], 'page' => ['nullable', 'integer', 'min:1'], 'sort' => ['nullable', Rule::in(['newest', 'oldest', 'name'])], 'view' => ['nullable', Rule::in(['grid', 'list'])]]);
        $entries = $this->entries($request);
        $counts = $entries->countBy('category');
        $total = $entries->count();
        $filtered = $entries->filter(fn (array $entry) => (! $request->filled('category') || $entry['category'] === $data['category'])
            && (! $request->filled('q') || str_contains(mb_strtolower($entry['title'].' '.$entry['origin']), mb_strtolower($data['q']))))->values();
        $filtered = match ($request->input('sort')) {
            'oldest' => $filtered->sortBy('date')->values(),
            'name' => $filtered->sortBy(fn (array $entry): string => mb_strtolower($entry['title']))->values(),
            default => $filtered->sortByDesc('date')->values(),
        };
        $page = $request->integer('page', 1);
        $documents = (new LengthAwarePaginator($filtered->forPage($page, 12)->values(), $filtered->count(), 12, $page, ['path' => $request->url()]))->withQueryString();

        $documents->setCollection($documents->getCollection()->map(function (array $entry): array {
            $disk = $entry['source'] === 'registration' ? Storage::build(['driver' => 'local', 'root' => storage_path('app/private/himsi-registration')]) : Storage::disk('local');
            $entry['size'] = $disk->exists($entry['path']) ? $disk->size($entry['path']) : null;

            return $entry;
        }));

        return view('kbh.archive', compact('documents', 'counts', 'total'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeMember($request);
        $data = $request->validate([
            'category' => ['required', Rule::in(MemberDocument::CATEGORIES)], 'title' => ['required', 'string', 'max:150'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:10240'],
        ]);
        $file = $request->file('document');
        $path = $file->store('member-archives/'.$request->user()->id, 'local');
        abort_unless($path, 500, 'Berkas gagal disimpan.');
        try {
            MemberDocument::create([
                'user_id' => $request->user()->id, 'category' => $data['category'], 'title' => $data['title'],
                'path' => $path, 'original_name' => $file->getClientOriginalName(), 'size' => $file->getSize(), 'mime_type' => $file->getMimeType(),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);
            throw $exception;
        }

        return back()->with('success', 'Dokumen tersimpan di arsip pribadi Anda.');
    }

    public function file(Request $request, string $source, int $record, string $field): StreamedResponse
    {
        $this->authorizeMember($request);
        $entry = $this->entries($request)->first(fn (array $entry) => $entry['source'] === $source && $entry['record'] === $record && $entry['field'] === $field);
        abort_unless($entry, 404);
        $disk = $source === 'registration' ? Storage::build(['driver' => 'local', 'root' => storage_path('app/private/himsi-registration')]) : Storage::disk('local');
        abort_unless($disk->exists($entry['path']), 404);

        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if ($request->boolean('preview') && in_array($disk->mimeType($entry['path']), ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true)) {
            return $disk->response($entry['path'], $entry['name'], $headers);
        }

        return $disk->download($entry['path'], $entry['name'], $headers);
    }

    public function update(Request $request, int $document): RedirectResponse
    {
        $this->authorizeMember($request);
        $record = MemberDocument::where('user_id', $request->user()->id)->findOrFail($document);
        $data = $request->validate(['title' => ['required', 'string', 'max:150'], 'category' => ['required', Rule::in(MemberDocument::CATEGORIES)]]);
        $record->update($data);

        return back()->with('success', 'Detail dokumen diperbarui.');
    }

    public function destroy(Request $request, int $document): RedirectResponse
    {
        $this->authorizeMember($request);
        $record = MemberDocument::where('user_id', $request->user()->id)->findOrFail($document);
        $path = $record->path;
        $record->delete();
        Storage::disk('local')->delete($path);

        return back()->with('success', 'Dokumen arsip dihapus.');
    }
}
