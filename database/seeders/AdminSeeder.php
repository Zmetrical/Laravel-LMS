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
                'admin_type' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'admin_name' => 'Super Admin',
                'email' => 'super_admin@trinity.edu',
                'admin_password' => Hash::make('tr1n1ty@edu'),
                'admin_type' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
