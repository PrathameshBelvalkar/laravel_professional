<?php

use App\Models\StreamDeck\Channel;
use App\Models\StreamDeck\TvLivestream;
use App\Models\StreamDeck\Videos;
use App\Models\Subscription\UserServiceSubscription;
use Carbon\Carbon;

if (!function_exists('getSubscriptionDetails')) {
    function getSubscriptionDetails($userId, $serviceId)
    {
        $subscription = UserServiceSubscription::select('service_plan_data', 'start_date', 'end_date', 'status', 'validity')
            ->where('user_id', $userId)
            ->where('service_id', $serviceId)
            ->first();

        if ($subscription) {
            $endDate = Carbon::parse($subscription->end_date);
            $today = Carbon::today();
            $daysRemaining = $today->diffInDays($endDate, false);
            // dd($daysRemaining);
            if ($endDate->isPast() && !$endDate->isToday()) {
                return null;
            }

            $servicePlanData = json_decode($subscription->service_plan_data);

            if (isset($servicePlanData->features)) {
                $servicePlanData->features = json_decode($servicePlanData->features, true);
            }

            if (isset($servicePlanData->styles)) {
                $servicePlanData->styles = json_decode($servicePlanData->styles, true);
            }

            return [
                'service_plan_data' => $servicePlanData,
                'start_date' => $subscription->start_date,
                'end_date' => $subscription->end_date,
                'status' => $subscription->status,
                'validity' => $subscription->validity,
                'is_ends_today' => $endDate->isToday(),
                'subscription_alert' => $daysRemaining <= 5 ? true : false,
            ];
        }
        return null;
    }
}
if (!function_exists('getUserVideosDuration')) {
    function getUserVideosDuration($userId)
    {
        $totalDurationInSeconds = 0;
        $videos = Videos::where('user_id', $userId)->get();

        foreach ($videos as $video) {
            $totalDurationInSeconds += $video->duration; // assuming duration is in seconds
        }
        $totalDurationInMinutes = $totalDurationInSeconds / 60;

        return $totalDurationInMinutes;
    }
}
if (!function_exists('getUserChannelsCount')) {
    function getUserChannelsCount($userId)
    {
        return Channel::where('user_id', $userId)->count();
    }
}
if (!function_exists('getUserTvLivestreamsCount')) {
    function getUserTvLivestreamsCount($userId)
    {
        return TvLivestream::where('user_id', $userId)
            ->where('status', '1')
            ->count();
    }
}
