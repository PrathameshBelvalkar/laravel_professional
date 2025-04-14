<?php

namespace App\Http\Middleware\storage;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Subscription\UserServiceSubscription;
use App\Models\Subscription\Service;
use Carbon\Carbon;

class CheckStorageSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->attributes->get("user");
        $storageService = Service::where("key", "storage")->first();
        $userStorageSubscription = UserServiceSubscription::where("user_id", $user->id)->where("service_id", $storageService->id)->first();

        if($userStorageSubscription && $userStorageSubscription != NULL ){
            $userStorageSubscriptionEndDate = Carbon::createFromFormat('Y-m-d', $userStorageSubscription->end_date)->startOfDay();
            $todayDate = Carbon::now()->startOfDay();

            if ($userStorageSubscriptionEndDate->lessThan($todayDate)) {
                $returnResponse = generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Your storage subscription is expired, please upgrade or renew your storage subscription plan to continue', 'toast' => true]);
                return response()->json($returnResponse);
            }
        }
        return $next($request);
    }
}
