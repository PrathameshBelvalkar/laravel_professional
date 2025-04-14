<?php

namespace Database\Seeders\Mail;

use App\Models\Mail\EmailLabel;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmailLabelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emailLabelsData = [
            [
                'id' => 1,
                'user_id' => NULL,
                'labels' => 'Personal',
                'theme' => 'primary',
            ],
            [
                'id' => 2,
                'user_id' => NULL,
                'labels' => 'Business',
                'theme' => 'success',
            ],
            [
                'id' => 3,
                'user_id' => NULL,
                'labels' => 'Feedback',
                'theme' => 'blue',
            ],
            [
                'id' => 4,
                'user_id' => NULL,
                'labels' => 'Events',
                'theme' => 'warning',
            ],
        ];
        foreach ($emailLabelsData as $label) {
            EmailLabel::create($label);
        }
    
    }
}
