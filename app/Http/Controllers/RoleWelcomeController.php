<?php

namespace App\Http\Controllers;

use App\Models\KpumElection;
use App\Models\User;
use App\Support\RecruitmentControl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleWelcomeController extends Controller
{
    public static function signature(User $user): string
    {
        return $user->role.':'.($user->department ?? '');
    }

    public static function pending(User $user): bool
    {
        return in_array($user->role, ['ketua_umum', 'sekretaris_umum', 'bendahara_umum', 'koordinator_departemen', 'anggota_departemen', 'kbh'], true)
            && $user->welcome_signature !== self::signature($user);
    }

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! self::pending($user)) {
            return redirect()->route('dashboard');
        }
        $result = null;
        $election = KpumElection::where('status', 'published')->latest('id')->first();
        if ($user->role === 'ketua_umum' && $election) {
            $tally = $election->tally();
            $candidate = $tally->firstWhere('user_id', $user->id);
            if ($candidate && $tally->where('ballots_count', $tally->max('ballots_count'))->count() === 1 && $candidate->ballots_count === $tally->max('ballots_count')) {
                $votes = (int) $candidate->ballots_count;
                $total = (int) $tally->sum('ballots_count');
                $result = ['votes' => $votes, 'percent' => $total ? round($votes / $total * 100, 1) : 0, 'electors' => DB::table('kpum_electors')->where('election_id', $election->id)->count(), 'period' => $election->period];
            }
        }

        return view('role-welcome', compact('user', 'result'));
    }

    public function acknowledge(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->forceFill(['welcome_signature' => self::signature($user)])->save();

        return redirect()->route($user->role === 'ketua_umum' && RecruitmentControl::enabled() ? 'recruitment.index' : 'dashboard');
    }
}
