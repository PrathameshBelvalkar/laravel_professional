<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $promo_codes = [
            [
                'promo_code' => 'GOLDEN25',
                'description' => 'Get 25% discount on first day of the launch to 25 users',
                'max_users' => 25,
                'end_date' => "2024-05-05",
                'status' => '1',
                "type" => "1",
                "value" => "25",
            ],
            [
                'promo_code' => 'SILVER50',
                'description' => 'Get 10% discount on first day of the launch to 50 users',
                'max_users' => 50,
                'end_date' => "2024-05-05",
                'status' => '1',
                "type" => "1",
                "value" => 10,
            ],
            [
                'promo_code' => 'BRONZE100',
                'description' => 'Get 5% discount on first day of the launch to 100 users',
                'max_users' => 100,
                'end_date' => "2024-05-05",
                'status' => '1',
                "type" => "1",
                "value" => 5,
            ],
        ];
        foreach ($promo_codes as $row) {
            PromoCode::create($row);
        }
    }
}
