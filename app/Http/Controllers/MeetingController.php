<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\NotifikasiHimsi;
use App\Models\User;
use App\Support\DepartmentWorkflow as Flow;
use App\Support\Governance;
use App\Support\MeetingAttendanceCode;
use App\Support\MeetingPdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MeetingController extends Controller
{
    private function authorizeRole(Request $request, bool $manage = false): void
    {
        abort_unless($request->user()->is_active && in_array($request->user()->role, $manage ? ['sekretaris_umum'] : User::OFFICER_ROLES, true), 403);
    }

    private function visible(Request $request): Builder
    {
        $this->authorizeRole($request);

        return Meeting::query()->when($request->user()->role !== 'sekretaris_umum', fn (Builder $query) => $query->whereHas('attendances', fn (Builder $attendance) => $attendance->where('user_id', $request->user()->id)));
    }

    private function filtered(Request $request): Builder
    {
        $request->validate(['category' => ['nullable', Rule::in(array_keys(Meeting::CATEGORIES))], 'from' => ['nullable', 'date_format:Y-m-d'], 'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')]]);

        return $this->visible($request)
            ->when($request->filled('category'), fn (Builder $query) => $query->where('category', $request->input('category')))
            ->when($request->filled('from'), fn (Builder $query) => $query->where('scheduled_at', '>=', Carbon::parse($request->input('from'), 'Asia/Makassar')->startOfDay()->utc()))
            ->when($request->filled('to'), fn (Builder $query) => $query->where('scheduled_at', '<=', Carbon::parse($request->input('to'), 'Asia/Makassar')->endOfDay()->utc()));
    }

    public function index(Request $request): View
    {
        $meetings = $this->filtered($request)->withCount(['attendances', 'attendances as present_count' => fn (Builder $query) => $query->where('status', 'present')])->orderByDesc('scheduled_at')->paginate(12)->withQueryString();

        return view('department.meetings-index', ['meetings' => $meetings, 'canManage' => $request->user()->role === 'sekretaris_umum']);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeRole($request, true);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(array_keys(Meeting::CATEGORIES))],
            'scheduled_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'location' => ['required', 'string', 'max:200'],
        ]);
        $data['scheduled_at'] = Carbon::createFromFormat('!Y-m-d\TH:i', $data['scheduled_at'], 'Asia/Makassar')->utc();
        Flow::require($data['scheduled_at']->greaterThanOrEqualTo(now()), 'Jadwal rapat tidak boleh berada di masa lalu.');
        $meeting = DB::transaction(function () use ($request, $data): Meeting {
            $meeting = Meeting::create($data + ['created_by' => $request->user()->id, 'status' => 'scheduled']);
            User::where('is_active', true)->whereIn('role', Meeting::participantRoles($data['category']))->chunkById(100, function ($users) use ($meeting): void {
                foreach ($users as $user) {
                    $meeting->attendances()->create([
                        'user_id' => $user->id,
                        'participant_name' => $user->name,
                        'participant_role' => $user->assignedRole(),
                        'participant_department' => $user->department,
                    ]);
                    NotifikasiHimsi::kirim($user->id, 'Undangan '.Meeting::CATEGORIES[$meeting->category], $meeting->title.' · '.$meeting->scheduled_at->copy()->timezone('Asia/Makassar')->format('d/m/Y H:i').' · '.$meeting->location, route('meetings.show', $meeting, false), 'rapat');
                }
            });

            return $meeting;
        });

        return redirect()->route('meetings.show', $meeting)->with('success', 'Undangan rapat berhasil dikirim kepada seluruh peserta sesuai kategori.');
    }

    public function show(Request $request, int $meeting): View
    {
        $meeting = $this->visible($request)->with('attendances')->findOrFail($meeting);

        return view('department.meeting-show', [
            'meeting' => $meeting,
            'canManage' => $request->user()->role === 'sekretaris_umum',
            'myAttendance' => $meeting->attendances->firstWhere('user_id', $request->user()->id),
        ]);
    }

    public function open(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeRole($request, true);
        DB::transaction(function () use ($meeting): void {
            $meeting = Meeting::whereKey($meeting->id)->lockForUpdate()->firstOrFail();
            Flow::require($meeting->status === 'scheduled', 'Absensi hanya dapat dibuka satu kali untuk rapat terjadwal.');
            $meeting->update(['status' => 'open', 'attendance_opened_at' => now()]);
        });

        return back()->with('success', 'Absensi dibuka. Kode dan QR diperbarui setiap dua menit.');
    }

    public function live(Request $request, Meeting $meeting, MeetingAttendanceCode $codes): JsonResponse
    {
        $this->authorizeRole($request, true);
        $meeting->load('attendances');

        return response()->json([
            'status' => $meeting->status,
            'token' => $meeting->status === 'open' ? $codes->current($meeting) : null,
            'attendances' => $meeting->attendances->map(fn (MeetingAttendance $attendance): array => [
                'id' => $attendance->id, 'status' => $attendance->status, 'label' => MeetingAttendance::STATUSES[$attendance->status],
                'attended_at' => $attendance->attended_at?->copy()->timezone('Asia/Makassar')->format('H:i:s'), 'version' => $attendance->version,
            ]),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function attend(Request $request, int $meeting, MeetingAttendanceCode $codes): RedirectResponse
    {
        $meeting = $this->visible($request)->findOrFail($meeting);
        $data = $request->validate(['code' => ['required', 'string', 'regex:/^[A-Fa-f0-9]{10}$/'], 'source' => ['required', Rule::in(['code', 'qr'])]]);
        DB::transaction(function () use ($request, $meeting, $codes, $data): void {
            $meeting = Meeting::whereKey($meeting->id)->lockForUpdate()->firstOrFail();
            Governance::assertWritable($meeting);
            Flow::require($meeting->status === 'open', 'Absensi belum dibuka atau sudah ditutup.');
            Flow::require(hash_equals($codes->current($meeting)['code'], strtoupper($data['code'])), 'Kode tidak berlaku atau sudah kedaluwarsa. Gunakan kode terbaru.');
            $attendance = $meeting->attendances()->where('user_id', $request->user()->id)->lockForUpdate()->firstOrFail();
            if ($attendance->status === 'present') {
                return;
            }
            Flow::require($attendance->source !== 'manual', 'Status Anda sudah ditetapkan Sekertaris. Hubungi Sekertaris untuk koreksi.');
            $attendance->update(['status' => 'present', 'source' => $data['source'], 'attended_at' => now(), 'recorded_by' => $request->user()->id, 'version' => $attendance->version + 1]);
        });

        return redirect()->route('meetings.show', $meeting)->with('success', 'Kehadiran Anda berhasil tercatat.');
    }

    public function manual(Request $request, Meeting $meeting, int $attendance): RedirectResponse
    {
        $this->authorizeRole($request, true);
        $data = $request->validate(['status' => ['required', Rule::in(['present', 'excused', 'absent'])], 'version' => ['required', 'integer', 'min:0']]);
        DB::transaction(function () use ($meeting, $attendance, $data, $request): void {
            $meeting = Meeting::whereKey($meeting->id)->lockForUpdate()->firstOrFail();
            Governance::assertWritable($meeting);
            Flow::require($meeting->status === 'open', 'Pencatatan manual hanya tersedia selama absensi dibuka.');
            $record = $meeting->attendances()->whereKey($attendance)->lockForUpdate()->firstOrFail();
            Flow::version($record->version, (int) $data['version']);
            $record->update(['status' => $data['status'], 'source' => 'manual', 'recorded_by' => $request->user()->id, 'attended_at' => $data['status'] === 'present' ? ($record->attended_at ?? now()) : null, 'version' => $record->version + 1]);
        });

        return back()->with('success', 'Absensi peserta berhasil diperbarui.');
    }

    public function close(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeRole($request, true);
        DB::transaction(function () use ($meeting): void {
            $meeting = Meeting::whereKey($meeting->id)->lockForUpdate()->firstOrFail();
            Flow::require($meeting->status === 'open', 'Absensi rapat tidak sedang dibuka.');
            $meeting->update(['status' => 'closed', 'attendance_closed_at' => now()]);
            $meeting->attendances()->where('status', 'pending')->update(['status' => 'absent', 'source' => 'closing', 'version' => DB::raw('version + 1')]);
        });

        return back()->with('success', 'Absensi ditutup. Peserta yang belum mengisi ditandai tidak hadir. Silakan isi notula rapat.');
    }

    public function minutes(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorizeRole($request, true);
        $data = $request->validate(['minutes' => ['required', 'string', 'max:30000']]);
        DB::transaction(function () use ($meeting, $data): void {
            $meeting = Meeting::whereKey($meeting->id)->lockForUpdate()->firstOrFail();
            Flow::require($meeting->status === 'closed', 'Notula dapat disimpan setelah absensi ditutup dan belum difinalkan.');
            $meeting->update(['minutes' => $data['minutes'], 'status' => 'completed']);
        });

        return back()->with('success', 'Notula tersimpan. Rapat selesai dan tersedia dalam riwayat peserta.');
    }

    public function export(Request $request): Response
    {
        $this->authorizeRole($request, true);
        $meetings = $this->filtered($request)->with('attendances')->orderBy('category')->orderBy('scheduled_at')->lazy(100);
        $pdf = new MeetingPdf;
        foreach ($meetings as $meeting) {
            $pdf->meeting($meeting);
        }
        if ($pdf->PageNo() === 0) {
            $pdf->AddPage();
            $pdf->MultiCell(0, 7, 'Belum ada rapat yang sesuai dengan filter.');
        }

        return response($pdf->Output('S'), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="rekap-absensi-rapat.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
