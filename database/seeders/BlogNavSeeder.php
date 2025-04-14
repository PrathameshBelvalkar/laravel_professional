<?php

namespace Database\Seeders;

use App\Models\Blog\BlogNav;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BlogNavSeeder extends Seeder
{
  /**
  * Run the database seeds.
  */
  public function run(): void
  {
    $navs = [
      [
        'id' => 1,
        'nav' => 'Learn',
        'link' => 'learn',
        "logo" => "https://blog.sovereignstateofgoodhope.com/logo.png",
        'created_at' => now(),
        'updated_at' => now(),
      ],
      [
        'id' => 2,
        'nav' => 'Swear to Oath',
        'link' => 'oath',
        'logo' => "",
        'created_at' => now(),
        'updated_at' => now(),
      ],
      [
        'id' => 3,
        'nav' => 'Constitution',
        'link' => 'read',
        'logo' => "",
        'created_at' => now(),
        'updated_at' => now(),
      ],
    ];

    foreach ($navs as $nav) {
      BlogNav::create($nav);
    }
  }
}
