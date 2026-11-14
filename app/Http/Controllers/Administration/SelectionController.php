<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\KbhPendaftaranSeleksi;
use App\Models\KbhPeriode;
use App\Models\NotifikasiHimsi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SelectionController extends Controller
{
    private function authorize(Request $request): void
    {
        abort_unless(in_array($request->user()->role, ['sekretaris_umum', 'super_admin'], true), 403);
    }

    public function index(Request $request): View
    {
        return app(RecruitmentController::class)->index($request);
    }

    public function period(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'ketua_umum' && ! KbhPeriode::whereNotNull('batch')->exists(), 403);
        $data = $request->validate(['nama' => 'required|string|max:150', 'mulai_pada' => 'required|date', 'selesai_pada' => 'required|date|after:mulai_pada']);
        foreach (['mulai_pada', 'selesai_pada'] as $key) {
            $data[$key] = Carbon::parse($data[$key], 'Asia/Makassar')->setTimezone(config('app.timezone'));
        }
        DB::transaction(function () use ($data) {
            User::where('role', 'super_admin')->orderBy('id')->lockForUpdate()->get();
            KbhPeriode::where('jenis', 'seleksi')->update(['aktif' => false]);
            KbhPeriode::create($data + ['jenis' => 'seleksi', 'aktif' => true]);
        });

        return back()->with('success', 'Periode seleksi dibuka.');
    }

    public function close(Request $request, KbhPeriode $period): RedirectResponse
    {
        abort_unless($request->user()->role === 'ketua_umum' && $period->jenis === 'seleksi' && ! $period->batch, 403);
        DB::transaction(function () use ($period) {
            $locked = KbhPeriode::whereKey($period->id)->lockForUpdate()->firstOrFail();
            $locked->update(['aktif' => false]);
        });

        return back()->with('success', 'Periode seleksi ditutup.');
    }

    public function review(Request $request, KbhPendaftaranSeleksi $application): RedirectResponse
    {
        abort_unless($request->user()->role === 'sekretaris_umum' && ! $application->period?->batch, 403);
        $data = $request->validate(['status' => 'required|in:menunggu,wawancara,lolos,tidak_lolos', 'catatan' => 'nullable|string|max:3000', 'interview_at' => 'nullable|date', 'interview_location' => 'nullable|string|max:255', 'score' => 'nullable|integer|between:0,100']);
        if (! empty($data['interview_at'])) {
            $data['interview_at'] = Carbon::parse($data['interview_at'], 'Asia/Makassar')->setTimezone(config('app.timezone'));
        }
        $application->update($data);
        NotifikasiHimsi::kirim($application->user_id, 'Pembaruan seleksi pengurus', 'Status seleksi: '.$data['status'], route('kbh.seleksi.hasil', [], false), 'seleksi');

        return back()->with('success', 'Penilaian seleksi disimpan. Penetapan role tetap melalui Super Admin.');
    }

    public function file(Request $request, KbhPendaftaranSeleksi $application, string $type)
    {
        $this->authorize($request);
        abort_unless(in_array($type, ['khs', 'sertifikat'], true), 404);
        abort_if($application->period?->batch, 403);
        $path = $application->{$type.'_path'};
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
