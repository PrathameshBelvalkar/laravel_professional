<?php

namespace Database\Seeders;

use App\Models\App;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AiAppSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apps = [
            [
                "title" => "AI Writer",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/writer.png",
                "url" => config("app.ai_url") . "/user/templates",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "AI Article Wizard",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/article_wizard.png",
                "url" => config("app.ai_url") . "/user/wizard",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "AI Rewriter",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/rewriter.png",
                "url" => config("app.ai_url") . "/user/rewriter",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "Smart Editor",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/smart-editor.png",
                "url" => config("app.ai_url") . "/user/smart-editor",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "AI Images",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/images.png",
                "url" => config("app.ai_url") . "/",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "AI Voiceover",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/voice-over.png",
                "url" => config("app.ai_url") . "/user/dashboard#",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "AI Speech to Text",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/speech-to-text.png",
                "url" => config("app.ai_url") . "/user/speech-to-text",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "AI Vision",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/vision.png",
                "url" => config("app.ai_url") . "/user/vision",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "AI Chat Images",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/chat-image.png",
                "url" => config("app.ai_url") . "/user/chat/image",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "AI Chat",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/chat.png",
                "url" => config("app.ai_url") . "/user/chat",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "AI File Chat",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/fileChat.png",
                "url" => config("app.ai_url") . "/user/chat/file",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "AI Code",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/code.png",
                "url" => config("app.ai_url") . "/user/code",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "Brand Voice",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/brand-voice.png",
                "url" => config("app.ai_url") . "/user/brand",
                "is_frontend_app" => "1"
            ],
            [
                "title" => "AI Web Chat",
                "tabs_category" => "6",
                "category" => "AI",
                "rating" => "4.5",
                "free" => "0",
                "image" => "/logo/apps/web-chat.png",
                "url" => config("app.ai_url") . "/user/brand",
                "is_frontend_app" => "1"
            ],
        ];
        foreach ($apps as $app) {
            $appExists = App::where("title", $app['title'])->count();
            if (!$appExists)
                App::create($app);
        }
    }
}
