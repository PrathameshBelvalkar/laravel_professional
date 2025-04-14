<?php

namespace Database\Seeders;

use App\Models\Podcast\Langauge;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('countries')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $jsonString = file_get_contents(base_path('database/seeders/json/languages.json'));
        $json = json_decode($jsonString, TRUE);
        foreach ($json as $data) {
            Langauge::create([
                'country_id' => $data['country_id'],
                'label' => $data['label'],
                'value' => $data['value'],
                'status' => 'ACTIVE',
            ]);
        }
    }
}
