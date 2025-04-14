<?php

namespace Database\Seeders;

use App\Models\AppDetails\AppCategories;
use App\Models\AppDetails\SiloApp;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AppCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories=[
            [
                'app_id'=>1,
                'app_name'=>'Connect',
                'category'=>'Social'
            ],
            [
                'app_id'=>2,
                'app_name'=>'Storage',
                'category'=>'Utilize & tool'
            ],
            [
                'app_id'=>3,
                'app_name'=>'Mail',
                'category'=>'Productivity'
            ],
            [
                'app_id'=>4,
                'app_name'=>'QR Generator',
                'category'=>'Multimedia & design'
            ],
            [
                'app_id'=>5,
                'app_name'=>'TV',
                'category'=>'Entertainment'
            ],
            [
                'app_id'=>6,
                'app_name'=>'Streamdeck',
                'category'=>'Multimedia & design'
            ],
            [
                'app_id'=>7,
                'app_name'=>'SiteBuilder',
                'category'=>'Utilities & tool'
            ],
            [
                'app_id'=>8,
                'app_name'=>'Calendar',
                'category'=>'Planner & Reminder'
            ],
            [
                'app_id'=>9,
                'app_name'=>'Community',
                'category'=>'Social'
            ],
            [
                'app_id'=>10,
                'app_name'=>'Marketplace',
                'category'=>'Shopping'
            ],
            [
                'app_id'=>11,
                'app_name'=>'3D Viewer',
                'category'=>'Utilize & tool'
            ],
            [
                'app_id'=>12,
                'app_name'=>'Publisher',
                'category'=>'Utilize & tool'
            ],
            [
                'app_id'=>13,
                'app_name'=>'Talk',
                'category'=>'Social'
            ],
            [
                'app_id'=>14,
                'app_name'=>'Persona<br>Digest',
                'category'=>'Social'
            ],
            [
                'app_id'=>15,
                'app_name'=>'Persona<br>Radio',
                'category'=>'Entertainment'
            ],
            [
                'app_id'=>16,
                'app_name'=>'Persona<br>Post',
                'category'=>'Social'
            ],
            [
                'app_id'=>17,
                'app_name'=>'Persona<br>OS',
                'category'=>'Social'
            ],
            [
                'app_id'=>18,
                'app_name'=>'ERP',
                'category'=>'Productivity'
            ],
            [
                'app_id'=>19,
                'app_name'=>'Suite',
                'category'=>'Productivity'
            ],
            [
                'app_id'=>20,
                'app_name'=>'Constructor Tool',
                'category'=>'Utilities & tool'
            ],
            [
                'app_id'=>21,
                'app_name'=>'Assembler',
                'category'=>'Utilities & tool'
            ],
            [
                'app_id'=>22,
                'app_name'=>'Canvas',
                'category'=>'Utilities & tool'
            ],
            [
                'app_id'=>23,
                'app_name'=>'Maps',
                'category'=>'Utilities & tool'
            ],
            [
                'app_id'=>24,
                'app_name'=>'SYM',
                'category'=>'AI'
            ],
            [
                'app_id'=>25,
                'app_name'=>'Podcast',
                'category'=>'Utilize & tool'
            ],
            [
                'app_id'=>26,
                'app_name'=>'Wallet',
                'category'=>'Finance'
            ],
            [
                'app_id'=>27,
                'app_name'=>'Blockchain',
                'category'=>'Finance'
            ],
            [
                'app_id'=>28,
                'app_name'=>'Coin Exchange',
                'category'=>'Finance'
            ],
            [
                'app_id'=>29,
                'app_name'=>'Merchant',
                'category'=>'Finance'
            ],
            [
                'app_id'=>30,
                'app_name'=>'Bank',
                'category'=>'Finance'
            ],
            [
                'app_id'=>31,
                'app_name'=>'Persona<br>News',
                'category'=>'Social'
            ],

        ];


        foreach ($categories as $category) {
            SiloApp::where('name', $category['app_name'])->update([
                'category' => $category['category'],
            ]);
        }
    }
}
