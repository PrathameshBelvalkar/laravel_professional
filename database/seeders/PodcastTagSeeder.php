<?php

namespace Database\Seeders;

use App\Models\Podcast\Podtag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PodcastTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $tags = [
            // Comedy (category_id = 1)
            [
                'name' => 'Stand-up Comedy',
                'category_id' => 1,
            ],
            [
                'name' => 'Improv Comedy',
                'category_id' => 1,
            ],
            [
                'name' => 'Sketch Comedy',
                'category_id' => 1,
            ],
            [
                'name' => 'Satire & Parody',
                'category_id' => 1,
            ],
            [
                'name' => 'Pop Culture Humor',
                'category_id' => 1,
            ],

            // News & Politics (category_id = 2)
            [
                'name' => 'Current Events',
                'category_id' => 2,
            ],
            [
                'name' => 'In-depth Analysis',
                'category_id' => 2,
            ],
            [
                'name' => 'Political Commentary',
                'category_id' => 2,
            ],
            [
                'name' => 'International News',
                'category_id' => 2,
            ],
            [
                'name' => 'Investigative Journalism',
                'category_id' => 2,
            ],

            // True Crime (category_id = 3)
            [
                'name' => 'Unsolved Mysteries',
                'category_id' => 3,
            ],
            [
                'name' => 'Serial Killers',
                'category_id' => 3,
            ],
            [
                'name' => 'Cold Cases',
                'category_id' => 3,
            ],
            [
                'name' => 'True Crime History',
                'category_id' => 3,
            ],
            [
                'name' => 'Disappearances',
                'category_id' => 3,
            ],

            // Business (category_id = 4)
            [
                'name' => 'Entrepreneurship',
                'category_id' => 4,
            ],
            [
                'name' => 'Marketing & Sales',
                'category_id' => 4,
            ],
            [
                'name' => 'Finance & Investing',
                'category_id' => 4,
            ],
            [
                'name' => 'Leadership & Management',
                'category_id' => 4,
            ],
            [
                'name' => 'Career Development',
                'category_id' => 4,
            ],

            // Education (category_id = 5)
            [
                'name' => 'Science & Technology',
                'category_id' => 5,
            ],
            [
                'name' => 'History & Culture',
                'category_id' => 5,
            ],
            [
                'name' => 'Literature & Language',
                'category_id' => 5,
            ],
            [
                'name' => 'Philosophy & Religion',
                'category_id' => 5,
            ],
            [
                'name' => 'Personal Development',
                'category_id' => 5,
            ],

            // Health & Wellness (category_id = 6)
            [
                'name' => 'Mental Health',
                'category_id' => 6,
            ],
            [
                'name' => 'Physical Fitness',
                'category_id' => 6,
            ],
            [
                'name' => 'Nutrition',
                'category_id' => 6,
            ],
            [
                'name' => 'Self-Care',
                'category_id' => 6,
            ],
            [
                'name' => 'Wellness Trends',
                'category_id' => 6,
            ],

            // Technology (category_id = 7)
            [
                'name' => 'AI & Machine Learning',
                'category_id' => 7,
            ],
            [
                'name' => 'Cybersecurity',
                'category_id' => 7,
            ],
            [
                'name' => 'Blockchain',
                'category_id' => 7,
            ],
            [
                'name' => 'Software Development',
                'category_id' => 7,
            ],
            [
                'name' => 'Gadgets & Devices',
                'category_id' => 7,
            ],

            // Science (category_id = 8)
            [
                'name' => 'Astronomy',
                'category_id' => 8,
            ],
            [
                'name' => 'Physics',
                'category_id' => 8,
            ],
            [
                'name' => 'Biology',
                'category_id' => 8,
            ],
            [
                'name' => 'Earth Science',
                'category_id' => 8,
            ],
            [
                'name' => 'Chemistry',
                'category_id' => 8,
            ],

            // Sports (category_id = 9)
            [
                'name' => 'Football',
                'category_id' => 9,
            ],
            [
                'name' => 'Basketball',
                'category_id' => 9,
            ],
            [
                'name' => 'Baseball',
                'category_id' => 9,
            ],
            [
                'name' => 'Tennis',
                'category_id' => 9,
            ],
            [
                'name' => 'Olympic Sports',
                'category_id' => 9,
            ],

            // History (category_id = 10)
            [
                'name' => 'Ancient Civilizations',
                'category_id' => 10,
            ],
            [
                'name' => 'World Wars',
                'category_id' => 10,
            ],
            [
                'name' => 'Medieval History',
                'category_id' => 10,
            ],
            [
                'name' => 'Revolutionary History',
                'category_id' => 10,
            ],
            [
                'name' => 'Colonialism',
                'category_id' => 10,
            ],

            // Music (category_id = 11)
            [
                'name' => 'Classical Music',
                'category_id' => 11,
            ],
            [
                'name' => 'Pop Music',
                'category_id' => 11,
            ],
            [
                'name' => 'Hip-Hop',
                'category_id' => 11,
            ],
            [
                'name' => 'Rock & Roll',
                'category_id' => 11,
            ],
            [
                'name' => 'Electronic Dance Music',
                'category_id' => 11,
            ],
            [
                'name' => 'Visual Arts',
                'category_id' => 12,
            ],
            [
                'name' => 'Performing Arts',
                'category_id' => 12,
            ],
            [
                'name' => 'Literary Arts',
                'category_id' => 12,
            ],
            [
                'name' => 'Art History',
                'category_id' => 12,
            ],
            [
                'name' => 'Cultural Heritage',
                'category_id' => 12,
            ],

            // Society & Culture (category_id = 13)
            [
                'name' => 'Social Issues',
                'category_id' => 13,
            ],
            [
                'name' => 'Cultural Trends',
                'category_id' => 13,
            ],
            [
                'name' => 'Diversity & Inclusion',
                'category_id' => 13,
            ],
            [
                'name' => 'Global Cultures',
                'category_id' => 13,
            ],
            [
                'name' => 'Human Rights',
                'category_id' => 13,
            ],

            // Religion & Spirituality (category_id = 14)
            [
                'name' => 'Christianity',
                'category_id' => 14,
            ],
            [
                'name' => 'Buddhism',
                'category_id' => 14,
            ],
            [
                'name' => 'Islam',
                'category_id' => 14,
            ],
            [
                'name' => 'Hinduism',
                'category_id' => 14,
            ],
            [
                'name' => 'Spiritual Awakening',
                'category_id' => 14,
            ],

            // Kids & Family (category_id = 15)
            [
                'name' => 'Parenting Tips',
                'category_id' => 15,
            ],
            [
                'name' => 'Children’s Stories',
                'category_id' => 15,
            ],
            [
                'name' => 'Family Activities',
                'category_id' => 15,
            ],
            [
                'name' => 'Child Development',
                'category_id' => 15,
            ],
            [
                'name' => 'Educational Games',
                'category_id' => 15,
            ],

            // Food & Drink (category_id = 16)
            [
                'name' => 'Cooking Tips',
                'category_id' => 16,
            ],
            [
                'name' => 'Food Culture',
                'category_id' => 16,
            ],
            [
                'name' => 'Recipes',
                'category_id' => 16,
            ],
            [
                'name' => 'Wine & Spirits',
                'category_id' => 16,
            ],
            [
                'name' => 'Food Trends',
                'category_id' => 16,
            ],

            // Personal Journals (category_id = 17)
            [
                'name' => 'Life Stories',
                'category_id' => 17,
            ],
            [
                'name' => 'Daily Reflections',
                'category_id' => 17,
            ],
            [
                'name' => 'Self-Improvement',
                'category_id' => 17,
            ],
            [
                'name' => 'Personal Experiences',
                'category_id' => 17,
            ],
            [
                'name' => 'Mindfulness Journals',
                'category_id' => 17,
            ],

            // Fiction (category_id = 18)
            [
                'name' => 'Science Fiction',
                'category_id' => 18,
            ],
            [
                'name' => 'Fantasy',
                'category_id' => 18,
            ],
            [
                'name' => 'Horror Fiction',
                'category_id' => 18,
            ],
            [
                'name' => 'Mystery & Thriller',
                'category_id' => 18,
            ],
            [
                'name' => 'Romantic Fiction',
                'category_id' => 18,
            ],

            // TV & Film (category_id = 19)
            [
                'name' => 'Film Reviews',
                'category_id' => 19,
            ],
            [
                'name' => 'TV Show Analysis',
                'category_id' => 19,
            ],
            [
                'name' => 'Behind the Scenes',
                'category_id' => 19,
            ],
            [
                'name' => 'Celebrity Interviews',
                'category_id' => 19,
            ],
            [
                'name' => 'Movie History',
                'category_id' => 19,
            ],

            // Gaming (category_id = 20)
            [
                'name' => 'Video Game Reviews',
                'category_id' => 20,
            ],
            [
                'name' => 'Esports',
                'category_id' => 20,
            ],
            [
                'name' => 'Game Development',
                'category_id' => 20,
            ],
            [
                'name' => 'Board Games',
                'category_id' => 20,
            ],
            [
                'name' => 'Role-Playing Games',
                'category_id' => 20,
            ],

        ];

        foreach ($tags as $tag) {
            $slug = Str::slug($tag['name'], '-');

            // Create the tag with the category_id
            Podtag::create([
                'name' => $tag['name'],
                'slug' => $slug,
                'category_id' => $tag['category_id'],
            ]);
        }
    }
}
