<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $sections = DB::table('app_sections')->pluck('id', 'name');



        $apps = [
            // Core Apps
            ['name' => 'Connect', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/connect.png'), 'project_link' => 'https://connect.silocloud.io', 'dark_image_link' => null],
            ['name' => 'Storage', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/storage.png'), 'project_link' => 'https://storage.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'VMail', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/mail.png'), 'project_link' => 'https://mail.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'QR Generator', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/qr.png'), 'project_link' => 'https://qr.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'TV', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/tv-logo.png'), 'project_link' => 'https://tv.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'Streamdeck', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/streamdeck.png'), 'project_link' => 'https://streamdeck.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'SiteBuilder', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/site-builder.png'), 'project_link' => 'https://site.silocloud.com/', 'dark_image_link' => null],
            ['name' => 'Calendar', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/calender.png'), 'project_link' => 'https://calendar.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'Community', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/community.png'), 'project_link' => 'https://community.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'Marketplace', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/marketplace.png'), 'project_link' => 'https://store.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'Influencers', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/Influencer.png'), 'project_link' => 'https://influencer.silocloud.io/', 'dark_image_link' => null],
            ['name' => '3D Viewer', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/3d-viewer.png'), 'project_link' => 'https://3d.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'Publisher', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/publisher.png'), 'project_link' => 'https://publisher.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'Talk', 'section_id' => $sections['Core Apps'], 'image_link' => url('/logo/apps/talk.png'), 'project_link' => 'https://silotalk.com/login', 'dark_image_link' => null],
            // Social Apps
            ['name' => 'Persona<br>Digest', 'section_id' => $sections['Social Apps'], 'image_link' => url('/logo/apps/persona-digest.png'), 'project_link' => 'https://personadigest.com/', 'dark_image_link' => null],
            ['name' => 'Persona<br>Radio', 'section_id' => $sections['Social Apps'], 'image_link' => url('/logo/apps/persona-radio.png'), 'project_link' => 'https://personaradio.com/', 'dark_image_link' => null],
            ['name' => 'Persona<br>Post', 'section_id' => $sections['Social Apps'], 'image_link' => url('/logo/apps/persona-post.png'), 'project_link' => 'https://personapost.com/', 'dark_image_link' => null],
            ['name' => 'Persona<br>OS', 'section_id' => $sections['Social Apps'], 'image_link' => url('/logo/apps/persona-os.png'), 'project_link' => 'https://personaos.com/', 'dark_image_link' => null],
            // Productivity Apps
            ['name' => 'ERP', 'section_id' => $sections['Productivity Apps'], 'image_link' => url('/logo/apps/erp.png'), 'project_link' => 'https://siloerp.com/', 'dark_image_link' => null],
            ['name' => 'Suite', 'section_id' => $sections['Productivity Apps'], 'image_link' => url('/logo/apps/suite.png'), 'project_link' => 'https://silosuite.com/', 'dark_image_link' => null],
            ['name' => 'Constructor Tool', 'section_id' => $sections['Productivity Apps'], 'image_link' => url('/logo/apps/constructor-tool.png'), 'project_link' => 'https://silocloud.com/silo-constructor', 'dark_image_link' => null],
            ['name' => 'Assembler', 'section_id' => $sections['Productivity Apps'], 'image_link' => url('/logo/apps/assembler.png'), 'project_link' => '/', 'dark_image_link' => null],
            ['name' => 'Canvas', 'section_id' => $sections['Productivity Apps'], 'image_link' => url('/logo/apps/canvas.png'), 'project_link' => 'https://canvas.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'Maps', 'section_id' => $sections['Productivity Apps'], 'image_link' => url('/logo/apps/maps.png'), 'project_link' => 'https://mapbuilder.silocloud.com/', 'dark_image_link' => null],
            ['name' => 'SYM', 'section_id' => $sections['Productivity Apps'], 'image_link' => url('/logo/apps/ai.png'), 'project_link' => 'https://ai.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'Podcast', 'section_id' => $sections['Productivity Apps'], 'image_link' => url('/logo/apps/podcast.png'), 'project_link' => 'https://podcast.silocloud.io/', 'dark_image_link' => null],
            // Exchange Apps
            ['name' => 'Wallet', 'section_id' => $sections['Exchange Apps'], 'image_link' => url('/logo/apps/wallet.png'), 'project_link' => 'https://wallet.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'Blockchain', 'section_id' => $sections['Exchange Apps'], 'image_link' => url('/logo/apps/blockchain.png'), 'project_link' => 'https://silocloud.com/enumblockchain-explorer', 'dark_image_link' => null],
            ['name' => 'Coin Exchange', 'section_id' => $sections['Exchange Apps'], 'image_link' => url('/logo/apps/coin_exchange.png'), 'project_link' => 'https://coin.silocloud.io/', 'dark_image_link' => null],
            ['name' => 'Merchant', 'section_id' => $sections['Exchange Apps'], 'image_link' => url('/logo/apps/merchant-white.png'), 'project_link' => 'https://silomerchants.com/', 'dark_image_link' => url('/logo/apps/merchant-dark.png')],
            ['name' => 'Bank', 'section_id' => $sections['Exchange Apps'], 'image_link' => url('/logo/apps/bank.png'), 'project_link' => 'https://silobank.com/', 'dark_image_link' => null],
        ];


        DB::table('silo_apps')->insert($apps);
    }
}
