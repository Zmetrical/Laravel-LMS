<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LevelsSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('levels')->insert([
            [
                'name' => 'Grade 11',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Grade 12',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
