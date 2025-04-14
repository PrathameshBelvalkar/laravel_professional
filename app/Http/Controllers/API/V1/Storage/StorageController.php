<?php

namespace App\Http\Controllers\API\V1\Storage;

use App\Http\Controllers\Controller;
use App\Models\Subscription\Service;
use App\Models\Subscription\ServicePlan;
use App\Models\Subscription\UserServiceSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StorageController extends Controller
{
    public function getUserStoragePlan(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $storageService = Service::where("key", "storage")->first();
            $storageSubscriptionDetails = UserServiceSubscription::where("user_id", $user->id)->where("service_id", $storageService->id)->first(["plan_id", "start_date", "end_date"]);

            if ($storageSubscriptionDetails != NULL) {
                $storagePlanDetails = ServicePlan::where("id", $storageSubscriptionDetails->plan_id)->first();
                $storagePlanDetails['features'] = json_decode($storagePlanDetails['features']);
                $storagePlanDetails['subscription_details'] = $storageSubscriptionDetails;
            } else {
                // hardcoded user storage plan unit as it is fixed in MB
                $userStorageUnit = "MB";
                $storagePlanDetails = array(
                    "id" => 0,
                    "name" => "Free Trial",
                    "features" => [
                        "storage" => [
                            "text" => "{$user->storage} {$userStorageUnit}",
                            "value" => $user->storage,
                            "unit" => $userStorageUnit
                        ]
                    ]
                );
            }
            $storageFilledAlert = userStorageFilledAlert($user);
            $storageUsedSize = $this->storageSize($user);
            // $files = Storage::disk('public_podcasts')->allFiles("$user->id");
            // dd($files);
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Storage details fetched successfully', 'toast' => true], ['storagePlan' => $storagePlanDetails, "usedStorage" => $storageUsedSize, "storageFilledAlert" => $storageFilledAlert]);
        } catch (\Exception $e) {
            Log::error('Get storage service plan details error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function getStoragePlans()
    {
        try {
            $storageService = Service::where("key", "storage")->first();
            $storagePlans = ServicePlan::where("service_id", $storageService->id)->get();
            if (!$storagePlans->isEmpty()) {
                foreach ($storagePlans as $storagePlan) {
                    $storagePlan['features'] = json_decode($storagePlan['features']);
                }
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Storage plans fetched successfully', 'toast' => true], ['storagePlans' => $storagePlans]);
            }
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No storage plans found', 'toast' => true]);
        } catch (\Exception $e) {
            Log::error('Get storage plans error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function getStorageServiceDetails()
    {
        try {
            $storageServiceDetails = Service::where("key", "storage")->first();
            if ($storageServiceDetails != NULL) {
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Storage service details fetched successfully', 'toast' => true], ['storageServiceDetails' => $storageServiceDetails]);
            }
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No storage service details found', 'toast' => true]);
        } catch (\Exception $e) {
            Log::error('Get storage service details error: ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function storageSize($user)
    {
        $privateStorageSize = getFileSize("users/private/$user->id", "folder", 'local');
        $publicStorageSize = getFileSize("$user->id", "folder", 'public_podcasts');
        $totalStorageSize = $privateStorageSize + $publicStorageSize;

        return formatFileSize($totalStorageSize, false);
    }
}
