<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $sections = [
            ['name' => 'Core Apps'],
            ['name' => 'Social Apps'],
            ['name' => 'Productivity Apps'],
            ['name' => 'Exchange Apps'],
        ];

        DB::table('app_sections')->insert($sections);
    }
}
