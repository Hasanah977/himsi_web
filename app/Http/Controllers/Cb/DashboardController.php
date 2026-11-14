<?php

namespace App\Http\Controllers\Cb;

use App\Http\Controllers\Controller;
use App\Models\CbActivity;
use App\Models\CbParticipant;
use App\Models\HimsiRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $counts = HimsiRegistration::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $start = now('Asia/Makassar')->startOfDay()->subDays(13);
        $days = [];
        $registrations = HimsiRegistration::where('created_at', '>=', $start->copy()->timezone(config('app.timezone')))->get(['created_at']);
        $daily = $registrations->groupBy(fn ($r) => $r->created_at->copy()->timezone('Asia/Makassar')->format('Y-m-d'));
        for ($i = 0; $i < 14; $i++) {
            $date = $start->copy()->addDays($i);
            $days[] = ['date' => $date->format('d M'), 'total' => $daily->get($date->format('Y-m-d'), collect())->count()];
        }

        return view('cb.dashboard', [
            'pendingRegistrations' => HimsiRegistration::with('user')->where('status', 'pending')->oldest()->limit(5)->get(),
            'completeDocuments' => HimsiRegistration::whereNotNull('student_card_path')->where('student_card_path', '!=', '')->whereNotNull('active_letter_path')->where('active_letter_path', '!=', '')->whereNotNull('certificate_path')->where('certificate_path', '!=', '')->count(),
            'counts' => $counts, 'days' => $days, 'total' => $counts->sum(),
            'participantCount' => CbParticipant::count(),
            'activities' => CbActivity::orderByDesc('starts_at')->limit(5)->get(),
            'logs' => DB::table('cb_logs')->latest('id')->paginate(10),
        ]);
    }

    public static function record(string $action, ?string $subject = null, ?string $detail = null): void
    {
        DB::table('cb_logs')->insert(['actor_id' => auth()->id(), 'action' => $action, 'subject' => $subject, 'detail' => $detail, 'created_at' => now()]);
    }
}
