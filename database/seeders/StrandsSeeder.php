<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class StrandsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('strands')->insert([
            ['name' => 'ICT',  'status' => 1],
            ['name' => 'ABM',   'status' => 1],
            ['name' => 'GAS', 'status' => 1],
        ]);
    }
}
