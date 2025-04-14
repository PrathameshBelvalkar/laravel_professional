<?php

namespace Database\Seeders;

use App\Models\Subscription\Package;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CreatePackageSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'id' => 1,
                'name' => 'Individual',
                'key' => 'individual',
                'services' => '{}',
                'monthly_price' => "0",
                'quarterly_price' => '0',
                'yearly_price' => '0',
                'type' => '1',
                "logo" => "assets/images/packages/bronze.svg",
            ],
            [
                'id' => 2,
                'name' => 'Trial',
                'key' => 'trial',
                'services' => '{}',
                'monthly_price' => "0",
                'quarterly_price' => '0',
                'yearly_price' => '0',
                'type' => '2',
                "logo" => "assets/images/packages/bronze.svg",
            ],
            [
                'id' => 3,
                'name' => 'Bronze',
                'key' => 'bronze',
                'services' => '{"1": 1,"2": 4,"3": 7}',
                'monthly_price' => "10",
                'quarterly_price' => '30',
                'yearly_price' => '120',
                'type' => '3',
                "logo" => "assets/images/packages/bronze.svg",
            ],
            [
                'id' => 4,
                'name' => 'Silver',
                'key' => 'silver',
                'services' => '{"1": 2,"2": 5,"3": 8}',
                'monthly_price' => "10",
                'quarterly_price' => '30',
                'yearly_price' => '120',
                'type' => '3',
                "logo" => "assets/images/packages/silver.svg",
            ],
            [
                'id' => 5,
                'name' => 'Gold',
                'key' => 'gold',
                'services' => '{"1": 3,"2": 6,"3": 9}',
                'monthly_price' => "10",
                'quarterly_price' => '30',
                'yearly_price' => '120',
                'type' => '3',
                "logo" => "assets/images/packages/gold.svg",
            ],
        ];
        foreach ($packages as $package) {
            Package::create($package);
        }
    }
}
