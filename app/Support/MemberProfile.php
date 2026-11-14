<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MemberProfile
{
    public static function labels(): array
    {
        return [
            'birth_place' => 'Tempat lahir', 'birth_date' => 'Tanggal lahir', 'semester' => 'Semester saat ini',
            'gender' => 'Jenis kelamin', 'religion' => 'Agama', 'guardian_name' => 'Nama orang tua/wali',
            'guardian_phone' => 'Nomor telepon orang tua/wali', 'nik' => 'NIK', 'nisn' => 'NISN', 'npwp' => 'NPWP',
            'address_street' => 'Jalan / alamat asal (sesuai KTP)', 'address_rt' => 'RT', 'address_rw' => 'RW',
            'address_hamlet' => 'Dusun / lingkungan', 'address_village' => 'Kelurahan / desa',
            'address_district' => 'Kecamatan', 'address_city' => 'Kabupaten / kota', 'address_province' => 'Provinsi',
            'address_postcode' => 'Kode pos',
        ];
    }

    public static function rules(): array
    {
        $rules = array_fill_keys(array_keys(self::labels()), ['required', 'string', 'max:255']);

        return array_replace($rules, [
            'birth_date' => ['required', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
            'semester' => ['required', 'integer', 'between:1,20'],
            'gender' => ['required', 'in:Laki-laki,Perempuan'],
            'religion' => ['required', 'in:Islam,Kristen,Katolik,Hindu,Buddha,Khonghucu,Kepercayaan lainnya'],
            'guardian_phone' => ['required', 'string', 'regex:/^\+?[0-9]{9,15}$/'],
            'nik' => ['nullable', 'digits:16'], 'nisn' => ['nullable', 'digits:10'],
            'npwp' => ['nullable', 'string', 'max:20', 'regex:/^[0-9.\-]+$/'],
            'address_rt' => ['required', 'digits_between:1,3'], 'address_rw' => ['required', 'digits_between:1,3'],
            'address_postcode' => ['nullable', 'digits:5'],
        ]);
    }

    public static function recordAppointment(User $user, string $role, ?string $department, string $periodLabel, string $source): void
    {
        $period = Governance::active();
        DB::table('member_appointments')->where('user_id', $user->id)->whereNull('ended_at')->where('source_key', '!=', $source)
            ->update(['ended_at' => now(), 'updated_at' => now()]);
        DB::table('member_appointments')->updateOrInsert(['source_key' => $source], [
            'user_id' => $user->id, 'governance_period_id' => $period?->id,
            'role' => $role, 'department' => $department, 'period_label' => $period?->name ?? $periodLabel,
            'appointed_at' => now(), 'ended_at' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** @return Collection<int, array{role: string, period: string, current: bool}> */
    public static function appointments(User $user): Collection
    {
        $appointments = DB::table('member_appointments as a')
            ->leftJoin('governance_periods as p', 'p.id', '=', 'a.governance_period_id')
            ->where('a.user_id', $user->id)->select('a.*', 'p.ends_at', 'p.closed_at')
            ->orderByDesc('a.appointed_at')->orderByDesc('a.id')->get();

        return $appointments->map(function (object $appointment, int $index) use ($user): array {
            $current = $index === 0 && ! $appointment->ended_at && ! $appointment->closed_at
                && (! $appointment->ends_at || now()->lt($appointment->ends_at))
                && $user->assignedRole() === $appointment->role && $user->department === $appointment->department;
            $role = config('superadmin.roles.'.$appointment->role, $appointment->role);
            $department = $appointment->department ? config('superadmin.departments.'.$appointment->department) : null;

            return ['role' => trim($role.' '.$department), 'period' => $appointment->period_label, 'current' => $current];
        });
    }
}
