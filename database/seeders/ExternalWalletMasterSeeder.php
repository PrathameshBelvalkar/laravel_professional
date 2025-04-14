<?php

namespace Database\Seeders;

use App\Models\Wallet\ExternalWalletMaster;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExternalWalletMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('external_wallet_masters')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $wallets = [
            [
                'name' => "BNB",
                'shortname' => "BNB",
                'status' => "1",
                'logo' => "logo/external-wallets/bnb.png",
            ],
            [
                'name' => "LIB",
                'shortname' => "LIB",
                'status' => "1",
                'logo' => "logo/external-wallets/lib.png",
            ],
            [
                'name' => "TRX",
                'shortname' => "TRX",
                'status' => "1",
                'logo' => "logo/external-wallets/trx.png",
            ],
            [
                'name' => "BTC",
                'shortname' => "BTC",
                'status' => "1",
                'logo' => "logo/external-wallets/btc.png",
            ]
        ];
        foreach ($wallets as $data) {
            ExternalWalletMaster::create($data);
        }
    }
}
