<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class MenuSeeder extends Seeder
{
  /**
  * Run the database seeds.
  *
  * @return void
  */
  public function run()
  {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    DB::table('sidebarmenu')->truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    $jsonString = file_get_contents(base_path('database/seeders/json/Menu.json'));
    $jsonData = json_decode($jsonString, true);

    DB::table('sidebarmenu')->insert([
      'menudata' => json_encode($jsonData),
    ]);
  }
}
