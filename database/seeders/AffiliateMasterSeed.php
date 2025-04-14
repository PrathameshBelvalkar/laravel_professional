<?php

namespace Database\Seeders;

use App\Models\Subscription\AffiliateMaster;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AffiliateMasterSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'name' => 'Register',
                'description' => 'Get 10% tokens on first transaction when your refered code used and your friend will get 5% discount',
                'banner' => "assets/images/site_settings/accounts/favicon.png",
                'affiliate_value' => 5,
                'refered_value' => 10,
                "status" => "1",
                "type" => "1",
            ]
        ];
        foreach ($settings as $row) {
            AffiliateMaster::create($row);
        }
    }
}
