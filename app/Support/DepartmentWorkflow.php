<?php

namespace App\Support;

use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DepartmentWorkflow
{
    public static function programs(string $department): Builder
    {
        return OpsProgram::where('department', $department)->with('pic')->withCount(['tasks', 'tasks as done_count' => fn ($q) => $q->where('status', 'done'), 'members']);
    }

    public static function people(string $department): Builder
    {
        return User::where('department', $department)->where('is_active', true)->whereIn('role', ['koordinator_departemen', 'anggota_departemen']);
    }

    public static function program(string $department, int $id, bool $lock = false): OpsProgram
    {
        $query = self::programs($department);
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->findOrFail($id);
    }

    public static function version(int $current, int $sent): void
    {
        if ($current !== $sent) {
            throw ValidationException::withMessages(['version' => 'Data sudah berubah. Muat ulang halaman sebelum menyimpan.']);
        }
    }

    public static function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['workflow' => $message]);
        }
    }

    public static function history(OpsProgram $program, string $action, ?string $note = null, array $snapshot = []): void
    {
        DB::table('ops_history')->insert(['program_id' => $program->id, 'actor_id' => auth()->id(), 'action' => $action, 'note' => $note, 'snapshot' => json_encode($snapshot), 'created_at' => now()]);
    }

    public static function file(?string $path, ?string $name = null, bool $preview = false): StreamedResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        if ($preview && Storage::disk('local')->mimeType($path) === 'application/pdf') {
            return Storage::disk('local')->response($path, $name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'], 'inline');
        }

        return Storage::disk('local')->download($path, $name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public static function canReadSubmission(User $user, OpsSubmission $submission): bool
    {
        if ($user->isSuperAdmin() || ($user->role === 'koordinator_departemen' && $user->department === $submission->program->department)) {
            return true;
        }
        if ($submission->status === 'draft') {
            return false;
        }
        if ($submission->type === 'rpk' && $user->role === 'koordinator_pengawas') {
            return in_array($submission->status, ['pending_supervisor', 'pending_archive', 'archived'], true);
        }

        return in_array($user->role, match ($submission->type) {
            'letter' => ['sekretaris_umum', 'ketua_umum'],
            'budget' => ['ketua_umum', 'bendahara_umum'],
            'report' => ['ketua_umum', 'koordinator_pengawas', 'sekretaris_umum'],
            'rpk' => ['sekretaris_umum', 'bendahara_umum', 'ketua_umum'],
            default => [],
        }, true);
    }
}
