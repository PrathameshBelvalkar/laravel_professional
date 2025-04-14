<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\Podcast\Podcategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PodcastCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define 20 unique gradients
        $gradients = [
            "linear-gradient(312deg, #ffc03c 0%, #be8000 100%)",
            "linear-gradient(312deg, #27856A 0%, #1A4A3A 100%)",
            "linear-gradient(312deg, #5F8109 0%, #304105 100%)",
            "linear-gradient(312deg, #F037A5 0%, #7B1957 100%)",
            "linear-gradient(312deg, #AF2896 0%, #631452 100%)",
            "linear-gradient(312deg, #477D95 0%, #2A4C57 100%)",
            "linear-gradient(312deg, #509BF5 0%, #254D7A 100%)",
            "linear-gradient(312deg, #1D3164 0%, #101B38 100%)",
            "linear-gradient(312deg, #E8115B 0%, #8B0831 100%)",
            "linear-gradient(312deg, #E13300 0%, #701A00 100%)",
            "linear-gradient(312deg, #BA5D07 0%, #5E2F03 100%)",
            "linear-gradient(312deg, #5F8109 0%, #304105 100%)",
            "linear-gradient(312deg, #7D5A50 0%, #2F1E18 100%)",
            "linear-gradient(312deg, #1A8C8B 0%, #0E4E4D 100%)",
            "linear-gradient(312deg, #9B4DCA 0%, #5D2A74 100%)",
            "linear-gradient(312deg, #F4A261 0%, #E76F51 100%)",
            "linear-gradient(312deg, #2A9D8F 0%, #264653 100%)",
            "linear-gradient(312deg, #6A4C93 0%, #321B50 100%)",
            "linear-gradient(312deg, #E9C46A 0%, #F4A261 100%)",
            "linear-gradient(312deg, #FFB4A2 0%, #E07A5F 100%)",
        ];

        $categories = [
            [
                "name" => 'Comedy',
                "icon" => "happy",
                "category_image" => "assets/images/podcast_genres/Comedy.png"
            ],
            [
                "name" => 'News & Politics',
                "icon" => "globe",
                "category_image" => "assets/images/podcast_genres/Politics1.png"
            ],
            [
                "name" => 'True Crime',
                "icon" => "crosshair",
                "category_image" => "assets/images/podcast_genres/True Crime.png"
            ],
            [
                "name" => 'Business',
                "icon" => "building",
                "category_image" => "assets/images/podcast_genres/Business.png"
            ],
            [
                "name" => 'Education',
                "icon" => "building",
                "category_image" => "assets/images/podcast_genres/Education.png"
            ],
            [
                "name" => 'Health & Wellness',
                "icon" => "capsule",
                "category_image" => "assets/images/podcast_genres/Health.png"
            ],
            [
                "name" => 'Technology',
                "icon" => "capsule",
                "category_image" => "assets/images/podcast_genres/Technology.png"
            ],
            [
                "name" => 'Science',
                "icon" => "capsule",
                "category_image" => "assets/images/podcast_genres/Science.png"
            ],
            [
                "name" => 'Sports',
                "icon" => "help-alt",
                "category_image" => "assets/images/podcast_genres/Sports.png",
            ],

            [
                "name" => 'History',
                "icon" => "book-read",
                "category_image" => "assets/images/podcast_genres/History.png"
            ],
            [
                "name" => 'Music',
                "icon" => "music",
                "category_image" => "assets/images/podcast_genres/Music.png"
            ],
            [
                "name" => 'Arts & Culture',
                "icon" => "sign-ada",
                "category_image" => "assets/images/podcast_genres/Culture.png"
            ],
            [
                "name" => 'Lifestyle & Hobbies',
                "icon" => "coffee",
                "category_image" => "assets/images/podcast_genres/Lifestyle.png"
            ],
            [
                "name" => 'Religion & Spirituality',
                "icon" => "sun",
                "category_image" => "assets/images/podcast_genres/religion.png"
            ],
            [
                "name" => 'Kids & Family',
                "icon" => "home-alt",
                "category_image" => "assets/images/podcast_genres/kids.png"
            ],
            [
                "name" => 'Food & Drink',
                "icon" => "bag",
                "category_image" => "assets/images/podcast_genres/food.png"
            ],
            [
                "name" => 'Self-Improvement & Motivation',
                "icon" => "hot-fill",
                "category_image" => "assets/images/podcast_genres/Self Love.png"
            ],
            [
                "name" => 'Fiction',
                "icon" => "sign-eos",
                "category_image" => "assets/images/podcast_genres/fictions.png"
            ],
            [
                "name" => 'TV & Film',
                "icon" => "ticket-alt",
                "category_image" => "assets/images/podcast_genres/Entertainment.png"
            ],
            [
                "name" => 'Gaming',
                "icon" => "star-round",
                "category_image" => "assets/images/podcast_genres/Game.png"
            ],
        ];

        foreach ($categories as $index => $category) {
            // Assign each category a unique gradient by using the index
            $category['gradient'] = $gradients[$index];
            $category['slug'] = Str::slug($category['name']);
            Podcategory::create($category);
        }
    }
}
