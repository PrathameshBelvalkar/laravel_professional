<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DashboardImageSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {

    DB::table('dashboard_images')->insert([
      [
        'images' => 'public/dashboard/wallpaper-3.jpg',
        'default' => true,
        'created_at' => now(),
        'updated_at' => now(),
      ],
      [
        'images' => 'public/dashboard/background-img.jpg',
        'default' => true,
        'created_at' => now(),
        'updated_at' => now(),
      ],
      [
        'images' => 'public/dashboard/wallpaper-1.jpg',
        'default' => true,
        'created_at' => now(),
        'updated_at' => now(),
      ],
      [
        'images' => 'public/dashboard/wallpaper-2.jpg',
        'default' => true,
        'created_at' => now(),
        'updated_at' => now(),
      ],
    ]);
  }
}
