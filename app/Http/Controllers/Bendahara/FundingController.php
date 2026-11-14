<?php

namespace App\Http\Controllers\Bendahara;

use App\Http\Controllers\Controller;
use App\Models\FinanceEntry;
use App\Models\NotifikasiHimsi;
use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\User;
use App\Support\DepartmentWorkflow as Flow;
use App\Support\FinanceLedger as Ledger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FundingController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'incoming');
        abort_unless(in_array($tab, ['incoming', 'waiting', 'ready', 'history']), 404);
        $statuses = match ($tab) {
            'incoming' => ['pending_treasurer'], 'waiting' => ['pending_chair', 'approved'],'ready' => ['ready_funding'],default => ['disbursed', 'revision', 'rejected']
        };
        $items = OpsSubmission::with('program')->where('type', 'budget')->whereIn('status', $statuses)->latest()->paginate(15)->withQueryString();

        $counts = [];
        foreach (['incoming' => ['pending_treasurer'], 'waiting' => ['pending_chair', 'approved'], 'ready' => ['ready_funding'], 'history' => ['disbursed', 'revision', 'rejected']] as $key => $states) {
            $counts[$key] = OpsSubmission::where('type', 'budget')->whereIn('status', $states)->count();
        }

        return view('bendahara.finance.funding', ['items' => $items, 'tab' => $tab, 'counts' => $counts, 'balance' => Ledger::balance()]);
    }

    public function show(OpsSubmission $submission): View
    {
        abort_unless($submission->type === 'budget' && $submission->status !== 'draft', 404);
        $submission->load('program');
        $entries = Ledger::query()->where('submission_id', $submission->id)->orderBy('id')->get();
        $paid = $entries->where('origin', 'disbursement')->sum('amount');
        $returned = $entries->where('origin', 'return')->sum('amount');

        return view('bendahara.finance.funding-show', compact('submission', 'entries', 'paid', 'returned'));
    }

    public function pay(Request $request, OpsSubmission $submission): RedirectResponse
    {
        return $this->save($request, $submission, false);
    }

    public function refund(Request $request, OpsSubmission $submission): RedirectResponse
    {
        return $this->save($request, $submission, true);
    }

    public function departmentRefund(Request $request, string $department, OpsSubmission $submission): RedirectResponse
    {
        abort_unless($request->user()->role === 'koordinator_departemen' && $request->user()->department === $department, 403);
        abort_unless($submission->program->department === $department, 404);

        return $this->save($request, $submission, true);
    }

    private function save(Request $request, OpsSubmission $submission, bool $refund): RedirectResponse
    {
        abort_unless($submission->type === 'budget', 404);
        $data = $request->validate(['version' => ['required', 'integer'], 'reference' => [$refund ? 'required' : 'nullable', 'uuid'], 'amount' => [$refund ? 'required' : 'nullable', 'integer', 'between:1,1000000000000'], 'occurred_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.config('finance.start'), 'before_or_equal:'.min(now()->toDateString(), config('finance.end'))], 'note' => ['nullable', 'string', 'max:2000'], 'receipt' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120']]);
        $path = null;
        try {
            DB::transaction(function () use ($request, $submission, $refund, $data, &$path) {
                Ledger::lock();
                $program = OpsProgram::whereKey($submission->program_id)->lockForUpdate()->firstOrFail();
                $s = OpsSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
                Flow::version($s->version, (int) $data['version']);
                if ($refund) {
                    Ledger::ensure($s->status === 'disbursed', 'Dana belum dicairkan.');
                    $payment = Ledger::query()->where('submission_id', $s->id)->where('origin', 'disbursement')->firstOrFail();
                    $returned = Ledger::query()->where('submission_id', $s->id)->where('origin', 'return')->sum('amount');
                    Ledger::ensure($data['amount'] <= $payment->amount - $returned, 'Pengembalian melebihi sisa dana yang dicairkan.');
                    Ledger::ensure($data['occurred_on'] >= $payment->occurred_on->toDateString(), 'Tanggal pengembalian mendahului pencairan.');
                    $ref = 'return:'.$s->id.':'.$data['reference'];
                    $amount = $data['amount'];
                } else {
                    Ledger::ensure($s->status === 'ready_funding' && $s->finance_approved_by && $s->finance_approved_at, 'Dana harus disetujui Ketua Umum sebelum dicairkan.');
                    Ledger::ensure($s->total > 0, 'Nominal RAB harus lebih dari nol.');
                    Ledger::ensure($data['occurred_on'] >= substr($s->finance_approved_at, 0, 10), 'Tanggal pencairan mendahului persetujuan Ketua.');
                    $ref = 'pencairan:'.$s->id;
                    $amount = $s->total;
                }
                Ledger::ensure(! FinanceEntry::where('reference', $ref)->exists(), 'Transaksi ini sudah dicatat.');
                $path ??= $request->file('receipt')->store('finance/receipts', 'local');
                Ledger::ensure((bool) $path, 'Bukti gagal disimpan.');
                Ledger::record(['reference' => $ref, 'kind' => $refund ? 'income' : 'expense', 'origin' => $refund ? 'return' : 'disbursement', 'occurred_on' => $data['occurred_on'], 'amount' => $amount, 'category' => $refund ? 'Pengembalian Dana' : 'Pencairan Kegiatan', 'description' => ($refund ? 'Pengembalian ' : 'Pencairan ').$program->title.(! empty($data['note']) ? ' — '.$data['note'] : ''), 'method' => 'Transfer', 'department' => $program->department, 'program_id' => $program->id, 'submission_id' => $s->id, 'receipt_path' => $path, 'receipt_name' => $request->file('receipt')->getClientOriginalName()]);
                $s->update(['status' => 'disbursed', 'version' => $s->version + 1]);
                Flow::history($program, $refund ? 'Pengembalian dana' : 'Dana dicairkan', 'Rp'.$amount);
                NotifikasiHimsi::kirim($program->created_by, $refund ? 'Pengembalian Dana Dicatat' : 'Dana Kegiatan Dicairkan', $program->title.' — Rp'.number_format($amount, 0, ',', '.'), route('department.program.show', [$program->department, $program->id], false), 'keuangan');
                if ($refund && $request->user()->role === 'koordinator_departemen') {
                    User::where('role', 'bendahara_umum')->where('is_active', true)->pluck('id')
                        ->each(fn ($id) => NotifikasiHimsi::kirim($id, 'Pengembalian Dana Departemen', $program->title.' — Rp'.number_format($amount, 0, ',', '.').' telah dicatat oleh koordinator.', route('finance.funding.show', $s->id, false), 'keuangan'));
                }
            }, 3);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }throw $e;
        }

        return back()->with('success', $refund ? 'Pengembalian dana tercatat sebagai pemasukan.' : 'Pencairan tercatat sebagai pengeluaran.');
    }
}
