<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admins')->insert([
            [
                'admin_name' => 'Admin',
                'email' => 'admin@trinity.edu',
                'admin_password' => Hash::make('tr1n1ty@edu'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
