<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoinCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('coin_category')->insert([
            ['category_name' => 'Yearly'],
            ['category_name' => 'Half Yearly'],
            ['category_name' => 'Quarterly'],
            ['category_name' => 'Monthly'],
        ]);
    }
}
