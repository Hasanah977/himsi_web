<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OpsGroupsSeeder extends Seeder
{
    public function run(): void
    {
        $groups = ['umum' => ['Umum HIMSI', null]];
        foreach (config('superadmin.departments') as $key => $label) {
            $groups['departemen-'.$key] = ['Departemen '.$label, $key];
        }
        foreach ($groups as $key => [$name, $department]) {
            DB::table('ops_groups')->insertOrIgnore(['system_key' => $key, 'department' => $department, 'name' => $name, 'description' => 'Ruang koordinasi '.$name.'.', 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
