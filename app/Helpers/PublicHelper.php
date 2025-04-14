<?php

use App\Models\User;
use App\Models\UserProfile;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('getDefaultFrontendSettings')) {
    function getDefaultFrontendSettings($key = "", $encodeApps = false)
    {
        $appsArray = [
            [
                "showIcon" => false,
                "link" => "https://silocloud.io/apps/",
                "popOver" => false,
                "module_no" => null,
                "useNavigate" => false,
                "imgSrc" => "logo/apps/store.png",
                "title" => "Silo Apps",
                'slug' => 'silo_apps',
                'key' => null,
                'footer_visibility' => '1',
                "sequence_no" => "1"
            ],
            [
                "showIcon" => false,
                "link" => "https://streamdeck.silocloud.io",
                "popOver" => false,
                "module_no" => "4",
                "useNavigate" => false,
                "imgSrc" => "logo/apps/streamdeck.png",
                "title" => "Streamdeck",
                'slug' => 'streamdeck',
                'key' => '4',
                'footer_visibility' => '1',
                "sequence_no" => "2"
            ],
            [
                "showIcon" => false,
                "link" => "https://tv.silocloud.io",
                "popOver" => false,
                "module_no" => "12",
                "useNavigate" => false,
                "imgSrc" => "logo/apps/tv-logo.png",
                "title" => "TV",
                'slug' => 'tv',
                'key' => '12',
                'footer_visibility' => '1',
                "sequence_no" => "3"
            ],
            [
                "showIcon" => false,
                "link" => "https://silotalk.com",
                "popOver" => false,
                "module_no" => "11",
                "useNavigate" => false,
                "imgSrc" => "logo/apps/talk.png",
                "title" => "Talk",
                'slug' => 'talk',
                'key' => '11',
                'footer_visibility' => '2',
                "sequence_no" => "4"
            ],
            [
                "showIcon" => false,
                "link" => "https://storage.silocloud.io",
                "popOver" => false,
                "module_no" => "7",
                "useNavigate" => false,
                "imgSrc" => "logo/apps/storage.png",
                "title" => "Storage",
                'slug' => 'storage',
                'key' => '7',
                'footer_visibility' => '1',
                "sequence_no" => "5"
            ],
            [
                "showIcon" => true,
                "link" => "https://mail.silocloud.io/",
                "popOver" => true,
                "module_no" => "9",
                "useNavigate" => false,
                "imgSrc" => "logo/apps/mail.png",
                "title" => "VMail",
                'slug' => 'vmail',
                'key' => '9',
                'footer_visibility' => '2',
                "sequence_no" => "6"
            ],
            [

                "showIcon" => true,
                "link" => "https://qr.silocloud.io",
                "popOver" => true,
                "module_no" => "2",
                "useNavigate" => false,
                "imgSrc" => "logo/apps/qr.png",
                "title" => "QR",
                'key' => '2',
                'slug' => 'qr',
                'footer_visibility' => '2',
                "sequence_no" => "7"
            ],
            [
                "showIcon" => true,
                "link" => "https://store.silocloud.io/",
                "popOver" => true,
                "module_no" => "5",
                "useNavigate" => false,
                "imgSrc" => "logo/apps/marketplace.png",
                "title" => "Store",
                'slug' => 'store',
                'key' => '5',
                'footer_visibility' => '2',
                "sequence_no" => "8"
            ],
            [
                "showIcon" => true,
                "link" => "https://site.silocloud.com/",
                "popOver" => true,
                "module_no" => "17",
                "useNavigate" => false,
                "imgSrc" => "logo/apps/site-builder.png",
                "title" => "Site Builder",
                'key' => '17',
                'slug' => 'site_builder',
                'footer_visibility' => '2',
                "sequence_no" => "9"
            ],
            [
                "showIcon" => true,
                "link" => "https://calendar.silocloud.io/",
                "popOver" => true,
                "module_no" => "6",
                "useNavigate" => false,
                "imgSrc" => "logo/apps/calender.png",
                "title" => "Calendar",
                'slug' => 'calendar',
                'key' => '6',
                'footer_visibility' => '2',
                "sequence_no" => "10"
            ],
            [
                "showIcon" => true,
                "link" => 'https://community.silocloud.io/',
                "popOver" => true,
                "module_no" => "18",
                "useNavigate" => false,
                "imgSrc" => "logo/apps/community.png",
                "title" => "Community",
                "slug" => "community",
                "key" => "18",
                "footer_visibility" => "2",
                "sequence_no" => "11",
            ],
            [
                "showIcon" => true,
                "useNavigate" => false,
                "link" => "https://publisher.silocloud.io/",
                "popOver" => true,
                "module_no" => "16",
                "imgSrc" => "logo/apps/publisher.png",
                "title" => "Publisher",
                'slug' => 'publisher',
                'key' => '16',
                'footer_visibility' => '2',
                "sequence_no" => "12"
            ],
            [
                "showIcon" => true,
                "link" => "https://3d.silocloud.io/",
                "popOver" => true,
                "module_no" => "15",
                "useNavigate" => false,
                "imgSrc" => "logo/apps/3d-viewer.png",
                "title" => "3D Viewer",
                'slug' => '3d-viewer',
                'key' => '15',
                'footer_visibility' => '2',
                "sequence_no" => "13"
            ],
            [
                "showIcon" => true,
                "link" => "https://connect.silocloud.io/",
                "popOver" => true,
                "module_no" => "19",
                "useNavigate" => false,
                "imgSrc" => "logo/apps/connect.png",
                "title" => "Connect",
                'slug' => 'connect',
                'key' => '19',
                'footer_visibility' => '2',
                "sequence_no" => "14"
            ],


        ];
        $arr = [
            'theme' => "1",
            "apps" => $appsArray,
        ];
        if ($key && isset($arr[$key]))
            return $arr[$key];
        else
            return $arr;
    }
}
if (!function_exists('generateTokenForURL')) {
    function generateTokenForURL($user_id)
    {
        $token = null;
        $user = User::where('id', $user_id)->first();
        if ($user && $user->toArray()) {
            $payload = [
                'exp' => time() + 100000,
                'iat' => $now = time(),
                'jti' => md5(($now) . mt_rand()),
                'email' => $user->email,
                'username' => $user->username,
                'cloud_user_id' => $user->sso_user_id
            ];
            $token = JWT::encode($payload, config('app.enc_key'), 'HS256');
        }
        return $token;
    }
}
if (!function_exists('generateUsersListForSearch')) {
    function generateUsersListForSearch($users)
    {
        $usersList = $users->map(function ($searchUser) {
            $userProfile = UserProfile::where("user_id", $searchUser->id)->first();

            $data = [
                'user_id' => $searchUser->id,
                'username' => $searchUser->username,
                'email' => $searchUser->email,
                'phone_no' => $userProfile->phone_number
            ];
            if ($userProfile['profile_image_path'] != NULL) {
                $data['profile_image_path'] = getFileTemporaryURL($userProfile['profile_image_path']);
            }
            return $data;
        });
        return $usersList;
    }
}
