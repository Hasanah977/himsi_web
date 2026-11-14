<?php

namespace App\Http\Controllers\kbh;

use App\Http\Controllers\Administration\RecruitmentController;
use App\Http\Controllers\Controller;
use App\Models\KbhPendaftaranSeleksi;
use App\Models\KbhPeriode;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SeleksiController extends Controller
{
    private function pendaftaran(?KbhPeriode $periode, Request $request): ?KbhPendaftaranSeleksi
    {
        return $periode ? KbhPendaftaranSeleksi::where('periode_id', $periode->id)->where('user_id', $request->user()->id)->first() : null;
    }

    public function index(Request $request)
    {
        if (KbhPeriode::whereNotNull('batch')->exists()) {
            return app(RecruitmentController::class)->result($request);
        }

        $periode = KbhPeriode::terbaru('seleksi');
        $pendaftaran = $this->pendaftaran($periode, $request);

        return view('kbh.seleksi.index', compact('periode', 'pendaftaran'));
    }

    public function upload(Request $request)
    {
        abort_unless($request->user()->assignedRole() === 'kbh' || $request->user()->isSuperAdmin(), 403);
        if (KbhPeriode::whereNotNull('batch')->exists()) {
            return app(RecruitmentController::class)->applyForm($request);
        }

        $periode = KbhPeriode::terbaru('seleksi');
        $pendaftaran = $this->pendaftaran($periode, $request);
        if ($pendaftaran) {
            return redirect()->route('kbh.seleksi.hasil')->with('info', 'Kamu sudah mendaftar pada periode ini.');
        }
        if (! $periode || ! $periode->sedangDibuka()) {
            return redirect()->route('kbh.seleksi.index')->with('error', 'Pendaftaran seleksi belum dibuka atau sudah ditutup.');
        }

        return view('kbh.seleksi.upload', compact('periode'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->assignedRole() === 'kbh', 403);
        if (KbhPeriode::whereNotNull('batch')->exists()) {
            return app(RecruitmentController::class)->apply($request);
        }

        $data = $request->validate([
            'khs' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'sertifikat' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);
        $periode = KbhPeriode::terbaru('seleksi');
        if (! $periode || ! $periode->sedangDibuka()) {
            throw ValidationException::withMessages(['khs' => 'Pendaftaran seleksi belum dibuka atau sudah ditutup.']);
        }
        if ($this->pendaftaran($periode, $request)) {
            return redirect()->route('kbh.seleksi.hasil')->with('info', 'Kamu sudah mendaftar pada periode ini.');
        }

        $khsPath = $data['khs']->store('kbh/seleksi/khs', 'local');
        $sertifikatPath = null;
        try {
            if (! empty($data['sertifikat'])) {
                $sertifikatPath = $data['sertifikat']->store('kbh/seleksi/sertifikat', 'local');
            }
            DB::transaction(function () use ($request, $periode, $khsPath, $sertifikatPath) {
                $locked = KbhPeriode::whereKey($periode->id)->lockForUpdate()->firstOrFail();
                $terbaruId = KbhPeriode::where('jenis', 'seleksi')->orderByDesc('mulai_pada')->orderByDesc('id')->value('id');
                if ($terbaruId != $locked->id || ! $locked->sedangDibuka()) {
                    throw ValidationException::withMessages(['khs' => 'Periode seleksi sudah berubah atau ditutup.']);
                }
                if (KbhPendaftaranSeleksi::where('periode_id', $locked->id)->where('user_id', $request->user()->id)->exists()) {
                    throw ValidationException::withMessages(['khs' => 'Kamu sudah pernah mendaftar pada periode ini.']);
                }
                KbhPendaftaranSeleksi::create([
                    'periode_id' => $locked->id, 'user_id' => $request->user()->id,
                    'khs_path' => $khsPath, 'sertifikat_path' => $sertifikatPath,
                    'status' => 'menunggu',
                ]);
            }, 3);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete(array_filter([$khsPath, $sertifikatPath]));
            if ($e instanceof QueryException && in_array($e->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages(['khs' => 'Kamu sudah mendaftar pada periode ini.']);
            }
            throw $e;
        }

        return redirect()->route('kbh.seleksi.hasil')->with('success', 'Berkas berhasil disimpan. Silakan menunggu hasil seleksi.');
    }

    public function hasil(Request $request)
    {
        if (KbhPeriode::whereNotNull('batch')->exists()) {
            return app(RecruitmentController::class)->result($request);
        }

        $periode = KbhPeriode::terbaru('seleksi');
        $pendaftaran = $this->pendaftaran($periode, $request);

        return view('kbh.seleksi.hasil', compact('periode', 'pendaftaran'));
    }
}
