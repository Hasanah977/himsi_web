<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\DatabaseRecovery;
use App\Support\EmergencyState;
use App\Support\SecurityAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AdminSecurityController extends Controller
{
    public function __construct(private EmergencyState $state, private DatabaseRecovery $recovery) {}

    public function index(): View
    {
        return view('admin.security.index', [
            'state' => $this->state->read(),
            'backups' => $this->recovery->listing(),
            'dangerCount' => SecurityEvent::where('severity', 'danger')->where('created_at', '>=', now()->subDay())->count(),
            'latestAlerts' => SecurityEvent::where('severity', 'danger')->latest('id')->limit(5)->get(),
        ]);
    }

    public function maintenance(Request $request): RedirectResponse
    {
        $data = $request->validate(['enabled' => ['required', 'boolean']]);
        $enabled = (bool) $data['enabled'];
        $this->state->change(['maintenance' => $enabled]);
        SecurityAudit::record('maintenance', $enabled ? 'Akses halaman publik ditutup.' : 'Akses halaman publik dibuka kembali.', $enabled ? 'danger' : 'info');

        return back()->with('success', $enabled ? 'Halaman publik sekarang menampilkan mode perbaikan. Panel internal tetap dapat diakses.' : 'Halaman publik dibuka kembali.');
    }

    public function freeze(): RedirectResponse
    {
        $this->state->traffic(function (): void {
            $this->state->change(['frozen' => true], true);
            User::where('role', '!=', 'super_admin')->orWhereNull('role')->update(['remember_token' => null]);
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table'))->whereNotIn('user_id', User::where('role', 'super_admin')->pluck('id'))->orWhereNull('user_id')->delete();
            }
            SecurityAudit::record('freeze', 'Seluruh sesi dan login non-Super Admin dibekukan.', 'danger');
        }, true);

        return back()->with('success', 'Akses seluruh role selain Super Admin dibekukan. Sesi lama tidak dapat digunakan kembali.');
    }

    public function unfreeze(): RedirectResponse
    {
        $this->state->traffic(fn () => $this->state->change(['frozen' => false]), true);
        SecurityAudit::record('unfreeze', 'Akses login non-Super Admin dibuka kembali.');

        return back()->with('success', 'Pengguna dapat login kembali. Akun yang sebelumnya nonaktif tetap nonaktif.');
    }

    public function backup(): RedirectResponse
    {
        try {
            $backup = $this->state->traffic(fn () => $this->recovery->create(), true);
            SecurityAudit::record('backup', 'Backup database terenkripsi berhasil dibuat.', context: ['backup_id' => $backup['id']]);
        } catch (Throwable $exception) {
            report($exception);
            SecurityAudit::record('backup_failed', 'Pembuatan backup gagal.', 'danger');

            return back()->withErrors(['backup' => 'Backup gagal. Periksa ruang penyimpanan dan koneksi database. Data tidak diubah.']);
        }

        return back()->with('success', 'Backup database berhasil dibuat dan integritasnya diverifikasi.');
    }

    public function download(string $backup): StreamedResponse
    {
        $this->recovery->read($backup);
        SecurityAudit::record('backup_download', 'Backup terenkripsi diunduh.', context: ['backup_id' => $backup]);

        return Storage::disk(config('security.backup_disk'))->download('security/backups/'.$backup.'.enc', 'himsi-'.$backup.'.enc', ['Cache-Control' => 'no-store, private']);
    }

    public function reviewRestore(string $backup): View
    {
        $payload = $this->recovery->read($backup);

        return view('admin.security.restore', ['backup' => $payload['metadata'], 'tables' => $payload['tables']]);
    }

    public function restore(Request $request, string $backup): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password'], 'confirm_restore' => ['accepted']]);
        try {
            $safety = $this->state->traffic(fn () => $this->recovery->restore($backup), true);
            SecurityAudit::record('restore', 'Database dipulihkan. Mode perbaikan dan pembekuan tetap aktif.', 'danger', ['backup_id' => $backup, 'safety_backup_id' => $safety['id']]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            SecurityAudit::record('restore_failed', 'Pemulihan gagal. Periksa cadangan pengaman sebelum membuka akses.', 'danger', ['backup_id' => $backup]);

            return back()->withErrors(['backup' => 'Restore gagal. Data yang sedang diganti dibatalkan jika transaksi belum selesai. Periksa log server dan cadangan pengaman; jangan buka akses sebelum diperiksa.']);
        }

        return redirect()->route('admin.security.index')->with('success', 'Database berhasil dipulihkan. Akun Super Admin saat ini tetap dipertahankan. Periksa hasilnya, kemudian buka kembali akses publik dan login secara terpisah.');
    }

    public function logs(Request $request): View
    {
        $filters = $request->validate(['severity' => ['nullable', Rule::in(['info', 'warning', 'danger'])], 'q' => ['nullable', 'string', 'max:100']]);
        $events = SecurityEvent::query()->when($filters['severity'] ?? null, fn ($query, $severity) => $query->where('severity', $severity))
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('actor_name', 'like', '%'.$search.'%')->orWhere('summary', 'like', '%'.$search.'%')->orWhere('route', 'like', '%'.$search.'%')))
            ->latest('id')->paginate(30)->withQueryString();

        return view('admin.security.logs', compact('events', 'filters'));
    }
}
