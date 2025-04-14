<?php

namespace Database\Seeders;

use App\Models\Sport;
use Illuminate\Database\Seeder;


class SportsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sports = [
            [
                "id" => 1,
                'user_id' => '2',
                'sport_name' => 'Football',
                "sport_image" => "assets\images\sports\\footabll.jpg",
                "sport_description" => "Soccer, also known as football in some regions, is one of the most popular sports globally, with an estimated 4 billion fans worldwide",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                "id" => 2,
                'user_id' => '2',
                'sport_name' => 'Basketball',
                "sport_image" => "assets\images\sports\basketball.jpg",
                "sport_description" => "Basketball is a popular team sport played worldwide, characterized by two teams of five players each trying to score points by throwing a ball through a hoop elevated 10 feet above the ground.",
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                "id" => 3,
                'user_id' => '2',
                'sport_name' => 'Rugby',
                "sport_image" => "assets\images\sports\\rugbyimage.jpg",
                "sport_description" => "Rugby is a widely loved sport with a passionate following across several nations, especially in countries such as Australia, England, South Africa, France, Wales, Ireland, Scotland, and Argentina.",
                'created_at' => now(),
                'updated_at' => now(),
            ],


        ];
        foreach ($sports as $sport) {
            Sport::create($sport);
        }
    }
}
