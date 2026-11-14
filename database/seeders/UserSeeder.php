<?php

namespace Database\Seeders;

use App\Models\HimsiRegistration;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');
        $period = DB::table('governance_periods')->whereNull('closed_at')->first();
        $periodId = $period?->id;

        $usersData = [
            // Super Admin
            [
                'name' => 'Super Admin HIMSI',
                'email' => 'superadmin@himsi.test',
                'role' => 'super_admin',
                'department' => null,
                'phone' => '081211112222',
                'is_active' => true,
                'registration' => null,
            ],
            // Pengurus Inti
            [
                'name' => 'Padli',
                'email' => 'ketua@himsi.test',
                'role' => 'ketua_umum',
                'department' => null,
                'phone' => '081234567001',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0221301',
                    'angkatan' => 2022,
                    'status' => 'approved',
                    'semester' => 7,
                    'gender' => 'Laki-laki',
                ],
            ],
            [
                'name' => 'Masdar',
                'email' => 'sekretaris@himsi.test',
                'role' => 'sekretaris_umum',
                'department' => null,
                'phone' => '081234567002',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0221302',
                    'angkatan' => 2022,
                    'status' => 'approved',
                    'semester' => 7,
                    'gender' => 'Laki-laki',
                ],
            ],
            [
                'name' => 'Waffiq Adkhilniy MR',
                'email' => 'bendahara@himsi.test',
                'role' => 'bendahara_umum',
                'department' => null,
                'phone' => '081234567003',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0221303',
                    'angkatan' => 2022,
                    'status' => 'approved',
                    'semester' => 7,
                    'gender' => 'Laki-laki',
                ],
            ],
            [
                'name' => 'M. Rizky Pratama',
                'email' => 'pengawas@himsi.test',
                'role' => 'koordinator_pengawas',
                'department' => null,
                'phone' => '081234567004',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0221304',
                    'angkatan' => 2022,
                    'status' => 'approved',
                    'semester' => 7,
                    'gender' => 'Laki-laki',
                ],
            ],
            // Panitia Khusus
            [
                'name' => 'Panitia CB HIMSI',
                'email' => 'panitia.cb@himsi.test',
                'role' => 'panitia_cb',
                'department' => null,
                'phone' => '081234567005',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0222305',
                    'angkatan' => 2023,
                    'status' => 'approved',
                    'semester' => 5,
                    'gender' => 'Laki-laki',
                ],
            ],
            [
                'name' => 'Panitia KPUM HIMSI',
                'email' => 'panitia.kpum@himsi.test',
                'role' => 'panitia_kpum',
                'department' => null,
                'phone' => '081234567006',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0222306',
                    'angkatan' => 2023,
                    'status' => 'approved',
                    'semester' => 5,
                    'gender' => 'Perempuan',
                ],
            ],
            // Koordinator Departemen (6 Departemen)
            [
                'name' => 'Serlia Turu Allo',
                'email' => 'kadep.akademik@himsi.test',
                'role' => 'koordinator_departemen',
                'department' => 'akademik',
                'phone' => '081234567011',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0222311',
                    'angkatan' => 2023,
                    'status' => 'approved',
                    'semester' => 5,
                    'gender' => 'Perempuan',
                ],
            ],
            [
                'name' => 'Nesa Alfiarianti',
                'email' => 'kadep.humas@himsi.test',
                'role' => 'koordinator_departemen',
                'department' => 'humas',
                'phone' => '081234567012',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0222312',
                    'angkatan' => 2023,
                    'status' => 'approved',
                    'semester' => 5,
                    'gender' => 'Perempuan',
                ],
            ],
            [
                'name' => 'Reza Anastasia',
                'email' => 'kadep.advokasi@himsi.test',
                'role' => 'koordinator_departemen',
                'department' => 'advokasi',
                'phone' => '081234567013',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0222313',
                    'angkatan' => 2023,
                    'status' => 'approved',
                    'semester' => 5,
                    'gender' => 'Perempuan',
                ],
            ],
            [
                'name' => 'Anugrah Wahyunni',
                'email' => 'kadep.kaderisasi@himsi.test',
                'role' => 'koordinator_departemen',
                'department' => 'kaderisasi',
                'phone' => '081234567014',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0222314',
                    'angkatan' => 2023,
                    'status' => 'approved',
                    'semester' => 5,
                    'gender' => 'Perempuan',
                ],
            ],
            [
                'name' => 'Rahmat',
                'email' => 'kadep.danus@himsi.test',
                'role' => 'koordinator_departemen',
                'department' => 'dana_usaha',
                'phone' => '081234567015',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0222315',
                    'angkatan' => 2023,
                    'status' => 'approved',
                    'semester' => 5,
                    'gender' => 'Laki-laki',
                ],
            ],
            [
                'name' => 'Paldiansyah',
                'email' => 'kadep.kesekretariatan@himsi.test',
                'role' => 'koordinator_departemen',
                'department' => 'kesekretariatan',
                'phone' => '081234567016',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0222316',
                    'angkatan' => 2023,
                    'status' => 'approved',
                    'semester' => 5,
                    'gender' => 'Laki-laki',
                ],
            ],
            // Anggota Departemen (6 Departemen)
            [
                'name' => 'Muh. Fadil',
                'email' => 'anggota.akademik@himsi.test',
                'role' => 'anggota_departemen',
                'department' => 'akademik',
                'phone' => '081234567021',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0223321',
                    'angkatan' => 2024,
                    'status' => 'approved',
                    'semester' => 3,
                    'gender' => 'Laki-laki',
                ],
            ],
            [
                'name' => 'Siti Nurhaliza',
                'email' => 'anggota.humas@himsi.test',
                'role' => 'anggota_departemen',
                'department' => 'humas',
                'phone' => '081234567022',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0223322',
                    'angkatan' => 2024,
                    'status' => 'approved',
                    'semester' => 3,
                    'gender' => 'Perempuan',
                ],
            ],
            [
                'name' => 'Ahmad Fauzan',
                'email' => 'anggota.advokasi@himsi.test',
                'role' => 'anggota_departemen',
                'department' => 'advokasi',
                'phone' => '081234567023',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0223323',
                    'angkatan' => 2024,
                    'status' => 'approved',
                    'semester' => 3,
                    'gender' => 'Laki-laki',
                ],
            ],
            [
                'name' => 'Putri Rahmawati',
                'email' => 'anggota.kaderisasi@himsi.test',
                'role' => 'anggota_departemen',
                'department' => 'kaderisasi',
                'phone' => '081234567024',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0223324',
                    'angkatan' => 2024,
                    'status' => 'approved',
                    'semester' => 3,
                    'gender' => 'Perempuan',
                ],
            ],
            [
                'name' => 'Dian Pratama',
                'email' => 'anggota.danus@himsi.test',
                'role' => 'anggota_departemen',
                'department' => 'dana_usaha',
                'phone' => '081234567025',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0223325',
                    'angkatan' => 2024,
                    'status' => 'approved',
                    'semester' => 3,
                    'gender' => 'Laki-laki',
                ],
            ],
            [
                'name' => 'Nurul Hikmah',
                'email' => 'anggota.kesekretariatan@himsi.test',
                'role' => 'anggota_departemen',
                'department' => 'kesekretariatan',
                'phone' => '081234567026',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0223326',
                    'angkatan' => 2024,
                    'status' => 'approved',
                    'semester' => 3,
                    'gender' => 'Perempuan',
                ],
            ],
            // KBH (Keluarga Besar Himpunan)
            [
                'name' => 'Andi Muh. Alif',
                'email' => 'kbh@himsi.test',
                'role' => 'kbh',
                'department' => null,
                'phone' => '081234567031',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0220301',
                    'angkatan' => 2021,
                    'status' => 'approved',
                    'semester' => 9,
                    'gender' => 'Laki-laki',
                ],
            ],
            [
                'name' => 'Fikri Haikal',
                'email' => 'kbh2@himsi.test',
                'role' => 'kbh',
                'department' => null,
                'phone' => '081234567032',
                'is_active' => true,
                'registration' => [
                    'nim' => 'D0220302',
                    'angkatan' => 2021,
                    'status' => 'approved',
                    'semester' => 9,
                    'gender' => 'Laki-laki',
                ],
            ],
            // Calon Pendaftar (Pending)
            [
                'name' => 'Budi Santoso',
                'email' => 'pending@himsi.test',
                'role' => 'pending',
                'department' => null,
                'phone' => '081234567041',
                'is_active' => false,
                'registration' => [
                    'nim' => 'D0224301',
                    'angkatan' => 2025,
                    'status' => 'pending',
                    'semester' => 1,
                    'gender' => 'Laki-laki',
                ],
            ],
        ];

        foreach ($usersData as $data) {
            $user = User::firstOrNew(['email' => $data['email']]);
            $user->name = $data['name'];
            $user->password = $password;
            $user->role = $data['role'];
            $user->department = $data['department'];
            $user->phone = $data['phone'];
            $user->is_active = $data['is_active'];
            $user->email_verified_at = now();
            $user->save();

            if (! empty($data['registration'])) {
                $reg = $data['registration'];
                HimsiRegistration::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nim' => $reg['nim'],
                        'angkatan' => $reg['angkatan'],
                        'program_studi' => 'Sistem Informasi',
                        'phone' => $data['phone'],
                        'status' => $reg['status'],
                        'certificate_path' => 'documents/certificates/sample.pdf',
                        'active_letter_path' => 'documents/active_letters/sample.pdf',
                        'student_card_path' => 'documents/student_cards/sample.pdf',
                        'birth_place' => 'Majene',
                        'birth_date' => '2004-05-15',
                        'semester' => $reg['semester'] ?? 5,
                        'gender' => $reg['gender'] ?? 'Laki-laki',
                        'religion' => 'Islam',
                        'guardian_name' => 'Orang Tua '.$data['name'],
                        'guardian_phone' => '081298765432',
                        'nik' => '760101'.str_pad((string) $user->id, 10, '0', STR_PAD_LEFT),
                        'nisn' => '00'.str_pad((string) $user->id, 8, '0', STR_PAD_LEFT),
                        'npwp' => '12.345.678.9-801.000',
                        'address_street' => 'Jl. Prof. Dr. Baharuddin Lopa, SH',
                        'address_rt' => '01',
                        'address_rw' => '02',
                        'address_hamlet' => 'Talumung',
                        'address_village' => 'Baurung',
                        'address_district' => 'Banggae Timur',
                        'address_city' => 'Majene',
                        'address_province' => 'Sulawesi Barat',
                        'address_postcode' => '91412',
                    ]
                );
            }

            // Catat riwayat SK kepengurusan untuk pengurus
            if (in_array($user->role, User::OFFICER_ROLES, true) && $periodId) {
                DB::table('member_appointments')->updateOrInsert(
                    ['source_key' => 'seeder:user:'.$user->id],
                    [
                        'user_id' => $user->id,
                        'governance_period_id' => $periodId,
                        'role' => $user->role,
                        'department' => $user->department,
                        'period_label' => 'Periode 2026/2027',
                        'appointed_at' => now()->startOfYear(),
                        'ended_at' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
