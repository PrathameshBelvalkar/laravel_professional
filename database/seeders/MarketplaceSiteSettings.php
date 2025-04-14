<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MarketplaceSiteSetting;

class MarketplaceSiteSettings extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marketplace_site_setting = [
            [
                'id' => 1,
                'field_name' => 'Site Logo',
                'field_key' => 'site_logo',
                'field_output_value' => null,
                'category_id' => null,
                'sub_category_id' => null,
                'store_id' => null,
                'product_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'field_name' => 'Main Banner',
                'field_key' => 'main_banner',
                'field_output_value' => null,
                'category_id' => null,
                'sub_category_id' => null,
                'store_id' => null,
                'product_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'field_name' => 'Discount Banner',
                'field_key' => 'discount_slider1',
                'field_output_value' => null,
                'category_id' => null,
                'sub_category_id' => null,
                'store_id' => null,
                'product_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'field_name' => 'Discount Banner',
                'field_key' => 'discount_slider2',
                'field_output_value' => null,
                'category_id' => null,
                'sub_category_id' => null,
                'store_id' => null,
                'product_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'field_name' => 'Discount Banner',
                'field_key' => 'discount_slider3',
                'field_output_value' => null,
                'category_id' => null,
                'sub_category_id' => null,
                'store_id' => null,
                'product_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'field_name' => 'Product Banner',
                'field_key' => 'product_slider1',
                'field_output_value' => null,
                'category_id' => null,
                'sub_category_id' => null,
                'store_id' => null,
                'product_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'field_name' => 'Product Banner',
                'field_key' => 'product_slider2',
                'field_output_value' => null,
                'category_id' => null,
                'sub_category_id' => null,
                'store_id' => null,
                'product_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'field_name' => 'Product Banner',
                'field_key' => 'product_slider3',
                'field_output_value' => null,
                'category_id' => null,
                'sub_category_id' => null,
                'store_id' => null,
                'product_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($marketplace_site_setting as $marketplace_site_settings) {
            MarketplaceSiteSetting::create($marketplace_site_settings);
        }
    }
}
