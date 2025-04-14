<?php

namespace Database\Seeders;

use App\Models\SupportCategory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class SupportCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $support_tags = [
            [
                "id" => 1,
                'title' => 'Account',
                'description' => 'Account settings, managing users ',
                "category_key" => "account",
                'tags' => json_encode(['Feedback', 'reCAPTCHA', 'request']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                "id" => 2,
                'title' => 'Bill and Payments',
                'description' => 'Billing information and payments',
                "category_key" => "bill _and_payments",
                'tags' => json_encode(['Feedback', 'reCAPTCHA', 'request']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                "id" => 3,
                'title' => 'General information',
                'description' => 'General information and issues',
                "category_key" => "general_information",
                'tags' => json_encode(['Feedback', 'reCAPTCHA', 'request']),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                "id" => 4,
                'title' => 'Other',
                'description' => 'Other Issues',
                "category_key" => "other",
                'tags' => json_encode(['Feedback', 'reCAPTCHA', 'request']),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];
        foreach ($support_tags as $tag) {
            SupportCategory::create($tag);
        }
    }
}

