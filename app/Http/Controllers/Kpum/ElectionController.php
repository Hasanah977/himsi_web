<?php

namespace App\Http\Controllers\Kpum;

use App\Http\Controllers\Controller;
use App\Models\KpumCandidate;
use App\Models\KpumElection;
use App\Models\NotifikasiHimsi;
use App\Models\PublicContent;
use App\Models\User;
use App\Support\MemberProfile;
use App\Support\PublicImage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ElectionController extends Controller
{
    private function data(): array
    {
        $election = KpumElection::findOrFail(1);
        $tally = $election->tally();
        $total = (int) $tally->sum('ballots_count');
        $eligible = DB::table('kpum_electors')->where('election_id', $election->id)->count();
        $used = DB::table('kpum_electors')->where('election_id', $election->id)->where('has_voted', true)->count();
        $percent = $eligible ? round($used / $eligible * 100, 1) : 0;

        return compact('election', 'tally', 'total', 'eligible', 'used', 'percent');
    }

    public function dashboard(): View
    {
        return view('kpum.dashboard', $this->data() + [
            'news' => PublicContent::where('section', 'announcements')->where('slug', 'hasil-kpum-1')->where('is_published', true)->get(),
            'audits' => DB::table('kpum_audits')->leftJoin('users', 'users.id', '=', 'kpum_audits.actor_id')->where('election_id', 1)->select('kpum_audits.*', 'users.name as actor')->latest('kpum_audits.id')->limit(8)->get(),
            'pending' => KpumCandidate::where('election_id', 1)->where('status', 'pending')->count(),
        ]);
    }

    public function candidates(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $query = KpumCandidate::where('election_id', 1);
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->input('q').'%')->orWhere('nim', 'like', '%'.$request->input('q').'%');
            });
        }

        return view('kpum.candidates', $this->data() + ['candidates' => $query->orderBy('number')->get()]);
    }

    public function control(): View
    {
        return view('kpum.control', $this->data());
    }

    public function voters(Request $request): View
    {
        $request->validate(['q' => ['nullable', 'string', 'max:120']]);
        $users = User::query()->where('is_active', true)->where('role', 'kbh')->whereDoesntHave('registration', fn ($q) => $q->where('status', '!=', 'approved'));
        if ($request->filled('q')) {
            $users->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->input('q').'%')->orWhere('email', 'like', '%'.$request->input('q').'%');
            });
        }

        return view('kpum.voters', $this->data() + [
            'users' => $users->orderBy('name')->paginate(20)->withQueryString(),
            'electors' => DB::table('kpum_electors')->where('election_id', 1)->get()->keyBy('user_id'),
        ]);
    }

    public function voter(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['include' => ['required', 'boolean']]);
        DB::transaction(function () use ($data, $user) {
            $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
            $election->requireDraft();
            if ($data['include']) {
                $user->refresh();
                abort_unless($user->is_active && $user->role === 'kbh' && ! $user->awaitingRegistration(), 422);
                DB::table('kpum_electors')->insertOrIgnore(['election_id' => 1, 'user_id' => $user->id, 'has_voted' => false]);
            } else {
                DB::table('kpum_electors')->where('election_id', 1)->where('user_id', $user->id)->delete();
            }
            $election->record('Daftar pemilih diperbarui', $user->name.($data['include'] ? ' ditambahkan' : ' dikeluarkan'));
        });

        return back()->with('success', 'Daftar pemilih diperbarui.');
    }

    public function allVoters(): RedirectResponse
    {
        DB::transaction(function () {
            $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
            $election->requireDraft();
            $eligible = User::where('is_active', true)->where('role', 'kbh')->whereDoesntHave('registration', fn ($q) => $q->where('status', '!=', 'approved'));
            DB::table('kpum_electors')->where('election_id', 1)->whereNotIn('user_id', (clone $eligible)->select('id'))->delete();
            $eligible->select('id')->chunkById(500, function ($users) {
                DB::table('kpum_electors')->insertOrIgnore($users->map(fn ($user) => ['election_id' => 1, 'user_id' => $user->id, 'has_voted' => false])->all());
            });
            $election->record('Semua KBH aktif dipilih sebagai pemilih');
        });

        return back()->with('success', 'Semua KBH aktif dari seluruh halaman telah ditambahkan sebagai pemilih.');
    }

    public function settings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'], 'period' => ['required', 'string', 'max:40'],
            'starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:starts_at'],
        ]);
        DB::transaction(function () use ($data) {
            $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
            $election->requireDraft();
            foreach (['starts_at', 'ends_at'] as $key) {
                $data[$key] = Carbon::createFromFormat('Y-m-d\TH:i', $data[$key], 'Asia/Makassar')->startOfMinute()->setTimezone(config('app.timezone'));
            }
            $election->update($data);
            $election->record('Jadwal pemilihan disimpan');
        });

        return back()->with('success', 'Jadwal disimpan dalam zona waktu WITA.');
    }

    public function open(): RedirectResponse
    {
        DB::transaction(function () {
            $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
            $election->requireDraft();
            DB::table('kpum_electors')->where('election_id', 1)->whereNotIn('user_id', User::where('role', 'kbh')->where('is_active', true)->select('id'))->delete();

            if (! $election->starts_at || ! $election->ends_at || now()->gte($election->ends_at)) {
                throw ValidationException::withMessages(['open' => 'Simpan jadwal yang valid sebelum mengaktifkan voting.']);
            }
            if (KpumCandidate::where('election_id', 1)->where('status', 'approved')->count() < 2) {
                throw ValidationException::withMessages(['open' => 'Minimal dua kandidat harus lolos verifikasi.']);
            }
            if (! DB::table('kpum_electors')->join('users', 'users.id', '=', 'kpum_electors.user_id')->where('election_id', 1)->where('users.is_active', true)->exists()) {
                throw ValidationException::withMessages(['open' => 'Tambahkan setidaknya satu akun aktif ke daftar pemilih.']);
            }
            $election->update(['status' => 'open']);
            $election->record('Voting diaktifkan', 'Kandidat, daftar pemilih, dan jadwal dikunci.');
        });

        return back()->with('success', 'Voting diaktifkan. Pemilih dapat memilih sesuai jadwal.');
    }

    public function close(Request $request): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($data) {
            $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
            if ($election->status !== 'open') {
                throw ValidationException::withMessages(['close' => 'Voting tidak sedang aktif.']);
            }
            $election->update(['status' => 'closed', 'closed_at' => now()]);
            $election->record('Voting ditutup', $data['reason']);
        });

        return back()->with('success', 'Voting ditutup. Lanjutkan pemeriksaan hasil.');
    }

    public function results(): View
    {
        $data = $this->data();

        return view('kpum.results', $data + ['checks' => $data['election']->checks(), 'audits' => DB::table('kpum_audits')->where('election_id', 1)->latest('id')->paginate(15)]);
    }

    public function certify(Request $request): RedirectResponse
    {
        $data = $request->validate(['confirmed' => ['accepted'], 'note' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($data) {
            $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
            if (! $election->finished() || in_array($election->status, ['certified', 'published'], true)) {
                throw ValidationException::withMessages(['audit' => 'Hasil hanya bisa disahkan sekali setelah voting berakhir.']);
            }
            if (in_array(false, array_values($election->checks()), true) || ! DB::table('kpum_ballots')->where('election_id', 1)->exists()) {
                throw ValidationException::withMessages(['audit' => 'Pengesahan ditolak: data tidak konsisten atau belum ada suara.']);
            }
            $election->update(['status' => 'certified', 'certified_at' => now(), 'audit_note' => $data['note']]);
            $election->record('Hasil disahkan', $data['note']);
        });

        return back()->with('success', 'Hasil disahkan. Pengumuman sekarang dapat dipublikasikan.');
    }

    public function announcement(): View
    {
        return view('kpum.announcement', $this->data() + ['accounts' => User::where('is_active', true)->where('role', 'kbh')->orderBy('name')->get(['id', 'name', 'email']), 'announcement' => PublicContent::where('section', 'announcements')->where('slug', 'hasil-kpum-1')->first()]);
    }

    public function publish(Request $request): RedirectResponse
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:160'], 'body' => ['required', 'string', 'max:5000'], 'confirmed' => ['accepted'], 'winner_user_id' => ['nullable', 'integer', 'exists:users,id'], 'winner_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120']], [
            'winner_photo.image' => 'Foto pemenang harus berupa gambar.',
            'winner_photo.mimes' => 'Gunakan foto JPG, PNG, atau WebP.',
            'winner_photo.max' => 'Ukuran foto pemenang maksimal 5 MB.',
        ]);
        $photoPath = null;
        try {
            DB::transaction(function () use ($data, $request, &$photoPath) {
                $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
                if ($election->status !== 'certified') {
                    throw ValidationException::withMessages(['publish' => 'Hasil harus disahkan dan belum dipublikasikan.']);
                }
                $tally = $election->tally();
                $total = $tally->sum('ballots_count');
                $winners = $tally->where('ballots_count', $tally->max('ballots_count'));
                if ($winners->count() === 1 && $total > 0) {
                    $winner = $winners->first();
                    $userId = $winner->user_id ?? ($data['winner_user_id'] ?? null);
                    $account = $userId ? User::whereKey($userId)->lockForUpdate()->first() : User::whereHas('registration', fn ($q) => $q->where('nim', $winner->nim)->where('status', 'approved'))->lockForUpdate()->first();
                    if (! $account || ! $account->is_active || ! in_array($account->role, ['kbh', 'ketua_umum'], true)) {
                        throw ValidationException::withMessages(['winner_user_id' => 'Hubungkan akun KBH milik kandidat pemenang sebelum mempublikasikan hasil.']);
                    }
                    $winner->update(['user_id' => $account->id]);
                    $account->forceFill(['role' => 'ketua_umum', 'department' => null, 'welcome_signature' => null])->save();
                    MemberProfile::recordAppointment($account, 'ketua_umum', null, $election->period, 'election:'.$election->id.':'.$election->period);
                    NotifikasiHimsi::kirim($account->id, 'Selamat, Ketua Umum HIMSI!', 'Anda terpilih untuk periode '.$election->period.'. Mari mulai menyusun kepengurusan.', route('role.welcome', [], false), 'kpum');
                }
                $leaders = $tally->where('ballots_count', $tally->max('ballots_count'))->pluck('name')->join(', ');
                $summary = 'Hasil pemilihan periode '.$election->period.'. Total '.$total.' suara sah.';
                $resultLines = $tally->map(fn ($candidate) => 'Nomor '.$candidate->number.' — '.$candidate->name.': '.$candidate->ballots_count.' suara.')->all();
                $resultLines[] = 'Perolehan suara tertinggi: '.$leaders.'.';
                if ($tally->where('ballots_count', $tally->max('ballots_count'))->count() > 1) {
                    $resultLines[] = 'Hasil seri. Penetapan ketua menunggu keputusan sesuai aturan pemilihan.';
                }
                if ($request->hasFile('winner_photo')) {
                    $photoPath = PublicImage::store($request->file('winner_photo'), 'winner_photo');
                    if (! $photoPath) {
                        throw ValidationException::withMessages(['winner_photo' => 'Foto gagal disimpan. Silakan coba kembali.']);
                    }
                }
                PublicContent::create(['section' => 'announcements', 'slug' => 'hasil-kpum-1', 'payload' => [
                    'title' => $data['title'], 'category' => 'Penting', 'date' => now('Asia/Makassar')->format('Y-m-d'),
                    'summary' => $summary, 'body' => array_merge([$summary, $data['body']], $resultLines),
                    'image' => $photoPath ? 'storage/'.$photoPath : null,
                ], 'is_published' => true, 'position' => 0]);
                $election->update(['status' => 'published', 'published_at' => now()]);
                $election->record('Hasil dipublikasikan', $data['title']);
            });
        } catch (\Throwable $exception) {
            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }
            throw $exception;
        }

        return back()->with('success', 'Pengumuman hasil sudah tampil di website publik.');
    }

    public function stats(): JsonResponse
    {
        $data = $this->data();

        return response()->json([
            'total' => $data['total'], 'eligible' => $data['eligible'], 'used' => $data['used'],
            'remaining' => max(0, $data['eligible'] - $data['used']), 'percent' => $data['percent'],
            'label' => $data['election']->label(),
            'candidates' => $data['tally']->map(fn ($c) => ['id' => $c->id, 'votes' => $c->ballots_count, 'percent' => $data['total'] ? round($c->ballots_count / $data['total'] * 100, 1) : 0]),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function export(): StreamedResponse
    {
        $data = $this->data();

        return response()->streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nomor', 'Nama', 'Suara', 'Persentase', 'Status hasil'], ',', '"', '');
            foreach ($data['tally'] as $candidate) {
                $name = preg_match('/^[\s]*[=+@\-]/u', $candidate->name) ? "'".$candidate->name : $candidate->name;
                fputcsv($out, [$candidate->number, $name, $candidate->ballots_count, $data['total'] ? round($candidate->ballots_count / $data['total'] * 100, 1) : 0, $data['election']->label()], ',', '"', '');
            }
            fclose($out);
        }, 'rekap-kpum.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }
}
