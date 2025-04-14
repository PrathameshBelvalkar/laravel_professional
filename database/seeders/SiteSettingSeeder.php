<?php

namespace Database\Seeders;

use App\Models\Public\SiteSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // type => 1 => File path, 2 => value eg email
        // module => 1 => account 2 => qr 3 => support 4 => streaming 5 => marketplace 6 => calendar 7 => storage 8 => wallet 0 => all	
        $settings = [
            [
                'field_name' => 'Favicon',
                'field_key' => 'favicon',
                'description' => 'Favicon for account',
                'field_value' => "assets/images/site_settings/accounts/favicon.png",
                'type' => '1',
                "module" => "1",
            ],
            [
                'field_name' => 'Dark logo',
                'field_key' => 'logo_dark',
                'description' => 'Dark logo for account',
                'field_value' => "assets/images/site_settings/accounts/logo-dark.png",
                'type' => '1',
                "module" => "1",
            ],
            [
                'field_name' => 'Logo',
                'field_key' => 'logo',
                'description' => 'Log for account',
                'field_value' => "assets/images/site_settings/accounts/logo.png",
                'type' => '1',
                "module" => "1",
            ],
            [
                'field_name' => 'Payment Method',
                'field_key' => 'payment_method',
                'description' => '1 => Token based wallet 2 => USD based wallet 3 => Direct payment',
                'field_value' => "1",
                'type' => '2',
                "module" => "1",
            ],
            [
                'field_name' => 'Payment currency',
                'field_key' => 'payment_currency',
                'description' => 'Payment currency to accept payment from gateway',
                'field_value' => "usd",
                'type' => '2',
                "module" => "1",
            ],
            [
                'field_name' => 'PayPaL Client ID',
                'field_key' => 'paypal_client_id',
                'description' => 'used for actual paypal payment',
                'field_value' => "AZDxjDScFpQtjWTOUtWKbyN_bDt4OgqaF4eYXlewfBP4-8aqX3PiV8e1GWU6liB2CUXlkA59kJXE7M6R",
                'type' => '2',
                "module" => "1",
            ],
            [
                'field_name' => 'PayPaL merchant ID',
                'field_key' => 'payment_merchant_id',
                'description' => 'used for actual paypal payment',
                'field_value' => "YQZCHTGHUK5P8",
                'type' => '2',
                "module" => "1",
            ],
            [
                'field_name' => 'Auger Fee',
                'field_key' => 'transaction_fee',
                'description' => 'transaction_fee',
                'field_value' => "2.9",
                'type' => '2',
                "module" => "1",
            ],
            [
                'field_name' => 'Service Subscription',
                'field_key' => 'service_subscription',
                'description' => 'service subscription',
                'field_value' => "1",
                'type' => '2',
                "module" => "1",
            ],
            [
                'field_name' => 'App List',
                'field_key' => 'apps',
                'description' => 'App List',
                'field_value' => "1",
                'type' => '2',
                "module" => "1",
            ],
            [
                'field_name' => 'Package Subscription',
                'field_key' => 'package_subscription',
                'description' => 'Package subscription',
                'field_value' => "1",
                'type' => '2',
                "module" => "1",
            ],
            [
                'field_name' => 'Connection',
                'field_key' => 'connection',
                'description' => 'Connection',
                'field_value' => "1",
                'type' => '2',
                "module" => "1",
            ],
            [
                'field_name' => 'Refer and Earn',
                'field_key' => 'refer_and_earn',
                'description' => 'Refer and Earn',
                'field_value' => "1",
                'type' => '2',
                "module" => "1",
            ],
        ];
        foreach ($settings as $row) {
            SiteSetting::create($row);
        }
    }
}
