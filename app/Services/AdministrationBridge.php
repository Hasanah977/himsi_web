<?php

namespace App\Services;

use App\Models\NotifikasiHimsi;
use App\Models\OpsProgram;
use App\Models\OpsSubmission;
use App\Models\SuratKeluar;
use App\Support\DepartmentWorkflow as Flow;
use Illuminate\Support\Facades\DB;

class AdministrationBridge
{
    public static function sync(SuratKeluar $letter): void
    {
        if (! $letter->ops_submission_id) {
            return;
        }
        $status = match ($letter->status) {
            'menunggu_persetujuan' => 'pending_chair','ditolak' => 'revision','disetujui' => $letter->file_final ? 'approved' : 'pending_chair',default => null
        };
        if (! $status) {
            return;
        }
        DB::transaction(function () use ($letter, $status) {
            $submission = OpsSubmission::findOrFail($letter->ops_submission_id);
            $program = OpsProgram::whereKey($submission->program_id)->lockForUpdate()->firstOrFail();
            $submission = OpsSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ($submission->status === 'sent' || ($submission->status === $status && (! $letter->file_final || $submission->file_path === $letter->file_final))) {
                return;
            }
            $submission->fill(['status' => $status, 'version' => $submission->version + 1, 'note' => $letter->catatan_persetujuan, 'file_path' => $letter->file_final ?: $letter->file_pratinjau ?: $letter->file_surat, 'file_name' => basename($letter->file_final ?: $letter->file_surat ?: 'surat.pdf'), 'payload' => array_merge($submission->payload ?? [], ['number' => $letter->nomor_surat, 'surat_keluar_id' => $letter->id])])->save();
            if (in_array($status, ['approved', 'revision'], true)) {
                $program->update(['status' => $status, 'version' => $program->version + 1]);
            }
            Flow::history($program, 'Persuratan — '.$status, $letter->catatan_persetujuan, $submission->toArray());
            NotifikasiHimsi::kirim($program->created_by, 'Pembaruan surat program', $program->title.': '.$status, route('department.program.show', [$program->department, $program->id], false), 'surat');
        });
    }
}
