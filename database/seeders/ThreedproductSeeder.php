<?php

namespace Database\Seeders;

use App\Models\ThreeDCategory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ThreedproductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $model_categories = [
            [
                "id" => 1,
                'title' => 'Electronic',
                'description' => 'All electronic ',
                "Key" => "electronic",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                "id" => 2,
                'title' => 'vehical',
                'description' => 'All vehical',
                "Key" => "vehical",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                "id" => 3,
                'title' => 'Building',
                'description' => 'All building',
                "Key" => "building",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                "id" => 4,
                'title' => 'Medical',
                'description' => 'All medical',
                "Key" => "medical",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                "id" => 5,
                'title' => 'Home Appliances',
                'description' => 'All Home appliances',
                "Key" => "Home_appliances",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        foreach ($model_categories as $tag) {
            ThreeDCategory::create($tag);
        }
    }
}
