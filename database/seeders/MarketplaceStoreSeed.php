<?php

namespace Database\Seeders;

use App\Models\Marketplace\MarketplaceStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MarketplaceStoreSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // DB::table('marketplace_stores')->truncate();
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $marketplace_stores = [
            [
                'id' => 1,
                'name' => 'Noitavonne',
                'slug' => 'noitavonne',
                'logo' => 'assets/marketplace/stores/noitavonne.png',
                'description' => 'Electronics appliances and gadgets store',
                "status" => "1",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'RP Digital',
                'slug' => 'rp_digital',
                'logo' => 'assets/marketplace/stores/rp_digital.png',
                'description' => ' Electronics products store',
                "status" => "1",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'NuAirs',
                'slug' => 'nuairs',
                'logo' => 'assets/marketplace/stores/nuairs.png',
                'description' => 'Noitavonne Helthcare devices',
                "status" => "1",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Kanobetriss',
                'slug' => 'kanobetriss',
                'logo' => 'assets/marketplace/stores/kanobetriss.png',
                'description' => 'Audio Headphone and Accessories store',
                "status" => "1",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        foreach ($marketplace_stores as $store) {
            MarketplaceStore::create($store);
        }
    }
}
