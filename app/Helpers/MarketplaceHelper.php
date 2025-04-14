<?php

use App\Models\Marketplace\MarketplaceStore;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

if (!function_exists('getCountrySubdivisions')) {
    function getCountrySubdivisions($stateName, $countryCode)
    {
        $apiKey = env('GEOCODIO_API_KEY'); 
        $apiUrl = "https://api.geocod.io/v1.6/geocode?q=" . urlencode($stateName) . "," . urlencode($countryCode) . "&api_key=" . $apiKey;

        try {
            $response = Http::get($apiUrl);
            $data = $response->json();
            if (isset($data['results']) && !empty($data['results'])) {
                foreach ($data['results'] as $result) {
                    if (isset($result['address_components']) && is_array($result['address_components'])) {
                        if (isset($result['address_components']['state']) && !empty($result['address_components']['state'])) {
                            return $result['address_components']['state'];
                        }
                    }
                }
            } else {
                Log::error("Geocoding API request returned no results.");
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Geocoding API request failed: " . $e->getMessage());
            return null;
        }
    }
}


if (!function_exists('isStoreOwner')) {
    function isStoreOwner($store_id, $user)
    {
        $status = ['status' => false, "message" => "Unauthorized store access"];
        $store = MarketplaceStore::where('id', $store_id)->first();
        if ($store) {
            if ($store->status == "0")
                $status = ['status' => false, "message" => "Store not approved yet"];
            else if ($store->status == "2")
                $status = ['status' => false, "message" => "Store is not active"];
            else if ($store->user_id == $user->id)
                $status = ['status' => true, "message" => "Valid store access"];
            else if ($store->user_id == null) {
                if ($user->role_id == '2' || $user->role_id == '1') {
                    $status = ['status' => true, "message" => "Valid store access"];
                }
            }
        }
        return $status;
    }
}
if (!function_exists('isProductOwner')) {
    function isProductOwner($product_id, $user, $isUserObject = false)
    {
        if (!$isUserObject)
            $user = User::where("id", $user)->first();

        $product = DB::table("marketplace_products as prod")
            ->leftJoin("marketplace_stores as store", "prod.store_id", "=", "store.id")
            ->whereRaw("prod.id = ?", [$product_id])
            ->selectRaw('prod.*,store.id as store_id,store.user_id')
            ->first();
        $status = ['status' => false, "message" => "Invalid product access"];
        if ($product) {
            if ($product->user_id == $user->id)
                $status = ['status' => true, "message" => "Valid product access"];
            else if ($product->user_id == null) {
                if ($user->role_id == '2' || $user->role_id == '1') {
                    $status = ['status' => true, "message" => "Valid product access"];
                }
            }
        }
        return $status;
    }
}
if (!function_exists('isVerifiedBuyer')) {
    function isVerifiedBuyer($product_id, $user)
    {
        $status = ['status' => false, "message" => "Not verified buyer"];
        return $status;
    }
}
if (!function_exists('createMarketPlaceDirectory')) {
    function createMarketPlaceDirectory($path)
    {
        $permissions = 0755;
        if (!is_dir($path)) {
            mkdir($path, $permissions, true);
        }
    }
}


