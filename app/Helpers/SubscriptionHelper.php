<?php
use App\Models\CalendarEvent;
use App\Models\PromoCode;
use App\Models\Subscription\Package;
use App\Models\Subscription\Service;
use App\Models\Subscription\ServicePlan;
use App\Models\Subscription\UserPackageSubscription;
use App\Models\Subscription\UserPackageSubscriptionLog;
use App\Models\Subscription\UserServiceSubscription;
use Carbon\Carbon;

if (!function_exists('getPackageDetails')) {
    function getPackageDetails($package)
    {
        $services = json_decode($package['services'], true);
        $serviceList = [];
        if (!$services) {
            $package['services'] = null;
            return $package;
        }
        foreach ($services as $service_id => $plan_id) {
            $service = Service::where("id", $service_id)->select(['id', 'name', 'key', 'description', 'category', 'logo', 'icon', 'bs_icon', 'thumbnail', 'link', 'sequence_no', 'status', 'is_external_app', 'is_free', 'trial_period'])->first();
            $plan = ServicePlan::where("id", $plan_id)->select(['id', 'name', 'service_id', 'features', 'monthly_price', 'quarterly_price', 'yearly_price', 'status', 'styles', 'logo', 'icon'])->first();
            $serviceList[$service_id]['service'] = $service;
            $serviceList[$service_id]['plan'] = $plan;
        }

        $package['services'] = $serviceList;
        return $package;
    }
}
if (!function_exists('getPackageDetailWithAllServices')) {
    function getPackageDetailWithAllServices($package, $request)
    {
        $packageServices = json_decode($package['services'], true);
        $allServices = Service::where("status", '0')->where("is_external_service", '0')->select(['id', 'name', 'key', 'description', 'category', 'logo', 'icon', 'bs_icon', 'thumbnail', 'link', 'sequence_no', 'status', 'is_external_app', 'is_free', 'trial_period'])->orderBy("sequence_no", "asc")->get();
        $monthlyPriceWithServices = 0;
        $quarterlyPriceWithServices = 0;
        $yearlyPriceWithServices = 0;
        $user = $request->attributes->get('user');
        $subscribedPackage = UserPackageSubscription::where("user_id", $user->id)->select(['package_id', 'validity', 'price', 'auger_price', 'end_date'])->whereDate('end_date', '>', Carbon::now())->where('status', "1")->first();

        $package['is_subscribed'] = false;
        $package['subscription_validity'] = null;
        $package['is_subscribed_end_date'] = null;
        $package['is_subscribed_price'] = 0;
        $package['is_upcoming'] = false;
        if ($subscribedPackage && $subscribedPackage->package_id == $package['id']) {
            $package['is_subscribed'] = true;
            $package['subscription_validity'] = $subscribedPackage->validity;
            $package['is_subscribed_price'] = $subscribedPackage->price + $subscribedPackage->auger_price;
            $package['is_subscribed_end_date'] = $subscribedPackage->end_date;
        }
        $serviceList = [];
        if ($allServices) {
            $allServices = $allServices->toArray();
            foreach ($allServices as $service) {
                if (array_key_exists($service['id'], $packageServices)) {
                    $plan = ServicePlan::where("id", $packageServices[$service['id']])->select(['id', 'name', 'service_id', 'features', 'monthly_price', 'quarterly_price', 'yearly_price', 'status', 'styles', 'logo', 'icon'])->first();
                    $service['include'] = true;
                    $service['plan'] = $plan;
                    $monthlyPriceWithServices = $plan && is_numeric($plan->monthly_price) ? $monthlyPriceWithServices + $plan->monthly_price : $monthlyPriceWithServices + 0;
                    $quarterlyPriceWithServices = $plan && is_numeric($plan->monthly_price) ? $quarterlyPriceWithServices + $plan->quarterly_price : $quarterlyPriceWithServices + 0;
                    $yearlyPriceWithServices = $plan && is_numeric($plan->yearly_price) ? $yearlyPriceWithServices + $plan->yearly_price : $yearlyPriceWithServices + 0;
                } else {
                    $service['include'] = false;
                    $service['plan'] = null;
                }
                $serviceList[] = $service;
            }
        }
        $package['services'] = $serviceList;
        $package['monthlyPriceWithServices'] = $monthlyPriceWithServices;
        $package['quarterlyPriceWithServices'] = $quarterlyPriceWithServices;
        $package['yearlyPriceWithServices'] = $yearlyPriceWithServices;
        return $package;
    }
}
if (!function_exists('getUsersServiceStatus')) {
    function getUsersServiceStatus($user, $service, $request)
    {
        $serviceStatus['plan'] = null;
        $serviceStatus['plan_id'] = null;
        $serviceStatus['package'] = null;
        $serviceStatus['end_date'] = null;
        $serviceStatus['start_date'] = null;
        $serviceStatus['renew'] = false;
        $serviceStatus['price'] = 0;
        $serviceStatus['auger_price'] = 0;
        $serviceStatus['validity'] = null;
        $serviceStatus['styles'] = null;
        $serviceStatus['current_subscribed'] = false;
        $serviceStatus['status'] = "Not subscribed";
        $serviceStatus['is_upcoming'] = false;
        $today = date('Y-m-d');
        $user_service_subscription = UserServiceSubscription::where("service_id", $service['id'])->where("user_id", $user->id)->select(['service_plan_data', 'plan_id', 'validity', 'end_date', 'start_date', 'package_id'])->where("status", "1")->where('end_date', '>=', $today)->with("plan", "service", "package")->first();
        if ($service['is_free'] == "1") {
            return $serviceStatus;
        }
        if ($user_service_subscription && $user_service_subscription->toArray()) {
            $user_service_subscription = $user_service_subscription->toArray();
            $subscribedPlan = json_decode($user_service_subscription['service_plan_data'], true);
            $serviceStatus['plan_id'] = $user_service_subscription['plan_id'];
            if ($user_service_subscription['validity'] === "1") {
                $serviceStatus['price'] = isset($subscribedPlan['monthly_price']) ? $subscribedPlan['monthly_price'] : 0;
            } else if ($user_service_subscription['validity'] === "3") {
                $serviceStatus['price'] = isset($subscribedPlan['quarterly_price']) ? $subscribedPlan['quarterly_price'] : 0;
            } else if ($user_service_subscription['validity'] === "12") {
                $serviceStatus['price'] = isset($subscribedPlan['yearly_price']) ? $subscribedPlan['yearly_price'] : 0;
            }
            $serviceStatus['auger_price'] = $serviceStatus['price'] ? $serviceStatus['price'] * (config('app.auger_fee') / 100) : 0;
            $serviceStatus['auger_price'] = (float) number_format($serviceStatus['auger_price'], 2, ".", "");
            $serviceStatus['end_date'] = $user_service_subscription['end_date'];
            $serviceStatus['start_date'] = $user_service_subscription['start_date'];
            $serviceStatus['renew'] = true;
            $serviceStatus['total_fee'] = $serviceStatus['price'] + $serviceStatus['auger_price'];
            $serviceStatus['status'] = "Active";
            $serviceStatus['current_subscribed'] = true;
            $serviceStatus['validity'] = $user_service_subscription['validity'];
            $serviceStatus['package'] = null;
            if ($user_service_subscription['package_id'] && isset($user_service_subscription['package']) && $user_service_subscription['package']) {
                $serviceStatus['package'] = $user_service_subscription['package']['name'] || "";
            }
            if (isset($user_service_subscription['plan']) && $user_service_subscription['plan']) {
                $serviceStatus['plan'] = $user_service_subscription['plan']['name'] || "";
            }
            if (json_decode($user_service_subscription['service_plan_data'], true)) {
                $servicePlanData = json_decode($user_service_subscription['service_plan_data'], true);
                if ($servicePlanData && isset($servicePlanData['styles'])) {
                    $serviceStatus['styles'] = json_decode($servicePlanData['styles'], true);
                }
            }
        }
        return $serviceStatus;
    }
}
if (!function_exists('checkPromoCode')) {
    function checkPromoCode($promocode, $user, $price)
    {
        $return = false;
        $today = date('Y-m-d');
        $promocode_data = PromoCode::where("promo_code", $promocode)->where("status", "1")->where('end_date', '>=', $today)->first();
        if ($promocode_data && $promocode_data->toArray()) {
            $promocode_data = $promocode_data->toArray();
            if ($promocode_data['max_users'] > $promocode_data['count']) {
                $promCodeUsed = UserPackageSubscriptionLog::where("promo_code", $promocode)->where("user_id", $user->id)->count();
                if (!$promCodeUsed) {
                    if ($promocode_data['type'] == '1') {
                        $price = $price - ($price * $promocode_data['value'] / 100);
                    } else {
                        $price = $price - $promocode_data['value'];
                    }
                    return array("price" => $price, "promocode_data" => $promocode_data);
                }
            }
        }
        return $return;
    }
}
if (!function_exists('checkStorageForDowngrade')) {
    function checkStorageForDowngrade($user, $newPlanId)
    {
        $checkStorageForDowngradeStatus = ['status' => true, "message" => "Valid downgrade"];
        $newStoragePlanRow = ServicePlan::where("id", $newPlanId)->first();
        if ($newStoragePlanRow && $newStoragePlanRow->toArray()) {
            $features = $newStoragePlanRow->features ? json_decode($newStoragePlanRow->features, true) : [];

            if ($features) {
                $storageBytes = $features['storage']['value'] * 1024 * 1024 * 1024;
                $existingStorageBytes = getFileSize("users/private/$user->id", "folder");
                if ($existingStorageBytes > $storageBytes) {
                    $checkStorageForDowngradeStatus = ['status' => false, "message" => 'Clear storage below ' . $features['storage']['value'] . " " . $features['storage']['unit'] . ' before downgrade plan'];
                }
            }
        }
        return $checkStorageForDowngradeStatus;
    }
}
if (!function_exists('getSubscriptionAlert')) {
    function getSubscriptionAlert($user, $type = "service", $service_id = null)
    {
        $alert[] = ['status' => false, "message" => null, "type" => null];
        $today = Carbon::now();
        if ($type == "package") {
            $subscribedPackageLog = UserPackageSubscriptionLog::where("user_id", $user->id)->select(['id', 'start_date', 'package_data'])->where("status", "0")->orderBy("start_date", "asc")->whereDate('end_date', '>', Carbon::now())->whereDate('start_date', '>', Carbon::now())->first();
            $activeSubscribedPackageLog = UserPackageSubscriptionLog::where("user_id", $user->id)->where("status", "1")->count();
            if ($subscribedPackageLog && $subscribedPackageLog->toArray() && !$activeSubscribedPackageLog) {
                $dateToCheck = $subscribedPackageLog->start_date;

                $isUpcoming = $today->lessThan($dateToCheck);
                $package_data = $subscribedPackageLog->package_data ? json_decode($subscribedPackageLog->package_data, true) : [];
                $name = "";
                if ($package_data && $package_data['name']) {
                    $name = $package_data['name'];
                }
                if ($isUpcoming) {
                    $alert[0] = ['status' => true, "message" => "You have subscribed $name package with upcoming date from " . date('F-d-Y', strtotime($subscribedPackageLog->start_date)), "type" => "info", "data" => ['invoice_id' => $subscribedPackageLog->id]];
                }
            }
            $subscribedPackage = UserPackageSubscription::where("user_id", $user->id)->select(['end_date', 'id', 'package_data'])->where("status", "1")->whereDate('end_date', '>', Carbon::now())->first();
            if ($subscribedPackage && $subscribedPackage->toArray()) {
                $dateToCheck = $subscribedPackage->end_date;
                $differenceInDays = $today->diffInDays($dateToCheck);
                $package_data = $subscribedPackage->package_data ? json_decode($subscribedPackage->package_data, true) : [];
                $name = "";
                if ($package_data && $package_data['name']) {
                    $name = $package_data['name'];
                }
                if ($differenceInDays < 4) {
                    if ($alert[0]['status'] == true) {
                        $alert[] = ['status' => true, "message" => "Your $name package is about expire on " . date("F-d-Y", strtotime($subscribedPackage->end_date)) . " days", "type" => "danger", "data" => ['invoice_id' => $subscribedPackage->id]];
                    } else {
                        $alert[0] = ['status' => true, "message" => "Your $name package is about expire on " . date("F-d-Y", strtotime($subscribedPackage->end_date)) . " days", "type" => "danger", "data" => ['invoice_id' => $subscribedPackage->id]];
                    }
                }
            }
        } else if ($type == "service") {
            $subscribedService = UserServiceSubscription::where("user_id", $user->id)->where("service_id", $service_id)->select(['start_date', 'id', 'end_date'])->orderBy("created_at", "desc")->whereDate('end_date', '>', Carbon::now())->first();
            if ($subscribedService && $subscribedService->toArray()) {
                $dateToCheck = $subscribedService->start_date;
                $isUpcoming = $today->lessThan($dateToCheck);
                $service_data = Service::where("id", $service_id)->select(['name'])->first();
                $name = "";
                if ($service_data && $service_data->toArray()) {
                    $name = $service_data->name;
                }
                if ($isUpcoming) {
                    $alert[0] = ['status' => true, "message" => "You have subscribed $name service with upcoming dates", "type" => "info", "data" => ['invoice_id' => $subscribedService->id]];
                } else {
                    $dateToCheck = $subscribedService->end_date;
                    $differenceInDays = $today->diffInDays($dateToCheck);
                    if ($differenceInDays < 4)
                        $alert[0] = ['status' => true, "message" => "Your $name service is about expire in " . $differenceInDays . " days", "type" => "danger", "data" => ['invoice_id' => $subscribedService->id]];
                }

            }
        }
        if (isset($alert[0]['status']) && !$alert[0]['status'])
            $alert = [];
        return $alert;
    }
}
if (!function_exists('getSubscribedPackageDataByKey')) {
    function getSubscribedPackageDataByKey($user_id, $key = "name")
    {
        $subscribedPackageQuery = UserPackageSubscription::query()->where("user_id", $user_id)->where("status", "1");
        if ($subscribedPackageQuery->count() > 0) {
            $subscribedPackage = $subscribedPackageQuery->first();
            $package_data = $subscribedPackage->package_data ? json_decode($subscribedPackage->package_data, true) : [];
            if ($package_data && isset($package_data[$key])) {
                return $package_data[$key];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }
}
if (!function_exists('getSubscribedPackageInvoice')) {
    function getSubscribedPackageInvoice($user_id, $current = true)
    {
        if ($current)
            $subscribedPackage = UserPackageSubscription::where('user_id', $user_id)->where('status', "1")->where("end_date", '>=', Carbon::now())->first();
        else
            $subscribedPackage = UserPackageSubscriptionLog::where('user_id', $user_id)->where('status', "0")->where("start_date", '>', Carbon::now())->orderBy('end_date', "desc")->first();
        if ($subscribedPackage && $subscribedPackage->toArray()) {
            $subscribedPackage = $subscribedPackage->toArray();
            $packageData = $subscribedPackage['package_data'] ? json_decode($subscribedPackage['package_data'], true) : [];
            $invoiceArray = [];
            $invoiceArray['name'] = $packageData['name'];
            $invoiceArray['price'] = $subscribedPackage['price'];
            $invoiceArray['start_date'] = $subscribedPackage['start_date'];
            $invoiceArray['end_date'] = $subscribedPackage['end_date'];
            $invoiceArray['validity'] = $subscribedPackage['validity'];
            $invoiceArray['created_at'] = date("Y-m-d", strtotime($subscribedPackage['created_at']));
            $invoiceArray['services'] = [];
            foreach ($packageData['services'] as $tempService) {
                $serviceDetail = $tempService['service'];
                $invoiceArray['services'][] = $serviceDetail['name'];
            }
            return $invoiceArray;
        } else {
            return null;
        }
    }
}
if (!function_exists('getSubscribedServices')) {
    function getSubscribedServices($user_id)
    {
        $subscribedServices = UserServiceSubscription::where('user_id', $user_id)->where('status', "1")->where("end_date", '>=', Carbon::now())->whereNull("package_id")->get();
        if ($subscribedServices && $subscribedServices->toArray()) {
            $subscribedServices = $subscribedServices->toArray();
            $serviceArray = array();
            foreach ($subscribedServices as $tempService) {
                $tempRow = array();
                $serviceDetails = Service::where("id", $tempService['service_id'])->first();
                $planDetails = ServicePlan::where("id", $tempService['plan_id'])->first();
                $tempRow['name'] = $serviceDetails->name;
                $tempRow['plan'] = $planDetails->name;
                $tempRow['price'] = $planDetails->monthly_price;
                $tempRow['validity'] = $tempService['validity'];
                if ($tempService['validity'] === "3") {
                    $tempRow['price'] = $planDetails->quarterly_price;
                } else if ($tempService['validity'] === "12") {
                    $tempRow['price'] = $planDetails->yearly_price;
                }
                $tempRow['features'] = $planDetails->features ? json_decode($planDetails->features, true) : [];
                $serviceArray[] = $tempRow;
            }
            return $serviceArray;
        } else {
            return null;
        }
    }
}
if (!function_exists('getInvoiceNotes')) {
    function getInvoiceNotes($type = "Package")
    {

        $arr['note']['text'] = "The contents of subscription " . strtolower($type) . ", such as names pricing, etc. are subject to change.";
        $arr['note']['title'] = "Note";
        $arr['payment_option']['text'] = "PayPal-SBC Token";
        $arr['payment_option']['title'] = "Payment Option";
        $arr['terms_and_conditions']['text'] = "<p>Additional Transaction fee<sup>1</sup> to be paid along with subscription fee. All Rights Reserved</p>";
        $arr['terms_and_conditions']['title'] = "Terms and Conditions";
        return $arr;
    }
}
if (!function_exists('addSubscriptionEvent')) {
    function addSubscriptionEvent($user_id, $event_title, $start_date_time, $end_date_time, $event_description, $link)
    {

        $temp_end_date_time = (new DateTime($start_date_time))->add(new DateInterval('PT15M'));

        $temp_end_date_time = $temp_end_date_time->format('Y-m-d H:i:s');

        $calendarData = [
            'user_id' => $user_id,
            'event_title' => $event_title,
            'category' => "Buy Subscription",
            'start_date_time' => date("Y-m-d H:i:s", strtotime($start_date_time)),
            'end_date_time' => $temp_end_date_time,
            'event_description' => $event_description,
            'link' => $link,
        ];
        $subscriptionStartEvent = CalendarEvent::create($calendarData);

        $calendarData['event_title'] = "Expire " . $event_title;
        $calendarData['start_date_time'] = date("Y-m-d H:i:s", strtotime($end_date_time));
        $temp_end_date_time = (new DateTime($end_date_time))->add(new DateInterval('PT15M'));
        $temp_end_date_time = $temp_end_date_time->format('Y-m-d H:i:s');
        $calendarData['end_date_time'] = $temp_end_date_time;
        $calendarData['category'] = "Subscription Expire";

        $subscriptionStartEvent = CalendarEvent::create($calendarData);

    }
}

