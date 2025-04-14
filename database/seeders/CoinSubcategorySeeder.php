<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoinSubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('coin_sub_category')->insert([
            ['sub_category_name' => 'profit/loss'],
            ['sub_category_name' => 'consolidated'],
        ]);
    }
}
