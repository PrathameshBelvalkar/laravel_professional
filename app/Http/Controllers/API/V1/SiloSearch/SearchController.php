<?php

namespace App\Http\Controllers\API\V1\SiloSearch;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\MarketplaceStore;
use App\Models\MarketplaceProducts;
use App\Models\StreamDeck\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function cloudSearch(Request $request)
    {
        try {
            $searchParam = $request->input('searchParam');
            $searchType = $request->input('searchType');

            $result = [];
            DB::transaction(function () use ($searchType, $searchParam, &$result) {
                if ($searchType == "all" || $searchType == "marketplace_products") {
                    $marketplaceProducts = MarketplaceProducts::where('product_name', 'like', '%' . $searchParam . '%')->limit(10)->get(["id", "product_name", "thumbnail"]);
                    if ($marketplaceProducts && count($marketplaceProducts) > 0) {
                        $result[] = ['type' => 'marketplace_products', 'data' => $marketplaceProducts];
                    }
                }
                if ($searchType == "all" || $searchType == "silo_apps") {
                    $apps = DB::table('silo_apps')->select('name', 'image_link', 'project_link')->where('name', 'like', '%' . $searchParam . '%')->limit(10)->get();
                    if ($apps && count($apps) > 0) {
                        $result[] = ['type' => 'silo_apps', 'data' => $apps];
                    }
                }
                if ($searchType == "all" || $searchType == "user_profiles") {
                    $userProfiles = DB::table('users')
                        ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                        ->select('users.username', 'users.first_name', 'users.last_name', 'user_profiles.profile_image_path')
                        ->where(function ($query) use ($searchParam) {
                            $query->where('users.username', 'like', '%' . $searchParam . '%')
                                ->orWhere('users.first_name', 'like', '%' . $searchParam . '%')
                                ->orWhere('users.last_name', 'like', '%' . $searchParam . '%');
                        })
                        ->limit(10)
                        ->get();
                    if ($userProfiles && count($userProfiles) > 0) {
                        $userProfiles->transform(function ($user) {
                            $user->user_profile = getFileTemporaryURL($user->profile_image_path);
                            return $user;
                        });
                        $result[] = ['type' => 'user_profiles', 'data' => $userProfiles];
                    }
                }
                if ($searchType == "all" || $searchType == "stores") {
                    $stores = MarketplaceStore::where('name', 'like', '%' . $searchParam . '%')->limit(10)->get(["name", "logo", "description"]);
                    if($stores && count($stores) > 0){
                        $result[] = ['type' => 'stores', 'data' => $stores];
                    }
                }
                if ($searchType == "all" || $searchType == "tv_channels") {
                    $channels = Channel::where('channel_name', 'like', '%' . $searchParam . '%')->limit(10)->get(["channel_name", "logo"]);

                    if($channels && count($channels) > 0){
                        $channels->transform(function ($channel) {
                            $channel->logo = getFileTemporaryURL($channel->logo);
                            return $channel;
                        });
    
                        $result[] = ['type' => 'channels', 'data' => $channels];
                    }
                }
            });

            if (count($result) == 0) {
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "No result found with this search parameter", 'toast' => true], ['noResults' => true]);
            }
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => "result fetched successfully", 'toast' => true], ['result' => $result]);
        } catch (\Exception $e) {
            Log::error("Error on processing: " . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error on processing", 'toast' => true]);
        }
    }
}
