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
        if (! app()->environment('local', 'testing')) {
            return;
        }
        $user = User::where('role', 'kbh')->first();
        if ($user) {
            $path = 'member-archives/'.$user->id.'/contoh.txt';
            $contents = 'Contoh arsip pribadi HIMSI.';
            Storage::disk('local')->put($path, $contents);
            MemberDocument::updateOrCreate(['user_id' => $user->id, 'path' => $path], [
                'category' => 'Lainnya', 'title' => 'Contoh arsip pribadi', 'original_name' => 'contoh.txt',
                'size' => strlen($contents), 'mime_type' => 'text/plain',
            ]);
        }
    }
}
