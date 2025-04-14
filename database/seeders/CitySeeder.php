<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('cities')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $jsonString = file_get_contents(base_path('database/seeders/json/cities.json'));
        $json = json_decode($jsonString, TRUE);
        foreach ($json as $data) {
            City::create([
                'name' => $data['name'],
                'id' => $data['id'],
                'state_id' => $data['state_id'],
            ]);
        }
    }
}
