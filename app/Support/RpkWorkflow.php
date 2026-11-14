<?php

namespace App\Support;

use App\Models\NotifikasiHimsi;
use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\User;

class RpkWorkflow
{
    public const OFFICERS = ['sekretaris_umum', 'bendahara_umum', 'ketua_umum'];

    public static function canUpload(OpsProgram $program): bool
    {
        $program->loadMissing('submissions');

        return $program->status === 'approved'
            && $program->submissions->contains(fn (OpsSubmission $submission): bool => $submission->type === 'letter' && in_array($submission->status, ['approved', 'sent'], true))
            && $program->submissions->contains(fn (OpsSubmission $submission): bool => $submission->type === 'budget' && in_array($submission->status, ['ready_funding', 'disbursed'], true));
    }

    public static function canReview(OpsSubmission $submission, User $user): bool
    {
        return match ($submission->status) {
            'pending_officers' => in_array($user->role, self::OFFICERS, true) && ! isset($submission->payload['approvals'][$user->role]),
            'pending_supervisor' => $user->role === 'koordinator_pengawas',
            'pending_archive' => $user->role === 'sekretaris_umum',
            default => false,
        };
    }

    /** @param array{action: string, note: string, version: int|string} $data */
    public static function decide(OpsSubmission $submission, OpsProgram $program, User $user, array $data): void
    {
        abort_unless(self::canReview($submission, $user), 403);
        DepartmentWorkflow::require($program->status === 'approved', 'PPRK hanya dapat diverifikasi sebelum pelaksanaan program kerja.');
        $payload = $submission->payload ?? [];
        $nextRoles = [];
        if ($submission->status === 'pending_archive') {
            DepartmentWorkflow::require($data['action'] === 'archive', 'PPRK yang telah diverifikasi Pengawas harus diarsipkan.');
            $status = 'archived';
            $payload['archived_by'] = $user->id;
            $payload['archived_at'] = now()->toIso8601String();
        } elseif (in_array($data['action'], ['revision', 'reject'], true)) {
            $status = $data['action'] === 'revision' ? 'revision' : 'rejected';
        } else {
            DepartmentWorkflow::require($data['action'] === 'approve', 'Pilih verifikasi, revisi, atau penolakan PPRK.');
            $payload['approvals'][$user->role] = ['user_id' => $user->id, 'name' => $user->name, 'at' => now()->toIso8601String(), 'note' => $data['note']];
            if ($submission->status === 'pending_supervisor') {
                DepartmentWorkflow::require(count(array_intersect(self::OFFICERS, array_keys($payload['approvals']))) === 3, 'Ketiga pengurus harus memverifikasi PPRK terlebih dahulu.');
                $status = 'pending_archive';
                $nextRoles = ['sekretaris_umum'];
            } else {
                $complete = count(array_intersect(self::OFFICERS, array_keys($payload['approvals']))) === 3;
                $status = $complete ? 'pending_supervisor' : 'pending_officers';
                $nextRoles = $complete ? ['koordinator_pengawas'] : [];
            }
        }
        $submission->update(['status' => $status, 'payload' => $payload, 'note' => $data['note'], 'version' => $submission->version + 1]);
        foreach ($nextRoles as $role) {
            User::where('role', $role)->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'PPRK menunggu tindakan', $program->title, route('department.review', $submission, false), 'pengajuan'));
        }
        User::where('role', 'koordinator_departemen')->where('department', $program->department)->where('is_active', true)->pluck('id')->each(fn ($id) => NotifikasiHimsi::kirim($id, 'Pembaruan PPRK', config('department.submission_statuses.'.$status).': '.$data['note'], route('department.program.show', [$program->department, $program], false), 'pengajuan'));
        DepartmentWorkflow::history($program, 'PPRK - '.config('department.submission_statuses.'.$status), $data['note'], $submission->toArray());
    }
}
