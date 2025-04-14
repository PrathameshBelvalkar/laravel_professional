<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MarketplaceCategory;

class MarketplaceCategorySeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marketplace_categories = [
            [
                'id' => 1,
                'category_name' => 'Electronics & Computers',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'category_name' => 'Home & Kitchen Appliences',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'category_name' => 'Smart Gadgets',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'category_name' => 'Health Care',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'category_name' => 'E-bike',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($marketplace_categories as $marketplace_category) {
            MarketplaceCategory::create($marketplace_category);
        }
    }
}
