<?php

namespace Database\Seeders;

use Database\Seeders\CreatePackageSeed;
use Database\Seeders\CreateServicePlansSeed;
use Database\Seeders\CreateServicesSeed;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run()
  {
    $this->call(CountrySeeder::class);
    $this->call(StateSeeder::class);
    $this->call(CitySeeder::class);
    $this->call(CreateRolesSeed::class);
    $this->call(CreatePrimaryUsersSeed::class);
    $this->call(CreateServicesSeed::class);
    $this->call(CreateServicePlansSeed::class);
    $this->call(CreatePackageSeed::class);
    $this->call(SupportCategoriesSeeder::class);
    $this->call(SupportQuestionSeeder::class);
    $this->call(SiteSettingSeeder::class);
    $this->call(CoinCategorySeeder::class);
    $this->call(CoinSubcategorySeeder::class);
    $this->call(SectionsTableSeeder::class);
    $this->call(AppsTableSeeder::class);
    $this->call(DashboardImageSeeder::class);
    $this->call(LanguageSeed::class);
    $this->call(FlipbookCategoriesSeeder::class);
    $this->call(PodcastCategorySeeder::class);
    $this->call(PodcastTagSeeder::class);
  }
}
