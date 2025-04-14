<?php

namespace Database\Seeders;

use App\Models\PlayerPosition;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlayerPositionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            [
                "id" => 1,
                'sport_id' => '1',
                'position' => 'Goalkeeper',
                "Key" => "goalkeeper",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                "id" => 2,
                'sport_id' => '1',
                'position' => 'Defenders',
                "Key" => "defenders",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                "id" => 3,
                'sport_id' => '1',
                'position' => 'Midfielders',
                "Key" => "midfielders",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                "id" => 4,
                'sport_id' => '1',
                'position' => 'Attackers or Strikers',
                "Key" => "attackers_or_strikers",
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        foreach ($positions as $position) {
            PlayerPosition::create($position);
        }
    }
}
