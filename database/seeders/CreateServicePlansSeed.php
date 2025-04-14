<?php

namespace Database\Seeders;

use App\Models\Subscription\ServicePlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateServicePlansSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('service_plans')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $service_plans = [
            [
                'name' => 'Bronze',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "1",
            ],
            [
                'name' => 'Silver',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "1",
            ],
            [
                'name' => 'Gold',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "1",
            ],
            [
                'name' => 'Bronze',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "2",
            ],
            [
                'name' => 'Silver',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "2",
            ],
            [
                'name' => 'Gold',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "2",
            ],
            [
                'name' => 'Bronze',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "3",
            ],
            [
                'name' => 'Silver',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "3",
            ],
            [
                'name' => 'Gold',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "3",
            ],
            [
                'name' => 'Bronze',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "4",
            ],
            [
                'name' => 'Silver',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "4",
            ],
            [
                'name' => 'Gold',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "4",
            ],
            [
                'name' => 'Bronze',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "5",
            ],
            [
                'name' => 'Silver',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "5",
            ],
            [
                'name' => 'Gold',
                'monthly_price' => 30,
                'quarterly_price' => 60,
                'yearly_price' => 120,
                'status' => "1",
                'service_id' => "5",
            ],
            [
                'name' => 'Bronze',
                'monthly_price' => 2,
                'quarterly_price' => 5,
                'yearly_price' => 19,
                'status' => "1",
                'service_id' => "6",
            ],
            [
                'name' => 'Silver',
                'monthly_price' => 5,
                'quarterly_price' => 13,
                'yearly_price' => 49,
                'status' => "1",
                'service_id' => "6",
            ],
            [
                'name' => 'Gold',
                'monthly_price' => 7,
                'quarterly_price' => 19,
                'yearly_price' => 69,
                'status' => "1",
                'service_id' => "6",
            ],
            [
                'name' => 'Bronze',
                'features' => json_encode([
                    "value" => 10,
                    "text" => "Upto 10 + free(" . config("app.free_flipbook_sell_count") . ") Flipbooks you can sell"
                ]),
                'monthly_price' => 2,
                'quarterly_price' => 5,
                'yearly_price' => 19,
                'status' => "1",
                'service_id' => "8",
            ],
            [
                'name' => 'Silver',
                'features' => json_encode([
                    "value" => 20,
                    "text" => "Upto 20 + free(" . config("app.free_flipbook_sell_count") . ") Flipbooks you can sell"
                ]),
                'monthly_price' => 5,
                'quarterly_price' => 13,
                'yearly_price' => 49,
                'status' => "1",
                'service_id' => "8",
            ],
            [
                'name' => 'Gold',
                'features' => json_encode([
                    "value" => 50,
                    "text" => "Upto 50 + free(" . config("app.free_flipbook_sell_count") . ") Flipbooks you can sell"
                ]),
                'monthly_price' => 7,
                'quarterly_price' => 19,
                'yearly_price' => 69,
                'status' => "1",
                'service_id' => "8",
            ],
        ];

        foreach ($service_plans as $plan) {
            ServicePlan::create($plan);
        }
    }
}
