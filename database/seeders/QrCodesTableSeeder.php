<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QrCodesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Set the number of records to insert
        $numRecords = 100;

        // Loop to insert multiple records
        for ($i = 0; $i < $numRecords; $i++) {
            DB::table('qr_codes')->insert([
                'user_id' => 13731,
                'qrcode_id' => Str::random(10),
                'qr_name' => 'QR Name ' . ($i + 1),
                'qrcode_data' => 'Sample Data ' . ($i + 1),
                'qrcode_type' => 'URL',
                'file_path' => 'users/private/13731/qrcodes/URL_66c5ec885ca48_1724247176.png',
                'pdf_path' => NULL,
                'file_key' => NULL,
                'qrscan_type' => '0',
                'scans' => 0,
                'product_price' => NULL,
                'product_stock' => NULL,
                'deleted_at' => NULL,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
