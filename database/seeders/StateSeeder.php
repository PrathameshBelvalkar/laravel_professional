<?php

namespace Database\Seeders;

use App\Models\StateModel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('states')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $jsonString = file_get_contents(base_path('database/seeders/json/states.json'));
        $json = json_decode($jsonString, TRUE);
        foreach ($json as $data) {
            StateModel::create([
                'name' => $data['name'],
                'id' => $data['id'],
                'country_id' => $data['country_id'],
            ]);
        }
    }
}
