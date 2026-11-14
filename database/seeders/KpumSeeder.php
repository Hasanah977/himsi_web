<?php

namespace Database\Seeders;

use App\Models\KpumCandidate;
use App\Models\KpumElection;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class KpumSeeder extends Seeder
{
    public function run(): void
    {
        $panitiaKpum = User::where('email', 'panitia.kpum@himsi.test')->first();

        // 1. Pemilihan Ketua Umum HIMSI
        $election = KpumElection::updateOrCreate(
            ['id' => 1],
            [
                'title' => 'Pemilihan Ketua Umum HIMSI Periode 2026/2027',
                'period' => '2026/2027',
                'status' => 'published',
                'starts_at' => now()->subDays(7),
                'ends_at' => now()->subDays(2),
                'closed_at' => now()->subDays(2),
                'certified_at' => now()->subDay(),
                'published_at' => now()->subDay(),
                'audit_note' => 'Pemilihan diselenggarakan secara langsung, umum, bebas, dan rahasia melalui e-voting terenkripsi HIMSI.',
                'registration_open' => false,
            ]
        );

        // 2. Kandidat Ketua Umum
        $candidate1User = User::where('email', 'anggota.advokasi@himsi.test')->first();
        $candidate2User = User::where('email', 'kbh2@himsi.test')->first();

        $c1 = KpumCandidate::updateOrCreate(
            ['election_id' => $election->id, 'nim' => 'D0221310'],
            [
                'number' => 1,
                'name' => 'Ahmad Fauzan & Tim Sinergi',
                'angkatan' => 2022,
                'ipk' => 3.82,
                'vision' => 'Mewujudkan HIMSI sebagai episentrum inovasi, kepemimpinan adaptif, dan kolaborasi berdaya saing global.',
                'mission' => "1. Mengembangkan kompetensi IT anggota melalui pelatihan intensif dan sertifikasi.\n2. Memperluas jejaring kemitraan dengan industri teknologi dan ikatan alumni.\n3. Mewujudkan tata kelola organisasi yang transparan, modern, dan akuntabel.",
                'photo' => 'candidates/kandidat_1.jpg',
                'status' => 'approved',
                'review_note' => 'Seluruh berkas persyaratan lolos verifikasi administrasi dan debat kandidat.',
                'user_id' => $candidate1User?->id,
            ]
        );

        $c2 = KpumCandidate::updateOrCreate(
            ['election_id' => $election->id, 'nim' => 'D0221315'],
            [
                'number' => 2,
                'name' => 'Fikri Haikal & Tim Kolaborasi',
                'angkatan' => 2022,
                'ipk' => 3.75,
                'vision' => 'HIMSI yang inklusif, profesional, dan berdampak nyata bagi almamater dan masyarakat luas.',
                'mission' => "1. Optimalisasi advokasi dan ruang aspirasi bagi seluruh mahasiswa Sistem Informasi.\n2. Pembinaan riset dan pengabdian masyarakat berbasis teknologi informasi.\n3. Membangun kultur kekeluargaan yang solid antargenerasi.",
                'photo' => 'candidates/kandidat_2.jpg',
                'status' => 'approved',
                'review_note' => 'Seluruh berkas persyaratan dinyatakan lengkap dan memenuhi syarat.',
                'user_id' => $candidate2User?->id,
            ]
        );

        // 3. Daftar Pemilih Tetap (Electors) & Suara Masuk (Ballots)
        $users = User::where('is_active', true)->whereNotNull('email_verified_at')->get();
        foreach ($users as $index => $voter) {
            $hasVoted = $index < ($users->count() - 2); // mayoritas sudah memilih
            DB::table('kpum_electors')->updateOrInsert(
                ['election_id' => $election->id, 'user_id' => $voter->id],
                ['has_voted' => $hasVoted]
            );

            if ($hasVoted) {
                // Pilih candidate 1 atau 2 bergantian (c1 mendapat lebih banyak suara)
                $chosenCandidate = ($index % 3 === 0) ? $c2 : $c1;
                $ballotExists = DB::table('kpum_ballots')
                    ->where('election_id', $election->id)
                    ->where('candidate_id', $chosenCandidate->id)
                    ->count();

                if ($ballotExists < 15) {
                    DB::table('kpum_ballots')->insert([
                        'id' => Str::uuid()->toString(),
                        'election_id' => $election->id,
                        'candidate_id' => $chosenCandidate->id,
                        'cast_at' => now()->subDays(3)->addHours($index),
                    ]);
                }
            }
        }

        // 4. Log Audit KPUM
        DB::table('kpum_audits')->insertOrIgnore([
            [
                'election_id' => $election->id,
                'actor_id' => $panitiaKpum?->id,
                'action' => 'publish_results',
                'detail' => 'Hasil rekapitulasi suara e-voting telah diverifikasi oleh saksi dan dipublikasikan.',
                'created_at' => now()->subDay(),
            ],
            [
                'election_id' => $election->id,
                'actor_id' => $panitiaKpum?->id,
                'action' => 'close_ballot_box',
                'detail' => 'Kotak suara digital ditutup secara otomatis pada batas akhir voting.',
                'created_at' => now()->subDays(2),
            ],
        ]);
    }
}
