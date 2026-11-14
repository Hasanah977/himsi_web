<?php

namespace Database\Seeders;

use App\Models\MemberDocument;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class MemberDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereIn('role', ['kbh', 'ketua_umum', 'anggota_departemen'])->get();

        foreach ($users as $user) {
            $docs = [
                [
                    'category' => 'Kartu Identitas',
                    'title' => 'Kartu Tanda Mahasiswa (KTM)',
                    'filename' => 'ktm_'.$user->id.'.pdf',
                    'content' => 'KTM '.$user->name.' - NIM '.$user->nim,
                    'mime' => 'application/pdf',
                ],
                [
                    'category' => 'Sertifikat',
                    'title' => 'Sertifikat Kelulusan Character Building',
                    'filename' => 'sertifikat_cb_'.$user->id.'.pdf',
                    'content' => 'Sertifikat Character Building atas nama '.$user->name,
                    'mime' => 'application/pdf',
                ],
                [
                    'category' => 'SK Kepengurusan',
                    'title' => 'Surat Keputusan Pengurus Periode 2026/2027',
                    'filename' => 'sk_pengurus_'.$user->id.'.pdf',
                    'content' => 'SK Pengangkatan Jabatan Organisasi '.$user->name,
                    'mime' => 'application/pdf',
                ],
            ];

            foreach ($docs as $doc) {
                $path = 'member-archives/'.$user->id.'/'.$doc['filename'];
                Storage::disk('local')->put($path, $doc['content']);
                MemberDocument::updateOrCreate(
                    ['user_id' => $user->id, 'path' => $path],
                    [
                        'category' => $doc['category'],
                        'title' => $doc['title'],
                        'original_name' => $doc['filename'],
                        'size' => strlen($doc['content']),
                        'mime_type' => $doc['mime'],
                    ]
                );
            }
        }
    }
}
