<?php

namespace Database\Seeders;

use App\Models\App;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AppSeed extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $apps = [
      [
        "title" => "Silo Storage",
        "tabs_category" => "1",
        "category" => "Utilize & tool",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-storage.png",
        "url" => "https://storage.silocloud.io/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo Talk",
        "tabs_category" => "1",
        "category" => "Social",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-talk.png",
        "url" => "https://silotalk.silocloud.io/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo TV",
        "tabs_category" => "1",
        "category" => "Entertainment",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-tv.png",
        "url" => "https://tv.silocloud.io/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo QR",
        "tabs_category" => "1",
        "category" => "Multimedia & design",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-qr.png",
        "url" => "https://qr.silocloud.io/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo Mail",
        "tabs_category" => "1",
        "category" => "Productivity",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-mail.png",
        "url" => "https://silocloud.com/silo-email",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo Calendar",
        "tabs_category" => "1",
        "category" => "Planner & Reminder",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-calender.png",
        "url" => "https://calendar.silocloud.io/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo Streamdeck",
        "tabs_category" => "1",
        "category" => "Multimedia & design",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-streamdeck.png",
        "url" => "https://streamdeck.silocloud.io/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo Marketplace",
        "tabs_category" => "1",
        "category" => "Shopping",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-marketplace.png",
        "url" => "https://store.silocloud.io/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo Support",
        "tabs_category" => "1",
        "category" => "Utilize & tool",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-support.png",
        "url" => "https://support.silocloud.io/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo Games",
        "tabs_category" => "1",
        "category" => "Sports",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-games.png",
        "url" => "https://games.silocloud.io/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo Social",
        "tabs_category" => "2",
        "category" => "Social",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-socials.png",
        "url" => "https://personaos.com/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Persona Post",
        "tabs_category" => "2",
        "category" => "Social",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/persona-post.png",
        "url" => "http://personapost.com/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Persona Radio",
        "tabs_category" => "2",
        "category" => "Entertainment",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/persona-radio.png",
        "url" => "https://personaradio.com/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Persona Digest",
        "tabs_category" => "2",
        "category" => "Social",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/persona-digest.png",
        "url" => "http://personadigest.com/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "HBCU Post",
        "tabs_category" => "2",
        "category" => "Social",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/hbcu-post.png",
        "url" => "https://hbcupost.com/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Preneur",
        "tabs_category" => "2",
        "category" => "Social",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/preneur.png",
        "url" => "https://preneur.ai/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Flow",
        "tabs_category" => "2",
        "category" => "Social",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/flow.png",
        "url" => "https://therealflow.com/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "IVIPP",
        "tabs_category" => "2",
        "category" => "Social",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/ivipp.png",
        "url" => "https://ivipp.com/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo Streams",
        "tabs_category" => "3",
        "category" => "Streaming",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-streams.png",
        "url" => "https://silocloud.com/silo-streaming",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "HBCU TV",
        "tabs_category" => "3",
        "category" => "Streaming",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/hbcu-tv.png",
        "url" => "https://www.hbculeaguepass.com/conference-epg",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "iWoman TV",
        "tabs_category" => "3",
        "category" => "Streaming",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/iwoman-tv.png",
        "url" => "https://www.iwoman.tv/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo Merchants",
        "tabs_category" => "4",
        "category" => "Finance",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-merchants.png",
        "url" => "https://silomerchants.com/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo Gateway",
        "tabs_category" => "4",
        "category" => "Finance",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-gateway.png",
        "url" => "https://silogateway.com/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo CoinExchange",
        "tabs_category" => "4",
        "category" => "Finance",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/coin_exchange.png",
        "url" => "https://coin.silocloud.io/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Crypto Derby",
        "tabs_category" => "5",
        "category" => "Utilities & tool",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/crypto-derby.png",
        "url" => "https://cryptoderby.org/",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Constructor Tool",
        "tabs_category" => "5",
        "category" => "Utilities & tool",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/constructor-tool.png",
        "url" => "https://silocloud.com/silo-constructor-download",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Silo Assembler",
        "tabs_category" => "5",
        "category" => "Utilities & tool",
        "rating" => "4.5",
        "free" => "0",
        "image" => "assets/images/silo_apps/silo-assembler.png",
        "url" => "https://silocloud.com/silo-wallet",
        "is_frontend_app" => "1"
      ],
      [
        "title" => "Dive into Delight!",
        "sub_title" => "Your ultimate hub for movie, TV, shows, music, and games, all in one place!",
        "image" => "assets/images/banners/banner-1.png",
        "is_frontend_banner" => "1"
      ],
      [
        "title" => "Your Custom QR Code Maker",
        "sub_title" => "Unlock Information Instantly",
        "image" => "assets/images/banners/banner-2.png",
        "is_frontend_banner" => "1"
      ],
      [
        "title" => "Endless Choices, Easy Shopping",
        "sub_title" => "Discover a world of products at unbeatable prices. Shop Smart and enjoy seamless, secure transactions with us!",
        "image" => "assets/images/banners/banner-3.png",
        "is_frontend_banner" => "1"
      ],
      [
        "title" => "Personal Connection and Secure Data",
        "sub_title" => "Simplify your Life with Smart Solutions",
        "image" => "assets/images/banners/banner-4.png",
        "is_frontend_banner" => "1"
      ],
      [
        "title" => "Derby Race",
        "sub_title" => "Experience the Thrill of the Track",
        "image" => "assets/images/banners/banner-5.png",
        "is_frontend_banner" => "1"
      ],
      [
        "title" => "SiloTalk: Connect, Share, Store",
        "sub_title" => "Seamless Chats, Limitless Sharing, Secure Storage",
        "image" => "assets/images/banners/banner-6.png",
        "is_frontend_banner" => "1"
      ],
      [
        "title" => "Silo Assembler",
        "sub_title" => "Effortlessly create robust steel structures with our cutting-edge silo assembler tool, designed for accuracy, efficiency, and durability in every project",
        "image" => "assets/images/banners/banner-9.jpg",
        "is_frontend_banner" => "1"
      ],
      [
        "title" => "Cloudstorage",
        "sub_title" => "Safeguard Your Data, Expand Your Space",
        "image" => "assets/images/banners/banner-8.jpg",
        "is_frontend_banner" => "1"
      ],
      [
        "title" => "StreamDeck: Your Ultimate Entertainment Hub",
        "sub_title" => "Watch movies and series, upload your own videos, and go live-all in one seamless platform",
        "image" => "assets/images/banners/banner-7.jpg",
        "is_frontend_banner" => "1"
      ]
    ];

    foreach ($apps as $app) {
      App::create($app);
    }
  }
}
