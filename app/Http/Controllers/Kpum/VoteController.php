<?php

namespace App\Http\Controllers\Kpum;

use App\Http\Controllers\Controller;
use App\Models\KpumCandidate;
use App\Models\KpumElection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VoteController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === 'kbh', 403, 'Voting hanya untuk role KBH.');
        $election = KpumElection::findOrFail(1);
        $elector = DB::table('kpum_electors')->where('election_id', 1)->where('user_id', $request->user()->id)->first();
        abort_unless($elector, 403, 'Akun Anda belum masuk daftar pemilih KPUM.');

        return view('kpum.vote', ['election' => $election, 'elector' => $elector, 'candidates' => KpumCandidate::where('election_id', 1)->where('status', 'approved')->orderBy('number')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'kbh', 403, 'Voting hanya untuk role KBH.');
        $data = $request->validate(['candidate_id' => ['required', 'integer'], 'confirmed' => ['accepted']]);
        DB::transaction(function () use ($request, $data) {
            $election = KpumElection::whereKey(1)->lockForUpdate()->firstOrFail();
            if (! $election->acceptingVotes()) {
                throw ValidationException::withMessages(['vote' => 'Voting belum dibuka atau sudah berakhir.']);
            }
            $elector = DB::table('kpum_electors')->where('election_id', 1)->where('user_id', $request->user()->id)->lockForUpdate()->first();
            abort_unless($elector, 403);
            if ($elector->has_voted) {
                throw ValidationException::withMessages(['vote' => 'Anda sudah memberikan suara. Setiap pemilih hanya dapat memilih satu kali.']);
            }
            $candidate = KpumCandidate::where('election_id', 1)->where('status', 'approved')->find($data['candidate_id']);
            if (! $candidate) {
                throw ValidationException::withMessages(['candidate_id' => 'Kandidat tidak tersedia.']);
            }
            DB::table('kpum_ballots')->insert(['id' => (string) Str::uuid(), 'election_id' => 1, 'candidate_id' => $candidate->id, 'cast_at' => now()]);
            DB::table('kpum_electors')->where('id', $elector->id)->update(['has_voted' => true]);
        }, 3);

        return redirect()->route($request->routeIs('kbh.voting.vote') ? 'kbh.voting.hasil' : 'kpum.vote')->with('success', 'Terima kasih! Suara Anda berhasil disimpan.');
    }
}
