<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\StreamDeck\Genre;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class GenreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $JsonString=\file_get_contents(\base_path('database/seeders/json/Genres.json'));
        $json=json_decode($JsonString,TRUE);

        foreach ($json as $data) {
            Genre::create([
                'name'=>$data['name'],
                'slug'=>$data['slug'],
                'user_id'=>null
            ]);
        }
    }
}
