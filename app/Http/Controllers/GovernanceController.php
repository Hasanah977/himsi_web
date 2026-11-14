<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Governance;
use App\Support\SecurityAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GovernanceController extends Controller
{
    public function index(): View
    {
        return view('admin.governance', [
            'periods' => DB::table('governance_periods')->latest('id')->get(),
            'active' => Governance::active(),
            'officerCount' => User::whereIn('role', User::OFFICER_ROLES)->count(),
        ]);
    }

    public function end(Request $request, int $period): RedirectResponse
    {
        $request->validate(['confirmed' => ['accepted']]);
        Governance::end($period);

        return redirect()->route('admin.governance.index')->with('success', 'Kepengurusan diakhiri. Seluruh akses jabatan dicabut, akun KBH tetap tersedia, dan data periode menjadi arsip baca.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80'], 'starts_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:today', 'after:'.now()->subYear()->toDateString()]]);
        DB::transaction(function () use ($data): void {
            User::where('role', 'super_admin')->orderBy('id')->lockForUpdate()->get();
            abort_if(DB::table('governance_periods')->whereNull('closed_at')->exists(), 422, 'Periode sebelumnya belum berakhir.');
            $start = Carbon::parse($data['starts_at'])->startOfDay();
            $cutoff = Governance::cutoff();
            if ($cutoff && $start->isSameDay(Carbon::parse($cutoff))) {
                $start = Carbon::parse($cutoff);
            }
            abort_if($cutoff && $start->lt(Carbon::parse($cutoff)), 422, 'Periode tidak boleh tumpang tindih.');
            $id = DB::table('governance_periods')->insertGetId(['name' => $data['name'], 'starts_at' => $start, 'ends_at' => $start->copy()->addYearNoOverflow(), 'created_at' => now(), 'updated_at' => now()]);
            SecurityAudit::record('governance_opened', 'Periode '.$data['name'].' dibuka.', context: ['period_id' => $id]);
        });

        return back()->with('success', 'Periode aktif disimpan. Data yang sudah ada tetap tersedia.');
    }

    public function archive(Request $request): View
    {
        $type = $request->query('type', 'program');
        $query = Governance::query($type, $request->user());
        $periods = DB::table('governance_periods')->whereNotNull('closed_at')->latest('id')->get();
        $period = $request->filled('period') ? $periods->firstWhere('id', (int) $request->input('period')) : $periods->first();
        abort_if($request->filled('period') && ! $period, 404);
        if ($period) {
            $query->where('created_at', '<', $period->ends_at);
            $previous = DB::table('governance_periods')->where('id', '<', $period->id)->max('ends_at');
            if ($previous) {
                $query->where('created_at', '>=', $previous);
            }
        } else {
            $query->whereRaw('1 = 0');
        }
        $records = $query->latest('id')->paginate(15)->withQueryString();
        $followups = DB::table('governance_followups')->where('source_type', $type)->whereIn('source_id', $records->modelKeys())->orderBy('id')->get()->groupBy('source_id');

        return view('administration.period-archive', compact('type', 'periods', 'period', 'records', 'followups'));
    }

    public function file(Request $request, string $type, int $record, string $field): StreamedResponse
    {
        $source = Governance::query($type, $request->user())->findOrFail($record);
        abort_unless(Governance::cutoff() && $source->created_at->lt(Governance::cutoff()), 404);
        abort_unless(preg_match('/^(file.*|.*_path|lampiran)$/', $field), 404);
        $path = $source->getRawOriginal($field);
        abort_unless(is_string($path) && $path !== '' && ! str_contains($path, '..') && ! str_starts_with($path, '/'), 404);
        $path = preg_replace('#^storage/#', '', $path);
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->download($path);
            }
        }
        abort(404, 'Berkas tidak tersedia.');
    }

    public function followup(Request $request, string $type, int $record): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'max:5000']]);
        DB::transaction(function () use ($request, $type, $record, $data): void {
            $period = DB::table('governance_periods')->whereNull('closed_at')->where('ends_at', '>', now())->lockForUpdate()->first();
            abort_unless($period, 423);
            $source = Governance::query($type, $request->user())->findOrFail($record);
            abort_unless(Governance::cutoff() && $source->created_at->lt(Governance::cutoff()), 422, 'Tindak lanjut ini khusus untuk arsip periode sebelumnya.');
            DB::table('governance_followups')->insert(['period_id' => $period->id, 'source_type' => $type, 'source_id' => $record, 'author_id' => $request->user()->id, 'note' => $data['note'], 'created_at' => now(), 'updated_at' => now()]);
        });

        return back()->with('success', 'Tindak lanjut disimpan sebagai catatan baru. Arsip asal tetap utuh.');
    }
}
