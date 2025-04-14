<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MarketplaceSubCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MarketplaceSubCategorySeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marketplace_categories = [
            [
                'id' => 1,
                'sub_category_name' => 'Mobile',
                'parent_category_id' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'sub_category_name' => 'Headphone',
                'parent_category_id' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'sub_category_name' => 'CCTV',
                'parent_category_id' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'sub_category_name' => 'Washing machine',
                'parent_category_id' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'sub_category_name' => 'Refrigerator',
                'parent_category_id' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'sub_category_name' => 'Dish washer',
                'parent_category_id' => '2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'sub_category_name' => 'Smart watch',
                'parent_category_id' => '3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'sub_category_name' => 'Smart band',
                'parent_category_id' => '3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'sub_category_name' => 'Mask',
                'parent_category_id' => '4',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'sub_category_name' => 'Smart Healthcare Devices',
                'parent_category_id' => '4',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'sub_category_name' => 'Accessories',
                'parent_category_id' => '5',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'sub_category_name' => 'Bike',
                'parent_category_id' => '5',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($marketplace_categories as $marketplace_category) {
            MarketplaceSubCategory::create($marketplace_category);
        }
    }
}
