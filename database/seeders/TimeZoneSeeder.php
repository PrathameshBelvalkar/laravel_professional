<?php

namespace Database\Seeders;

use App\Models\TimeZone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimeZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('time_zones')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $jsonString = file_get_contents(base_path('database/seeders/json/time_zones.json'));
        $timezones = json_decode($jsonString, TRUE);
        foreach ($timezones as $timezone) {
            TimeZone::create([
                'javascript_tz' => $timezone['javascript_tz'],
                'php_tz' => $timezone['php_tz'],
            ]);
        }
    }
}
