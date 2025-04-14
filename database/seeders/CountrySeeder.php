<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('countries')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $jsonString = file_get_contents(base_path('database/seeders/json/countries.json'));
        $json = json_decode($jsonString, TRUE);
        foreach ($json as $data) {
            Country::create([
                'name' => $data['name'],
                'phonecode' => !empty($data['phonecode']) ? (int) $data['phonecode'] : null,
                'shortname' => !empty($data['shortname']) ? $data['shortname'] : null,
                'status' => 'ACTIVE',
            ]);
        }
    }
}
