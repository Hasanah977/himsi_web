<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Sekretaris\DashboardController;
use App\Models\KbhAspirasi;
use App\Models\KbhIuranPembayaran;
use App\Models\KpumCandidate;
use App\Models\KpumElection;
use App\Models\NotifikasiHimsi;
use App\Models\OpsProgram;
use App\Models\PengawasanLaporan;
use App\Models\PublicContent;
use App\Models\SuratKeluar;
use App\Support\LeadershipDashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function dashboard(Request $request): View
    {
        $role = $request->attributes->get('administration_preview_role', $request->user()->role);

        if ($role === 'ketua_umum') {
            return view('ketua-umum.dashboard', LeadershipDashboard::chair());
        }

        if ($role === 'koordinator_pengawas') {
            return view('koordinator-pengawas.dashboard', LeadershipDashboard::supervisor());
        }

        if ($role === 'sekretaris_umum') {
            return app(DashboardController::class)->index($request);
        }

        if ($role === 'kbh') {
            $payments = KbhIuranPembayaran::where('user_id', $request->user()->id)->where('tahun', now()->year)->get();
            $currentPayment = $payments->firstWhere('bulan', now()->month);
            $paid = $payments->where('status', 'lunas')->sum('nominal');
            $aspirationCount = KbhAspirasi::where('user_id', $request->user()->id)->count();
            $election = KpumElection::find(1);
            $announcements = PublicContent::where('section', 'announcements')->where('is_published', true)->whereNull('department')->latest()->limit(5)->get()->map(fn ($item) => array_merge($item->payload, ['slug' => $item->slug]));

            return view('kbh.dashboard', compact('currentPayment', 'paid', 'aspirationCount', 'election', 'announcements'));
        }
        $stats = $role === 'kbh' ? ['Pengumuman' => PublicContent::where('section', 'announcements')->where('is_published', true)->count(), 'Program Publik' => PublicContent::where('section', 'programs')->where('is_published', true)->count(), 'Notifikasi Baru' => NotifikasiHimsi::where('user_id', $request->user()->id)->whereNull('dibaca_pada')->count()] : ['Program Departemen' => OpsProgram::count(), 'Surat Menunggu' => SuratKeluar::where('status', 'menunggu_persetujuan')->count(), 'Surat Disetujui' => SuratKeluar::where('status', 'disetujui')->count(), 'Laporan Pengawasan' => PengawasanLaporan::count()];

        $departmentStats = OpsProgram::selectRaw('department, COUNT(*) as total, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed', ['completed'])->groupBy('department')->get()->keyBy('department');

        $chartTitle = 'Statistik Persuratan';
        $chartDescription = 'Jumlah surat keluar berdasarkan tahap persetujuan.';
        $letterCounts = SuratKeluar::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $chartStats = ['Draft' => $letterCounts['draft'] ?? 0, 'Menunggu Persetujuan' => $letterCounts['menunggu_persetujuan'] ?? 0, 'Disetujui' => $letterCounts['disetujui'] ?? 0, 'Ditolak' => $letterCounts['ditolak'] ?? 0];
        if ($role === 'koordinator_pengawas') {
            $chartTitle = 'Statistik Pengawasan';
            $chartDescription = 'Jumlah laporan pengawasan berdasarkan status tindak lanjut.';
            $reportCounts = PengawasanLaporan::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
            $chartStats = ['Masuk' => $reportCounts['masuk'] ?? 0, 'Dalam Proses' => $reportCounts['proses'] ?? 0, 'Perlu Perbaikan' => $reportCounts['perlu_perbaikan'] ?? 0, 'Selesai' => $reportCounts['selesai'] ?? 0];
        }

        return view('administration.dashboard', compact('stats', 'role', 'departmentStats', 'chartStats', 'chartTitle', 'chartDescription'));
    }

    public function announcements(): View
    {
        return view('administration.public-list', ['title' => 'Pengumuman', 'items' => PublicContent::items('announcements'), 'detail' => 'kbh.pengumuman.show']);
    }

    public function announcement(string $id): View
    {
        return $this->publicDetail('announcements', $id);
    }

    public function publicPrograms(): View
    {
        return view('administration.public-list', ['title' => 'Program Kerja', 'items' => PublicContent::items('programs'), 'detail' => 'kbh.program-kerja.show', 'activities' => OpsProgram::whereIn('status', ['approved', 'running', 'completed'])->latest()->get()]);
    }

    public function publicProgram(string $id): View
    {
        return $this->publicDetail('programs', $id);
    }

    private function publicDetail(string $section, string $slug): View
    {
        $item = collect(PublicContent::items($section))->firstWhere('slug', $slug);
        abort_unless($item, 404);

        return view('administration.public-detail', compact('item'));
    }

    public function programs(Request $request): View
    {
        abort_unless(in_array($request->user()->role, ['ketua_umum', 'koordinator_pengawas', 'super_admin'], true), 403);
        $programs = OpsProgram::with('pic')->withCount(['tasks', 'tasks as done_count' => fn ($q) => $q->where('status', 'done')])->latest()->paginate(12);

        return view('administration.programs', compact('programs'));
    }

    public function program(Request $request, OpsProgram $program): View
    {
        abort_unless(in_array($request->user()->role, ['ketua_umum', 'koordinator_pengawas', 'super_admin'], true), 403);
        $program->load(['pic', 'tasks', 'submissions']);

        return view('administration.program', compact('program'));
    }

    public function activity(OpsProgram $program): View
    {
        abort_unless(in_array($program->status, ['approved', 'running', 'completed'], true), 404);

        return view('administration.public-detail', ['item' => ['title' => $program->title, 'summary' => $program->description, 'date' => $program->starts_on?->format('d M Y')]]);
    }

    public function notifications(Request $request): View
    {
        $request->validate(['filter' => ['nullable', 'in:unread,read']]);
        $query = NotifikasiHimsi::where('user_id', $request->user()->id);
        $totalCount = (clone $query)->count();
        $unreadCount = (clone $query)->whereNull('dibaca_pada')->count();
        $items = $query->when($request->input('filter') === 'unread', fn ($query) => $query->whereNull('dibaca_pada'))
            ->when($request->input('filter') === 'read', fn ($query) => $query->whereNotNull('dibaca_pada'))
            ->latest()->paginate(20)->withQueryString();

        return view('administration.notifications', compact('items', 'totalCount', 'unreadCount'));
    }

    public function voting(Request $request): View
    {
        $election = KpumElection::findOrFail(1);
        $elector = DB::table('kpum_electors')->where('election_id', 1)->where('user_id', $request->user()->id)->first();
        $results = $election->status === 'published';
        $candidates = $results ? ($election->status === 'published' ? $election->tally() : collect()) : KpumCandidate::where('election_id', 1)->where('status', 'approved')->orderBy('number')->get();

        return view('administration.voting', compact('election', 'elector', 'results', 'candidates'));
    }

    public function candidate(int $id): View
    {
        $candidate = KpumCandidate::where('election_id', 1)->where('status', 'approved')->findOrFail($id);

        return view('administration.candidate', compact('candidate'));
    }
}
