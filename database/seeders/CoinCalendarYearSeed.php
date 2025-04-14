<?php

namespace Database\Seeders;

use App\Models\coin\CoinCalendarYear;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CoinCalendarYearSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $calendar_years = [
            [
                'id' => 1,
                'start_year' => '2023',
                'start_month' => '10',
                'end_year' => '2024',
                'end_month' => '9',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' =>2,
                'start_year' => '2024',
                'start_month' => '10',
                'end_year' => '2025',
                'end_month' => '9',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'start_year' => '2025',
                'start_month' => '10',
                'end_year' => '2026',
                'end_month' => '9',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'start_year' => '2026',
                'start_month' => '10',
                'end_year' => '2027',
                'end_month' => '9',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'start_year' => '2027',
                'start_month' => '10',
                'end_year' => '2028',
                'end_month' => '9',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'start_year' => '2028',
                'start_month' => '10',
                'end_year' => '2029',
                'end_month' => '9',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'start_year' => '2029',
                'start_month' => '10',
                'end_year' => '2030',
                'end_month' => '9',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'start_year' => '2030',
                'start_month' => '10',
                'end_year' => '2031',
                'end_month' => '9',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'start_year' => '2031',
                'start_month' => '10',
                'end_year' => '2032',
                'end_month' => '9',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'start_year' => '2032',
                'start_month' => '10',
                'end_year' => '2033',
                'end_month' => '9',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
        ];
        foreach ($calendar_years as $calendar_year) {
            CoinCalendarYear::create($calendar_year);
        }
    }
}
