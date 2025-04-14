<?php

namespace Database\Seeders;

use App\Models\Subscription\Service;
use Illuminate\Database\Seeder;

class CreateServicesSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'id' => 1,
                'name' => 'Calendar',
                'key' => 'calendar',
                'link' => 'https://calendar.project.io/',
                'is_external_app' => "1",
                'is_free' => '1',
                "logo" => "assets/images/services/stream.svg",
            ],
            [
                'id' => 2,
                'name' => 'Streaming deck',
                'key' => 'streaming_deck',
                'link' => 'https://streamingdeck.project.io/',
                'is_external_app' => "1",
                'is_free' => '0',
                "trial_period" => 10,
                "logo" => "assets/images/services/stream.svg",
            ],
            [
                'id' => 3,
                'name' => 'QR',
                'key' => 'qr',
                'link' => 'https://qr.project.io/',
                'is_external_app' => "1",
                'is_free' => '1',
                "logo" => "assets/images/services/stream.svg",
            ],
            [
                'id' => 4,
                'name' => 'MarketPlace',
                'key' => 'marketplace',
                'link' => 'https://marketplace.project.io/',
                'is_external_app' => "1",
                'is_free' => '1',
                "logo" => "assets/images/services/stream.svg",
            ],
            [
                'id' => 5,
                'name' => 'Health',
                'key' => 'health',
                'link' => 'https://health.project.io/',
                'is_external_app' => "1",
                'is_free' => '0',
                "logo" => "assets/images/services/stream.svg",
            ],
            [
                'id' => 6,
                'name' => 'Storage',
                'key' => 'storage',
                'link' => 'https://storage.silocloud.io/',
                'is_external_app' => "1",
                'is_free' => '0',
                "logo" => "assets/images/services/stream.svg",
            ],
            [
                'id' => 7,
                'name' => 'AI',
                'key' => 'ai',
                'link' => 'https://ai.silocloud.io/',
                'is_external_app' => "1",
                'is_external_service' => "1",
                'is_free' => '0',
                "logo" => "",
            ],
            [
                'id' => 8,
                'name' => 'Publisher',
                'key' => 'publisher',
                'link' => 'https://publisher.silocloud.io/',
                'is_external_app' => "1",
                'is_external_service' => "0",
                'is_free' => '0',
                "logo" => "",
            ],
        ];
        foreach ($services as $service) {
            Service::create($service);
        }
    }
}
