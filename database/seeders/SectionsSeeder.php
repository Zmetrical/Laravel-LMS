<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sections')->insert([
            ['name' => 'Sagittarius','strand_id'=> 1,  'status' => 1],
            ['name' => 'Capricorn','strand_id'=> 1,   'status' => 1],
            ['name' => 'Virgo','strand_id'=> 2, 'status' => 1],
            ['name' => 'Aries','strand_id'=> 2,  'status' => 1],
            ['name' => 'Ophiuchus','strand_id'=> 3,   'status' => 1],
            ['name' => 'Leo','strand_id'=> 3, 'status' => 1],
        ]);
    }
}
