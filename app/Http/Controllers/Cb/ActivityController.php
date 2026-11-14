<?php

namespace App\Http\Controllers\Cb;

use App\Http\Controllers\Controller;
use App\Models\CbActivity;
use App\Models\CbParticipant;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $query = CbActivity::query();
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->input('q').'%');
        }

        return view('cb.activities', ['activities' => $query->orderByDesc('starts_at')->paginate(12)->withQueryString()]);
    }

    public function create(): View
    {
        return view('cb.activity-form', ['activity' => new CbActivity]);
    }

    public function edit(CbActivity $activity): View
    {
        return view('cb.activity-form', compact('activity'));
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, new CbActivity);
    }

    public function update(Request $request, CbActivity $activity): RedirectResponse
    {
        return $this->save($request, $activity);
    }

    private function save(Request $request, CbActivity $activity): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'], 'period' => ['required', 'string', 'max:40'],
            'location' => ['required', 'string', 'max:200'], 'description' => ['required', 'string', 'max:5000'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'], 'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
            'status' => ['required', Rule::in(array_keys(CbActivity::labels()))],
            'report' => ['nullable', 'required_if:status,completed', 'string', 'max:20000'],
        ], ['report.required_if' => 'Laporan kegiatan wajib diisi sebelum menandai kegiatan selesai.']);
        foreach (['starts_at', 'ends_at'] as $key) {
            $data[$key] = Carbon::createFromFormat('Y-m-d\TH:i', $data[$key], 'Asia/Makassar')->startOfMinute()->timezone(config('app.timezone'));
        }
        DB::transaction(function () use ($activity, $data) {
            if ($activity->exists) {
                $activity->refresh();
                $locked = CbActivity::whereKey($activity->id)->lockForUpdate()->firstOrFail();
                if ($locked->period !== $data['period'] && DB::table('cb_attendances')->where('activity_id', $activity->id)->exists()) {
                    throw ValidationException::withMessages(['period' => 'Periode kegiatan dengan peserta terdaftar tidak dapat diubah.']);
                }
            }
            $activity->fill($data)->save();
            DashboardController::record('Kegiatan CB disimpan', $activity->title, CbActivity::labels()[$activity->status]);
        });

        return redirect()->route('cb.activities.show', $activity)->with('success', 'Kegiatan dan laporan tersimpan.');
    }

    private function details(CbActivity $activity): array
    {
        $attendances = DB::table('cb_attendances')->join('cb_participants', 'cb_participants.id', '=', 'cb_attendances.participant_id')->where('activity_id', $activity->id)->select('cb_attendances.*', 'cb_participants.name', 'cb_participants.nim')->orderBy('cb_participants.name')->get();
        $attachments = DB::table('cb_attachments')->where('activity_id', $activity->id)->latest('id')->get();

        return compact('activity', 'attendances', 'attachments');
    }

    public function show(CbActivity $activity): View
    {
        $data = $this->details($activity);
        $available = CbParticipant::where('period', $activity->period)->where('status', 'accepted')->whereNotIn('id', $data['attendances']->pluck('participant_id'))->orderBy('name')->get();

        return view('cb.activity', $data + compact('available'));
    }

    public function report(CbActivity $activity): View
    {
        return view('cb.report', $this->details($activity));
    }

    public function enroll(Request $request, CbActivity $activity): RedirectResponse
    {
        $data = $request->validate(['participants' => ['required', 'array', 'min:1', 'max:500'], 'participants.*' => ['integer', 'distinct']]);
        DB::transaction(function () use ($activity, $data) {
            $activity = CbActivity::whereKey($activity->id)->lockForUpdate()->firstOrFail();
            $this->requireOpen($activity);
            $participants = CbParticipant::whereIn('id', $data['participants'])->where('period', $activity->period)->where('status', 'accepted')->lockForUpdate()->get();
            if ($participants->count() !== count($data['participants'])) {
                throw ValidationException::withMessages(['participants' => 'Peserta harus diterima dan berasal dari periode yang sama.']);
            }
            foreach ($participants as $participant) {
                DB::table('cb_attendances')->insertOrIgnore(['activity_id' => $activity->id, 'participant_id' => $participant->id, 'status' => 'registered', 'created_at' => now(), 'updated_at' => now()]);
            }
            DashboardController::record('Peserta ditambahkan ke kegiatan', $activity->title, $participants->count().' peserta');
        });

        return back()->with('success', 'Peserta ditambahkan ke kegiatan.');
    }

    private function requireOpen(CbActivity $activity): void
    {
        if (in_array($activity->status, ['completed', 'cancelled'], true)) {
            throw ValidationException::withMessages(['activity' => 'Kehadiran terkunci untuk kegiatan selesai atau dibatalkan.']);
        }
    }

    public function attendance(Request $request, CbActivity $activity, int $attendance): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['registered', 'present', 'excused', 'absent'])]]);
        DB::transaction(function () use ($activity, $attendance, $data) {
            $activity = CbActivity::whereKey($activity->id)->lockForUpdate()->firstOrFail();
            $this->requireOpen($activity);
            $entry = DB::table('cb_attendances')->where('activity_id', $activity->id)->where('id', $attendance)->first();
            abort_unless($entry, 404);
            DB::table('cb_attendances')->where('id', $entry->id)->update(['status' => $data['status'], 'updated_at' => now()]);
            DashboardController::record('Kehadiran peserta diperbarui', $activity->title, CbParticipant::findOrFail($entry->participant_id)->name.' — '.$data['status']);
        });

        return back()->with('success', 'Kehadiran tersimpan.');
    }

    public function upload(Request $request, CbActivity $activity): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:150'], 'kind' => ['required', Rule::in(['photo', 'report'])], 'file' => ['required', 'file', 'max:5120', $request->input('kind') === 'photo' ? 'mimes:jpg,jpeg,png,webp' : 'mimes:pdf']]);
        $path = $request->file('file')->store('cb-documentation', 'local');
        if (! $path) {
            throw ValidationException::withMessages(['file' => 'Berkas gagal disimpan.']);
        }
        try {
            DB::transaction(function () use ($activity, $data, $path) {
                DB::table('cb_attachments')->insert(['activity_id' => $activity->id, 'title' => $data['title'], 'kind' => $data['kind'], 'path' => $path, 'created_at' => now(), 'updated_at' => now()]);
                DashboardController::record('Dokumentasi diunggah', $activity->title, $data['title']);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }

        return back()->with('success', 'Dokumentasi kegiatan disimpan.');
    }

    public function file(CbActivity $activity, int $attachment, Request $request): StreamedResponse
    {
        $file = DB::table('cb_attachments')->where('activity_id', $activity->id)->where('id', $attachment)->first();
        abort_unless($file && Storage::disk('local')->exists($file->path), 404);
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if ($file->kind === 'photo' && ! $request->boolean('download')) {
            return Storage::disk('local')->response($file->path, null, $headers);
        }

        return Storage::disk('local')->download($file->path, 'dokumentasi-'.$file->id.'.'.pathinfo($file->path, PATHINFO_EXTENSION), $headers);
    }

    public function deleteFile(CbActivity $activity, int $attachment): RedirectResponse
    {
        $file = DB::table('cb_attachments')->where('activity_id', $activity->id)->where('id', $attachment)->first();
        abort_unless($file, 404);
        DB::transaction(function () use ($file, $activity) {
            DB::table('cb_attachments')->where('id', $file->id)->delete();
            DashboardController::record('Dokumentasi dihapus', $activity->title, $file->title);
        });
        Storage::disk('local')->delete($file->path);

        return back()->with('success', 'Dokumentasi dihapus.');
    }
}
