<?php

namespace App\Support;

use App\Models\KbhPendaftaranSeleksi as Application;
use App\Models\KbhPeriode as Period;
use App\Models\NotifikasiHimsi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruitmentWorkflow
{
    public const REVIEWERS = ['ketua_umum', 'sekretaris_umum', 'bendahara_umum'];

    public static function documents(int $batch): array
    {
        return $batch === 1 ? ['ktm' => 'KTM', 'transcript' => 'Transkrip Nilai'] : [
            'propesif' => 'Sertifikat Propesif', 'supporting' => 'Sertifikat Pendukung',
            'khs' => 'KHS', 'transcript' => 'Transkrip Nilai', 'ktm' => 'KTM',
        ];
    }

    public static function questions(): array
    {
        return [
            'commitment' => 'Bagaimana Anda membagi waktu kuliah dan tanggung jawab organisasi?',
            'integrity' => 'Apa yang Anda lakukan ketika menemukan kesalahan administrasi atau penggunaan dana?',
            'teamwork' => 'Bagaimana Anda menyelesaikan perbedaan pendapat dalam tim?',
            'competence' => 'Jelaskan pengalaman dan kemampuan yang mendukung posisi atau departemen pilihan Anda.',
            'initiative' => 'Program atau perbaikan apa yang ingin Anda jalankan di HIMSI?',
        ];
    }

    public static function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['recruitment' => $message]);
        }
    }

    public static function localDate(string $value): Carbon
    {
        return Carbon::createFromFormat('!Y-m-d\TH:i', $value, 'Asia/Makassar')->setTimezone(config('app.timezone'));
    }

    public function open(User $chair, array $data): Period
    {
        return DB::transaction(function () use ($chair, $data) {
            User::where('role', 'ketua_umum')->orderBy('id')->lockForUpdate()->get();
            self::require(! Period::whereNotNull('batch')->where('stage', '!=', 'completed')->exists(), 'Selesaikan batch yang sedang berjalan terlebih dahulu.');
            $parent = null;
            if ((int) $data['batch'] === 2) {
                $parent = Period::where('batch', 1)->latest('id')->lockForUpdate()->first();
                self::require($parent && $parent->stage === 'completed', 'Batch 1 harus selesai sebelum Batch 2 dibuka.');
                self::require(! Period::where('parent_id', $parent->id)->where('batch', 2)->exists(), 'Batch 2 untuk periode ini sudah pernah dibuka.');
                $officers = Application::where('periode_id', $parent->id)->where('status', 'lolos')->with('user')->get();
                foreach (['sekretaris_umum', 'bendahara_umum'] as $role) {
                    self::require($officers->contains(fn ($item) => $item->appointed_role === $role && $item->user->role === $role && $item->user->is_active), 'Sekertaris dan bendahara hasil Batch 1 harus aktif.');
                }
            } else {
                $previous = Period::where('batch', 1)->latest('id')->first();
                self::require(! $previous || Period::where('parent_id', $previous->id)->where('batch', 2)->where('stage', 'completed')->exists(), 'Selesaikan Batch 2 periode sebelumnya sebelum membuka periode baru.');
            }
            Period::where('jenis', 'seleksi')->update(['aktif' => false]);

            return Period::create([
                'jenis' => 'seleksi', 'nama' => $data['nama'], 'batch' => $data['batch'],
                'parent_id' => $parent?->id, 'created_by' => $chair->id,
                'mulai_pada' => now(), 'selesai_pada' => self::localDate($data['selesai_pada']),
                'aktif' => true, 'stage' => 'registration',
            ]);
        }, 3);
    }

    public function reviewer(Period $period): User
    {
        $ids = [$period->created_by];
        if ($period->batch === 2) {
            $ids = array_merge($ids, Application::where('periode_id', $period->parent_id)->where('status', 'lolos')->whereIn('appointed_role', ['sekretaris_umum', 'bendahara_umum'])->pluck('user_id')->all());
        }
        $reviewers = User::whereIn('id', $ids)->where('is_active', true)->whereIn('role', self::REVIEWERS)->orderBy('id')->get();
        self::require($reviewers->count() === ($period->batch === 1 ? 1 : 3), 'Tim pemeriksa belum lengkap atau ada akun yang tidak aktif. Hubungi ketua umum.');
        $counts = Application::where('periode_id', $period->id)->selectRaw('reviewer_id, COUNT(*) as total')->groupBy('reviewer_id')->pluck('total', 'reviewer_id');

        return $reviewers->sortBy(fn ($user) => $counts[$user->id] ?? 0)->first();
    }

    public function close(Period $period): void
    {
        DB::transaction(function () use ($period) {
            $period = Period::whereKey($period->id)->lockForUpdate()->firstOrFail();
            self::require($period->stage === 'registration', 'Pendaftaran sudah ditutup.');
            $period->update(['aktif' => false, 'stage' => 'review', 'selesai_pada' => now()]);
        });
    }

    public function reopen(Period $period, string $end): void
    {
        DB::transaction(function () use ($period, $end) {
            $period = Period::whereKey($period->id)->lockForUpdate()->firstOrFail();
            self::require($period->stage === 'review' && ! Application::where('periode_id', $period->id)->whereNotNull('interview_at')->exists(), 'Pendaftaran hanya dapat dibuka kembali sebelum jadwal wawancara dikirim.');
            $endsAt = self::localDate($end);
            self::require($endsAt->isFuture(), 'Batas pendaftaran harus setelah waktu sekarang (WITA).');
            $period->update(['stage' => 'registration', 'aktif' => true, 'selesai_pada' => $endsAt]);
        });
    }

    public function authorizeReviewer(User $user, Application $application): void
    {
        abort_unless(in_array($user->role, self::REVIEWERS, true) && $application->reviewer_id === $user->id, 403, 'Peserta ini ditugaskan kepada pemeriksa lain.');
    }

    public function review(User $reviewer, Application $application, array $data): void
    {
        DB::transaction(function () use ($reviewer, $application, $data) {
            $period = Period::whereKey($application->periode_id)->lockForUpdate()->firstOrFail();
            $application->refresh();
            $this->authorizeReviewer($reviewer, $application);
            self::require($period->batch !== null && in_array($period->stage, ['registration', 'review'], true) && $application->status === 'menunggu', 'Berkas ini sudah diverifikasi atau batch telah selesai.');
            $application->update(['status' => $data['status'], 'catatan' => $data['catatan'], 'reviewed_at' => now()]);
            $this->notify($application, $data['status'] === 'lulus_berkas' ? 'Selamat, Anda lulus seleksi berkas. Jadwal wawancara akan menyusul.' : 'Anda belum lulus seleksi berkas. '.$data['catatan']);
        });
    }

    public function schedule(User $reviewer, Application $application, array $data): void
    {
        DB::transaction(function () use ($reviewer, $application, $data) {
            $period = Period::whereKey($application->periode_id)->lockForUpdate()->firstOrFail();
            $application->refresh();
            $this->authorizeReviewer($reviewer, $application);
            self::require($period->batch !== null && ! $period->aktif && $period->stage === 'review', 'Tutup pendaftaran sebelum mengirim jadwal wawancara.');
            self::require(! Application::where('periode_id', $period->id)->where('status', 'menunggu')->exists(), 'Seluruh berkas peserta harus diverifikasi terlebih dahulu.');
            self::require(in_array($application->status, ['lulus_berkas', 'wawancara'], true), 'Hanya peserta yang lulus berkas dapat dijadwalkan.');
            $at = self::localDate($data['interview_at']);
            self::require($at->isFuture(), 'Jadwal wawancara harus setelah waktu sekarang (WITA).');
            $application->update(['status' => 'wawancara', 'interview_at' => $at, 'interview_location' => $data['interview_location']]);
            $this->notify($application, 'Anda lulus berkas. Wawancara: '.$at->copy()->timezone('Asia/Makassar')->format('d M Y H:i').' WITA di '.$data['interview_location'].'.');
        });
    }

    public function score(User $reviewer, Application $application, array $data): void
    {
        DB::transaction(function () use ($reviewer, $application, $data) {
            $period = Period::whereKey($application->periode_id)->lockForUpdate()->firstOrFail();
            $application->refresh();
            $this->authorizeReviewer($reviewer, $application);
            self::require($period->stage === 'review' && in_array($application->status, ['wawancara', 'dinilai'], true) && $application->interview_at?->isPast(), 'Wawancara belum dijadwalkan, belum waktunya, atau batch sudah selesai.');
            $department = $data['assigned_department'] ?? null;
            if ($period->batch === 2 && $data['outcome'] === 'pass') {
                self::require(in_array($department, $application->department_choices ?? [], true), 'Penempatan harus sesuai salah satu dari dua pilihan peserta.');
            }
            $scores = array_intersect_key($data['scores'], self::questions());
            $application->update([
                'status' => 'dinilai', 'interview_scores' => $scores,
                'score' => round(array_sum($scores) / count($scores), 2),
                'interview_notes' => $data['interview_notes'],
                'assigned_department' => $data['outcome'] === 'pass' ? $department : null,
            ]);
        });
    }

    public function finalize(Period $period, array $data): void
    {
        DB::transaction(function () use ($period, $data) {
            $period = Period::whereKey($period->id)->lockForUpdate()->firstOrFail();
            self::require($period->stage === 'review' && ! $period->aktif, 'Pendaftaran harus ditutup sebelum penetapan.');
            $applications = Application::with('user')->where('periode_id', $period->id)->orderByDesc('score')->orderBy('id')->lockForUpdate()->get();
            self::require($applications->isNotEmpty() && $applications->every(fn ($a) => in_array($a->status, ['dinilai', 'tidak_lolos'], true)), 'Selesaikan verifikasi dan wawancara seluruh peserta terlebih dahulu.');
            $appointments = [];
            if ($period->batch === 1) {
                foreach (['sekretaris_umum', 'bendahara_umum'] as $role) {
                    $chosen = $applications->firstWhere('id', (int) ($data[$role] ?? 0));
                    self::require($chosen && $chosen->status === 'dinilai' && $chosen->position === $role, 'Pilih peserta yang telah diwawancarai untuk masing-masing jabatan.');
                    $appointments[$chosen->id] = [$role, null];
                }
            } else {
                $groups = $applications->where('status', 'dinilai')->whereNotNull('assigned_department')->groupBy('assigned_department');
                foreach ($groups as $department => $members) {
                    $leaders = $members->where('score', $members->max('score'));
                    $leader = $leaders->count() === 1 ? $leaders->first() : $leaders->firstWhere('id', (int) ($data['coordinators'][$department] ?? 0));
                    self::require($leader !== null, 'Nilai tertinggi seri di '.config('superadmin.departments.'.$department).'. Pilih koordinator dari peserta dengan nilai tertinggi.');
                    foreach ($members as $member) {
                        $appointments[$member->id] = [$member->id === $leader->id ? 'koordinator_departemen' : 'anggota_departemen', $department];
                    }
                }
            }
            foreach ($applications as $application) {
                if (isset($appointments[$application->id])) {
                    [$role, $department] = $appointments[$application->id];
                    $user = User::whereKey($application->user_id)->lockForUpdate()->firstOrFail();
                    self::require($user->role === 'kbh' && $user->is_active, 'Akun peserta '.$user->name.' sudah berubah atau tidak aktif. Penetapan dibatalkan.');
                    $user->forceFill(['role' => $role, 'department' => $department, 'welcome_signature' => null])->save();
                    $application->update(['status' => 'lolos', 'appointed_role' => $role]);
                    MemberProfile::recordAppointment($user, $role, $department, $period->nama, 'selection:'.$application->id);
                    $this->notify($application, 'Selamat! Anda terpilih sebagai '.config('superadmin.roles.'.$role).($department ? ' '.config('superadmin.departments.'.$department) : '').'. Mari bertumbuh dan berkarya bersama HIMSI.');
                } elseif ($application->status !== 'tidak_lolos') {
                    $application->update(['status' => 'tidak_lolos']);
                    $this->notify($application, 'Terima kasih atas partisipasi Anda. Anda belum lolos seleksi akhir pengurus periode ini.');
                }
            }
            $period->update(['stage' => 'completed', 'completed_at' => now()]);
        }, 3);
    }

    private function notify(Application $application, string $message): void
    {
        NotifikasiHimsi::kirim($application->user_id, 'Hasil seleksi pengurus HIMSI', $message, route('recruitment.result', [], false), 'seleksi');
    }
}
