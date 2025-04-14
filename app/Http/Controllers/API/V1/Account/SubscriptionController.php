<?php

namespace App\Http\Controllers\API\V1\Account;

use App\Http\Requests\Subscription\DowngradeServiceSubscriptionRequest;
use App\Http\Requests\Subscription\ExternalServiceSubscriptionRequest;
use App\Http\Requests\Subscription\GetInvoiceRequest;
use App\Http\Requests\Subscription\GetPlanRequest;
use App\Http\Requests\Subscription\GetServiceDetailsRequest;
use App\Http\Requests\Subscription\GetServiceInvoiceRequest;
use App\Http\Requests\Subscription\GetServiceListRequest;
use App\Http\Requests\Subscription\PromoCodeValidateRequest;
use App\Http\Requests\Subscription\SubscribeServiceRequest;
use App\Http\Requests\Subscription\DowngradePackageRequest;
use App\Mail\SendMail;
use App\Models\PromoCode;
use App\Models\Subscription\Service;
use App\Models\Subscription\ServicePlan;
use App\Models\Subscription\UserPackageSubscriptionLog;
use App\Models\Subscription\UserServiceSubscriptionLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\SubscribePackageRequest;
use App\Models\Subscription\Package;
use App\Models\Subscription\UserPackageSubscription;
use App\Models\Subscription\UserServiceSubscription;
use App\Models\TokenTransactionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriptionController extends Controller
{
    public function getPackageList(Request $request)
    {
        $packages = Package::where("type", "3")->select(['id', 'name', 'key', 'description', 'services', 'logo', 'icon', 'thumbnail', 'monthly_price', 'quarterly_price', 'yearly_price'])->get();
        $user = $request->attributes->get('user');
        if ($packages && $packages->toArray()) {
            $package_list = [];
            foreach ($packages->toArray() as $package) {
                $package_list[] = getPackageDetailWithAllServices($package, $request);
            }

            $token_value = getTokenMetricsValues();
            $auger_fee = (float) config('app.auger_fee');
            $account_tokens = $user->account_tokens;
            $alert_data = getSubscriptionAlert($user, "package");
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'List retrieved', 'toast' => true], [
                "packages" => $package_list,
                "token_value" => $token_value,
                "auger_fee" => $auger_fee,
                "account_tokens" => $account_tokens,
                "alert_data" => $alert_data
            ]);
        } else {
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No packages found', 'toast' => true]);
        }
    }
    public function getPackage(Request $request)
    {
        $user = $request->attributes->get('user');
        if (isset($request->package_id)) {
            $package_id = $request->package_id;
            $package = Package::where("id", $package_id)->where("type", '3')->select(['id', 'name', 'key', 'description', 'services', 'logo', 'icon', 'thumbnail', 'monthly_price', 'quarterly_price', 'yearly_price', 'type'])->first();
            if ($package) {
                $token_value = getTokenMetricsValues();
                $auger_fee = (float) config('app.auger_fee');
                $account_tokens = $user->account_tokens;
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Package retrieved', 'toast' => true], ["package" => getPackageDetails($package->toArray()), "token_value" => $token_value, "auger_fee" => $auger_fee, "account_tokens" => $account_tokens]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Package not found', 'toast' => true]);
            }
        } else {
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Package id missing', 'toast' => true]);
        }
    }
    public function subscribePackage(SubscribePackageRequest $request)
    {
        DB::beginTransaction();
        try {
            // declare variable
            $package_id = $request->package_id;
            $package_start_date = isset($request->package_start_date) ? $request->package_start_date : date('Y-m-d');
            $user = $request->attributes->get('user');
            $now = Carbon::now();
            $upComingPlan = false;

            // check upComingPlan is available/eligible 
            $user_package_subscription_row = UserPackageSubscription::where("user_id", $user->id)->orderBy("id", "desc")->where('status', "1")->first();
            if ($user_package_subscription_row && $user_package_subscription_row->toArray()) {
                $packageEndDate = Carbon::parse($user_package_subscription_row->end_date);
                $package_start_date = $packageEndDate->lessThan($now) ? date('Y-m-d') : $package_start_date;
                $upComingPlan = $now->lessThan($package_start_date);
            }

            // collect package information
            $package = Package::where("id", $package_id)->first();
            $package = getPackageDetails($package->toArray());
            $price = $package['monthly_price'];
            $validity = $request->validity;
            if ($validity === "3") {
                $price = $package['quarterly_price'];
            } else if ($validity === "12") {
                $price = $package['yearly_price'];
            }

            // apply promocode if applicable
            $promocode = false;
            $promoCodeArr = $this->makePromoCodeEntry($user, $request, $price);
            $price = $promoCodeArr['price'];
            $promocode = $promoCodeArr['promocode'];

            // check balance available
            $token_value = getTokenMetricsValues();
            $packageTokenPrice = $price / $token_value;
            $auger_tokens = $packageTokenPrice * (config('app.auger_fee') / 100);
            if (!balanceValidations($user->id, $packageTokenPrice))
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Insufficient token balance', 'toast' => true], ["reqiured_tokens" => $packageTokenPrice + $auger_tokens, "available_tokens" => $user->account_tokens, "token_value" => $token_value, "price" => $price]);


            // make transactions
            $admin_user = User::where("role_id", "2")->orderBy('id', "asc")->first();
            if (!$admin_user)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Admin user not found', 'toast' => true]);

            $perticulars = ucfirst($package['name']) . " package purchase";
            $lastTokenTransactionLog = TokenTransactionLog::orderBy('id', 'desc')->first();
            $transaction_id = makeTransaction($user, $admin_user, $packageTokenPrice, $perticulars, "4", "2", $lastTokenTransactionLog);
            $auger_transaction_id = makeTransaction($user, $admin_user, $auger_tokens, "Auger Fee " . $perticulars, "5", "2", $lastTokenTransactionLog, $transaction_id);

            // subscribe package(if upcoming then make entry for log) and subscribe service
            $user_packages_subcription = $this->makePackageSubscriptionEntry($user, $package, $price, $transaction_id, $auger_transaction_id, $package_start_date, $validity, $promocode, $upComingPlan);

            // add alert to notification
            $link = "/subscriptions/subscription-package-details/" . $package['id'] . "/print-invoice/" . $user_packages_subcription->id;
            addNotification($user->id, $user->id, $package['name'] . " Package Subscription", "You have subscribed package " . $package['name'], $user_packages_subcription->id, "1", $link);

            // add calendar event
            $calendarLink = config("app.account_url") . "subscriptions/subscription-package-details/" . $package['id'] . "/print-invoice/" . $user_packages_subcription->id;
            $end_date = addMonthsToDate($package_start_date, $validity);
            addSubscriptionEvent($user->id, $package['name'] . " Package Subscription", $package_start_date, $end_date, null, $calendarLink);

            // commit database transaction
            DB::commit();

            // send subscription mail
            $this->sendPackageSubcriptionMail($user, $user_packages_subcription, $package_start_date, $transaction_id, $price, $package, $auger_transaction_id, $validity);

            $updatedUser = User::where('id', $user->id)->first();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Package subscribed successfully', 'toast' => true, 'data' => ["invoice_id" => $user_packages_subcription->id, "package" => $user_packages_subcription->toArray(), "account_tokens" => $updatedUser->account_tokens]]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Subscription Error File ' . $e->getFile() . ' LineNo ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function makePromoCodeEntry($user, $request, $price)
    {
        $returnArr = ['price' => $price, 'promocode' => null];
        if (isset($request->promocode)) {
            $tempPromoData = checkPromoCode($request->promocode, $user, $price);
            if ($tempPromoData) {
                $returnArr['price'] = isset($tempPromoData['price']) ? $tempPromoData['price'] : $price;
                $returnArr['promocode'] = $request->promocode;
                $promocode_data = PromoCode::where("promo_code", $request->promocode)->first();
                $promocode_data->count = $promocode_data->count ? $promocode_data->count + 1 : 1;
                $promocode_data->save();
            }
        }
        return $returnArr;
    }
    public function sendPackageSubcriptionMail($user, $user_packages_subcription, $package_start_date, $transaction_id, $price, $package, $auger_transaction_id, $validity_months)
    {
        $txnLog = TokenTransactionLog::where("id", $transaction_id)->first();
        $pdfData = [ // Your dynamic data for the PDF
            'page_title' => 'Package Subscription',
            'logo' => asset('assets/images/logo/logo-dark.png'),
            'username' => $user->username,
            'email' => $user->email,
            'invoice_id' => $user_packages_subcription->id,
            'invoice_date' => date("Y-m-d"),
            'auger_fee' => $price * (config('app.auger_fee') / 100),
            'grand_total' => $price,
        ];

        $pdfData['services'] = [];
        $tempServices = $package['services'];
        foreach ($tempServices as $service) {

            $service_row = $service['service'];
            $plan_row = $service['plan'];

            $tempService['qty'] = 1;
            $tempService['price'] = $plan_row['monthly_price'];
            if ($validity_months === "1") {
                $tempService['price'] = $service['plan']['monthly_price'];
            } else if ($validity_months === "3") {
                $tempService['price'] = $service['plan']['quarterly_price'];
            } else if ($validity_months === "12") {
                $tempService['price'] = $service['plan']['yearly_price'];
            }
            $tempService['service_name'] = $service_row['name'];
            $tempService['service_id'] = $service_row['id'];
            $tempService['plan_name'] = $plan_row['name'];
            $pdfData['services'][] = $tempService;
        }

        $pdf = Pdf::loadView('pdf-template.invoice', $pdfData);
        $message = "Thank you for package subscription [" . $package['name'] . "], Package will be available from " . date("F-d-Y", strtotime($package_start_date));
        sendTransactionMail($txnLog, $pdf, $message, true, true);
        $augerTxnLog = TokenTransactionLog::where("id", $auger_transaction_id)->first();
        sendTransactionMail($augerTxnLog, null, null, true, true);
    }
    public function makePackageSubscriptionEntry($user, $package, $price, $transaction_id, $auger_transaction_id, $package_start_date, $validity, $promocode, $upComingPlan)
    {
        $user_packages_subcription_log = new UserPackageSubscriptionLog();
        $user_packages_subcription_log->user_id = $user->id;
        $user_packages_subcription_log->package_id = $package['id'];
        $user_packages_subcription_log->price = $price;
        $user_packages_subcription_log->auger_price = $price * (config('app.auger_fee') / 100);
        $user_packages_subcription_log->package_data = json_encode($package);
        $user_packages_subcription_log->txn_id = $transaction_id;
        $user_packages_subcription_log->auger_txn_id = $auger_transaction_id;
        $user_packages_subcription_log->payment_mode = "4";
        $user_packages_subcription_log->start_date = $package_start_date;
        $user_packages_subcription_log->validity = $validity;
        $user_packages_subcription_log->token_value = getTokenMetricsValues();
        $user_packages_subcription_log->end_date = addMonthsToDate($package_start_date, $validity);
        $user_packages_subcription_log->promo_code = $promocode ? $promocode : null;
        $user_packages_subcription_log->status = $upComingPlan ? "0" : "1";
        $user_packages_subcription_log->save();
        $user_packages_subcription = null;


        $isFirstPackageSubscription = UserPackageSubscription::where("user_id", $user->id)->count();
        if (!$upComingPlan) {
            UserPackageSubscriptionLog::whereNot('id', $user_packages_subcription_log->id)->where("status", "1")->where("user_id", $user->id)->update(['status' => "2"]);
            $user_packages_subcription_count = UserPackageSubscription::where("user_id", $user->id)->count();
            if (!$user_packages_subcription_count) {
                $user_packages_subcription = new UserPackageSubscription();
                $user_packages_subcription->user_id = $user->id;
            } else {
                $user_packages_subcription = UserPackageSubscription::where("user_id", $user->id)->first();
            }
            $user_packages_subcription->package_id = $package['id'];
            $user_packages_subcription->price = $price;
            $user_packages_subcription->auger_price = $price * (config('app.auger_fee') / 100);
            $user_packages_subcription->package_data = json_encode($package);
            $user_packages_subcription->txn_id = $transaction_id;
            $user_packages_subcription->auger_txn_id = $auger_transaction_id;
            $user_packages_subcription->token_value = getTokenMetricsValues();
            $user_packages_subcription->payment_mode = "4";
            $user_packages_subcription->start_date = $package_start_date;
            $user_packages_subcription->validity = $validity;
            $user_packages_subcription->status = "1";
            $user_packages_subcription->current_subscriptions_log_id = $user_packages_subcription_log->id;
            $user_packages_subcription->end_date = addMonthsToDate($package_start_date, $validity);
            $user_packages_subcription->promo_code = $promocode ? $promocode : null;
            $user_packages_subcription->save();
            $this->subscribeServiceBypackage($user, $package, $package_start_date, $validity, $transaction_id);
        }
        if (!$isFirstPackageSubscription)
            makeAffliateTransactions($user->id, $price);
        return $user_packages_subcription_log;
    }
    public function subscribeServiceBypackage($user, $package, $package_start_date, $validity, $transaction_id)
    {
        foreach ($package['services'] as $temp_service) {
            $service_row = $temp_service['service'];
            $plan_row = $temp_service['plan'];
            $end_date = addMonthsToDate($package_start_date, $validity);

            $user_service_row = UserServiceSubscription::where("service_id", $service_row['id'])->where("user_id", $user->id)->first();
            if ($user_service_row && $user_service_row->toArray()) {
                $serviceEndDate = Carbon::parse($user_service_row->end_date);
                $packageServiceEndDate = Carbon::parse($end_date);
                $end_date = $packageServiceEndDate->lessThan($serviceEndDate) ? $user_service_row->end_date : $end_date;
            } else {
                $user_service_row = new UserServiceSubscription();
                $user_service_row->service_id = $service_row['id'];
                $user_service_row->user_id = $user->id;
            }
            $user_service_row->package_id = $package['id'];
            $user_service_row->plan_id = $plan_row['id'];
            $user_service_row->service_plan_data = $plan_row;
            $user_service_row->start_date = $package_start_date;
            $user_service_row->end_date = $end_date;
            $user_service_row->txn_id = $transaction_id;
            $user_service_row->validity = $validity;
            $user_service_row->payment_mode = "4";
            $user_service_row->status = "1";
            $user_service_row->save();
        }
    }
    public function getSubscribedPackage(Request $request)
    {
        $user = $request->attributes->get('user');
        $subscribedPackageRow = UserPackageSubscription::where("user_id", $user->id)->whereDate('end_date', '>', Carbon::now())->where("status", "1")->first();
        $subscribedPackage = null;
        if ($subscribedPackageRow && $subscribedPackageRow->toArray()) {
            $subscribedPackage = $subscribedPackageRow;
            $subscribedPackage['package_data'] = json_decode($subscribedPackageRow['package_data'], true);
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Subscribed package retrieved', 'toast' => true, 'data' => ["package" => $subscribedPackage]]);
        } else {
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Package not subscribed yet', 'toast' => true]);
        }
    }
    public function getInvoice(GetInvoiceRequest $request)
    {
        try {
            $invoice_id = $request->invoice_id;
            $user = $request->attributes->get('user');
            $userPackageLog = UserPackageSubscriptionLog::where("id", $invoice_id)->where('user_id', $user->id)->first();

            if ($userPackageLog) {
                $userPackageLog = $userPackageLog->toArray();
                $subscribedPackage = json_decode($userPackageLog['package_data'], true);
                $data['username'] = $user->username;
                $data['email'] = $user->email;
                $data['date'] = date("Y-m-d", strtotime($userPackageLog['created_at']));
                $data['txn_id'] = $invoice_id;
                $data['services'] = [];
                $tempServices = $subscribedPackage['services'];
                foreach ($tempServices as $service) {

                    $tempService['qty'] = null;
                    $tempService['price'] = $service['plan']['monthly_price'];
                    if ($userPackageLog['validity'] === "1") {
                        $tempService['price'] = $service['plan']['monthly_price'];
                    } else if ($userPackageLog['validity'] === "3") {
                        $tempService['price'] = $service['plan']['quarterly_price'];
                    } else if ($userPackageLog['validity'] === "12") {
                        $tempService['price'] = $service['plan']['yearly_price'];
                    }
                    $tempService['name'] = $service['service']['name'];
                    $tempService['plan_name'] = $service['plan']['name'];
                    $data['services'][] = $tempService;
                }
                $data['end_date'] = $userPackageLog['end_date'];
                $data['price'] = $userPackageLog['price'];
                $data['auger_price'] = $userPackageLog['auger_price'];
                $data['validity'] = $userPackageLog['validity'];
                $data['package_name'] = $subscribedPackage['name'];
                $data['promocode'] = null;

                if ($userPackageLog['promo_code']) {
                    $tempArr = array();
                    $promoCodeData = PromoCode::where('promo_code', $userPackageLog['promo_code'])->first();
                    if ($promoCodeData && $promoCodeData->toArray()) {
                        $tempArr['promo_code'] = $promoCodeData->promo_code;
                        $tempArr['description'] = $promoCodeData->description;
                        $data['promocode'] = $tempArr;
                    }
                }
                $description = getInvoiceNotes("Package");
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Invoice retrieved successfully', 'toast' => true, 'data' => ["invoice" => $data, "description" => $description]]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Invoice not found', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('subscription Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function getServices(GetServiceListRequest $request)
    {
        try {
            $user = $request->attributes->get('user');
            $limit = isset($request->limit) ? $request->limit : 20;
            $page = isset($request->page) && $request->page ? $request->page : 1;
            $offset = ($page - 1) * $limit;
            $allServices = Service::where("status", '0')->where("is_external_service", '0')->limit($limit)->select(['id', 'name', 'key', 'description', 'category', 'logo', 'icon', 'bs_icon', 'thumbnail', 'link', 'sequence_no', 'status', 'is_external_app', 'is_free', 'trial_period'])->offset($offset)->get();

            if ($allServices && $allServices->toArray()) {
                $services = [];
                foreach ($allServices->toArray() as $service) {
                    $service['user_service_status'] = getUsersServiceStatus($user, $service, $request);
                    $services[] = $service;
                }
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Services data fetched', 'toast' => true], ["services" => $services]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No services available', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('getServices Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function getServiceDetail(GetServiceDetailsRequest $request)
    {
        try {
            $user = $request->attributes->get('user');
            $service_id = $request->service_id;
            $service = Service::where("id", $service_id)->select(['id', 'name', 'key', 'description', 'category', 'logo', 'icon', 'bs_icon', 'thumbnail', 'link', 'sequence_no', 'status', 'is_external_app', 'is_free', 'trial_period'])->with("servicePlans")->first()->toArray();

            $plans = $service['service_plans'];

            if ($plans) {
                $planList = array();
                foreach ($plans as $p) {
                    $p['is_subscribed'] = false;
                    $user_service_plan_subscription = UserServiceSubscription::where("service_id", $service['id'])->where("user_id", $user->id)->where("plan_id", $p['id'])->where("status", "1")->select(['end_date'])->first();
                    if ($user_service_plan_subscription) {
                        $serviceEndDate = Carbon::parse($user_service_plan_subscription->end_date);
                        $now = Carbon::now();
                        $p['is_subscribed'] = $now->lessThan($serviceEndDate) ? true : false;
                    }
                    $planList[] = $p;
                }
                $service['plans'] = $planList;
            } else {
                $service['plans'] = null;
            }
            $alert_data = getSubscriptionAlert($user, "service", $service_id);
            $subscribedData = getUsersServiceStatus($user, $service, $request);
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Service data fetched', 'toast' => true], ["services" => $service, "subscribedData" => $subscribedData, "alert_data" => $alert_data]);
        } catch (\Exception $e) {
            Log::info('getServiceDetail Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function subscribeService(SubscribeServiceRequest $request)
    {
        DB::beginTransaction();
        try {
            $service_id = $request->service_id;
            $plan_id = $request->plan_id;
            $validity = $request->validity;
            $user = $request->attributes->get('user');
            $service_start_date = isset($request->service_start_date) ? $request->service_start_date : date('Y-m-d');
            $upComingPlan = false;

            $service = Service::where("id", $service_id)->first()->toArray();
            $plan = ServicePlan::where("id", $plan_id)->where('service_id', $service_id)->first();

            $user_service_subscription_row = UserServiceSubscription::where("service_id", $service_id)->where("user_id", $user->id)->where('status', "1")->first();
            if ($user_service_subscription_row && $user_service_subscription_row->toArray()) {
                $serviceEndDate = Carbon::parse($user_service_subscription_row->end_date);
                $now = Carbon::now();
                $service_start_date = $serviceEndDate->lessThan($now) ? date('Y-m-d') : $service_start_date;
                $upComingPlan = $now->lessThan($service_start_date);
            }


            $price = $plan['monthly_price'];
            if ($validity === "3") {
                $price = $plan['quarterly_price'];
            } else if ($validity === "12") {
                $price = $plan['yearly_price'];
            }

            // check balance
            $planTokenPrice = $price / getTokenMetricsValues();
            $auger_tokens = $planTokenPrice * (config('app.auger_fee') / 100);
            if (!balanceValidations($user->id, $planTokenPrice))
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Insufficient token balance', 'toast' => true]);


            $admin_user = User::where("role_id", "2")->first();
            if (!$admin_user)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Admin user not found', 'toast' => true]);
            $perticulars = ucfirst($service['name']) . " service purchase";
            $lastTokenTransactionLog = TokenTransactionLog::orderBy('id', 'desc')->first();
            $transaction_id = makeTransaction($user, $admin_user, $planTokenPrice, $perticulars, "4", "3", $lastTokenTransactionLog);
            $auger_transaction_id = makeTransaction($user, $admin_user, $auger_tokens, "Auger Fee " . $perticulars, "5", "3", $lastTokenTransactionLog, $transaction_id);


            $user_service_row = $this->makeServiceSubscriptionEntry($user, $service, $plan, $service_start_date, $validity, $transaction_id, $auger_transaction_id, $upComingPlan);


            // add alert to notification
            addNotification($user->id, $user->id, ucfirst($service['name']) . "-" . ucfirst($plan['name']) . " plan subscription", "You have subscribed service " . $service['name'] . " with " . $plan['name'] . " plan and price is $" . $price, $user_service_row->id, "1", "/subscriptions/service-plan/invoice/" . $service['id'] . "/" . $plan['id'] . "/print-invoice/" . $user_service_row->id);

            DB::commit();

            // send email for transaction
            $this->sendServiceSubscriptionEmail($user, $user_service_row, $price, $validity, $plan, $service, $transaction_id, $auger_transaction_id);

            // add calendar event
            $calendarLink = config("app.account_url") . "subscriptions/service-plan/invoice/" . $service['id'] . "/" . $plan['id'] . "/print-invoice/" . $user_service_row->id;
            $end_date = addMonthsToDate(date("Y-m-d"), $validity);
            addSubscriptionEvent($user->id, ucfirst($service['name']) . " Service Subscription", date("Y-m-d"), $end_date, null, $calendarLink);

            $updatedUser = User::where('id', $user->id)->first();

            $user_service_data = $user_service_row->toArray();
            $keysToUnset = [
                'created_at',
                'updated_at',
                'deleted_at',
            ];
            $user_service_data = array_diff_key($user_service_data, array_flip($keysToUnset));

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Service subscribed successfully', 'toast' => true, 'data' => ["invoice_id" => $user_service_row->id, "service" => $user_service_data, "account_tokens" => $updatedUser->account_tokens]]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Subscription Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function makeServiceSubscriptionEntry($user, $service, $plan, $service_start_date, $validity, $transaction_id, $auger_transaction_id, $upComingPlan)
    {
        $end_date = addMonthsTodate($service_start_date, $validity);

        $user_service_row_log = new UserServiceSubscriptionLog();
        $user_service_row_log->service_id = $service['id'];
        $user_service_row_log->user_id = $user->id;
        $user_service_row_log->package_id = null;
        $user_service_row_log->plan_id = $plan['id'];
        $user_service_row_log->service_plan_data = json_encode($plan);
        $user_service_row_log->start_date = $service_start_date;
        $user_service_row_log->end_date = $end_date;
        $user_service_row_log->txn_id = $transaction_id;
        $user_service_row_log->auger_txn_id = $auger_transaction_id;
        $user_service_row_log->payment_mode = "4";
        $user_service_row_log->status = $upComingPlan ? "0" : "1";
        $user_service_row_log->validity = $validity;
        $user_service_row_log->save();

        $user_service_row = UserServiceSubscription::where("service_id", $service['id'])->where("user_id", $user->id)->first();
        if ($user_service_row && $user_service_row->toArray()) {
            $serviceEndDate = Carbon::parse($user_service_row->end_date);
            $purchasedServiceEndDate = Carbon::parse($end_date);
            $end_date = $purchasedServiceEndDate->lessThan($serviceEndDate) ? $user_service_row->end_date : $end_date;
        } else {
            $user_service_row = new UserServiceSubscription();
            $user_service_row->service_id = $service['id'];
            $user_service_row->user_id = $user->id;
        }
        $user_service_row->plan_id = $plan['id'];
        $user_service_row->package_id = null;
        $user_service_row->service_plan_data = json_encode($plan);
        $user_service_row->start_date = $service_start_date;
        $user_service_row->end_date = $end_date;
        $user_service_row->txn_id = $transaction_id;
        $user_service_row->auger_txn_id = $auger_transaction_id;
        $user_service_row->payment_mode = "4";
        $user_service_row->status = "1";
        $user_service_row->validity = $validity;
        $user_service_row->current_subscriptions_log_id = $user_service_row_log->id;
        if (!$upComingPlan)
            $user_service_row->save();

        $user_service_row_log->end_date = $end_date;
        $user_service_row_log->save();

        return $user_service_row_log;
    }
    public function sendServiceSubscriptionEmail($user, $user_service_row, $price, $validity, $plan, $service, $transaction_id, $auger_transaction_id)
    {
        $pdfData = [ // Your dynamic data for the PDF
            'page_title' => 'Service Subscription',
            'logo' => asset('assets/images/logo/logo-dark.png'),
            'username' => $user->username,
            'email' => $user->email,
            'invoice_id' => $user_service_row->id,
            'invoice_date' => date("Y-m-d"),
            'auger_fee' => $price * (config('app.auger_fee') / 100),
            'grand_total' => $price,
        ];

        $pdfData['services'] = [];


        $tempService['qty'] = 1;
        $tempService['price'] = $plan['monthly_price'];
        if ($validity === "1") {
            $tempService['price'] = $plan['monthly_price'];
        } else if ($validity === "3") {
            $tempService['price'] = $plan['quarterly_price'];
        } else if ($validity === "12") {
            $tempService['price'] = $plan['yearly_price'];
        }
        $tempService['service_name'] = $service['name'];
        $tempService['service_id'] = $service['id'];
        $tempService['plan_name'] = $plan['name'];
        $pdfData['services'][] = $tempService;
        $txnLog = TokenTransactionLog::where("id", $transaction_id)->first();
        $pdf = Pdf::loadView('pdf-template.service-invoice', $pdfData);
        $message = "Thank you for service subscription [" . $service['name'] . "], Explore our web app to more information";

        sendTransactionMail($txnLog, $pdf, $message, true, true);
        $augerTxnLog = TokenTransactionLog::where("id", $auger_transaction_id)->first();
        sendTransactionMail($augerTxnLog, null, null, true, true);
    }
    public function getServiceInvoice(GetServiceInvoiceRequest $request)
    {
        try {
            $invoice_id = $request->invoice_id;
            $user = $request->attributes->get('user');
            $userService = UserServiceSubscriptionLog::where("id", $invoice_id)->where('user_id', $user->id)->with("service")->first();

            if ($userService) {
                $userService = $userService->toArray();
                $subscribedService = $userService['service'];
                $subscribedPlan = json_decode($userService['service_plan_data'], true);
                $data['username'] = $user->username;
                $data['email'] = $user->email;
                $data['date'] = date("Y-m-d", strtotime($userService['created_at']));
                $data['txn_id'] = $invoice_id;
                $data['services'] = [];

                $tempService['qty'] = null;
                $tempService['price'] = $subscribedPlan['monthly_price'];
                if ($userService['validity'] === "1") {
                    $tempService['price'] = $subscribedPlan['monthly_price'];
                } else if ($userService['validity'] === "3") {
                    $tempService['price'] = $subscribedPlan['quarterly_price'];
                } else if ($userService['validity'] === "12") {
                    $tempService['price'] = $subscribedPlan['yearly_price'];
                }
                $tempService['name'] = $subscribedService['name'];
                $tempService['plan_name'] = $subscribedPlan['name'];


                $data['services'][] = $tempService;
                $data['end_date'] = $userService['end_date'];
                $data['price'] = $tempService['price'];
                $data['auger_price'] = $tempService['price'] * (config('app.auger_fee') / 100);
                $data['auger_price'] = (float) number_format($data['auger_price'], 2, ".", "");
                $data['validity'] = $userService['validity'];
                $data['package_name'] = "";
                $description = getInvoiceNotes("Service");
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Invoice retrieved successfully', 'toast' => true, 'data' => ["invoice" => $data, "description" => $description]]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Invoice not found', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('getServiceInvoice Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function getPlan(GetPlanRequest $request)
    {
        try {
            $user = $request->attributes->get('user');
            $service_id = $request->service_id;
            $plan_id = $request->plan_id;
            $is_subscribed = false;
            $subscribed_price = null;
            $subscribed_auger_price = null;
            $plan = ServicePlan::where('service_id', $service_id)->where('id', $plan_id)->select(['id', 'name', 'service_id', 'features', 'monthly_price', 'quarterly_price', 'yearly_price', 'status', 'styles', 'logo', 'icon'])->with('service')->first();
            $service = $plan->service;
            if ($plan && $plan->toArray()) {

                $plan = $plan->toArray();
                $service = $service->toArray();
                $subscribedData = getUsersServiceStatus($user, $service, $request);

                if ($subscribedData['plan_id'] == $plan_id) {
                    $is_subscribed = $subscribedData['current_subscribed'];
                    $subscribed_price = $subscribedData['price'];
                    $subscribed_auger_price = $subscribedData['auger_price'];
                }
                $plan['service_name'] = $service['name'];
                $plan['monthly_price'] = ["price" => $plan['monthly_price'], "auger_price" => $auger_fee = (float) config('app.auger_fee') * $plan['monthly_price'] / 100];
                $plan['quarterly_price'] = ["price" => $plan['quarterly_price'], "auger_price" => $auger_fee = (float) config('app.auger_fee') * $plan['quarterly_price'] / 100];
                $plan['yearly_price'] = ["price" => $plan['yearly_price'], "auger_price" => $auger_fee = (float) config('app.auger_fee') * $plan['yearly_price'] / 100];
                $token_value = getTokenMetricsValues();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Plan details retrieved', 'toast' => true], ['plan' => $plan, "account_tokens" => $user->account_tokens, "token_value" => $token_value, "subscribed_price" => $subscribed_price, "subscribed_auger_price" => $subscribed_auger_price, "is_subscribed" => $is_subscribed]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Plan not found', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('getPlan Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function getInvoiceList(Request $request)
    {
        try {
            $statusArray = ["Upcoming", "Active", "Expired"];
            $user = $request->attributes->get('user');
            $tempInvoiceList = UserPackageSubscriptionLog::where('user_id', $user->id)->orderBy("id", "desc")->get();
            $invoiceList = [];
            if ($tempInvoiceList && $tempInvoiceList->toArray()) {
                $tempInvoiceList = $tempInvoiceList->toArray();
                foreach ($tempInvoiceList as $invoice) {
                    $temp = [];
                    $temp['id'] = $invoice['id'];
                    $temp['invoiceId'] = $invoice['id'];
                    $plan = json_decode($invoice['package_data'], true);
                    $temp['plan'] = $plan['name'];
                    $temp['plan_id'] = $plan['id'];
                    $temp['package'] = $plan['name'];
                    $temp['price'] = $invoice['price'];
                    $temp['auger_price'] = $invoice['auger_price'];
                    $temp['start_date'] = $invoice['start_date'];
                    $temp['end_date'] = $invoice['end_date'];
                    $temp['date'] = $invoice['created_at'];

                    $temp['status'] = isset($statusArray[$invoice['status']]) ? $statusArray[$invoice['status']] : "";
                    $temp['validity_status'] = isset($statusArray[$invoice['status']]) ? $statusArray[$invoice['status']] : "";
                    $temp['txn_id'] = $invoice["txn_id"];
                    $temp['type'] = "package";
                    $temp['service_id'] = null;
                    $invoiceList[] = $temp;
                }
            }
            $tempServiceInvoiceList = UserServiceSubscriptionLog::where('user_id', $user->id)->whereNull('package_id')->get();

            if ($tempServiceInvoiceList && $tempServiceInvoiceList->toArray()) {
                $tempServiceInvoiceList = $tempServiceInvoiceList->toArray();
                foreach ($tempServiceInvoiceList as $invoice) {
                    $temp = [];
                    $service = Service::where('id', $invoice['service_id'])->first();
                    $package = Package::where('id', $invoice['package_id'])->first();
                    $temp['id'] = $invoice['id'];
                    $temp['invoiceId'] = $invoice['id'];
                    $plan = json_decode($invoice['service_plan_data'], true);
                    $temp['plan'] = $service->name . " " . $plan['name'];
                    $temp['plan_id'] = $plan['id'];
                    $temp['price'] = $plan['monthly_price'];
                    if ($invoice["validity"] == "12") {
                        $temp['price'] = $plan['yearly_price'];
                    } else if ($invoice["validity"] == "3") {
                        $temp['price'] = $plan['quarterly_price'];
                    }
                    $temp['status'] = isset($statusArray[$invoice['status']]) ? $statusArray[$invoice['status']] : "";
                    $temp['validity_status'] = isset($statusArray[$invoice['status']]) ? $statusArray[$invoice['status']] : "";
                    $temp['auger_price'] = $temp['price'] * (config('app.auger_fee') / 100);
                    $temp['date'] = $invoice['created_at'];
                    $temp['txn_id'] = $invoice["txn_id"];
                    $temp['type'] = "service";
                    $temp['package'] = null;
                    $temp['service_id'] = $invoice['service_id'];
                    $invoiceList[] = $temp;
                }
            }
            $currentInvoiceSummary['current_package'] = getSubscribedPackageInvoice($user->id);
            $currentInvoiceSummary['is_package'] = getSubscribedPackageInvoice($user->id) ? true : false;
            $currentInvoiceSummary['is_upcoming'] = getSubscribedPackageInvoice($user->id, false) ? true : false;
            $currentInvoiceSummary['upcoming_package'] = getSubscribedPackageInvoice($user->id, false);
            $currentInvoiceSummary['current_service'] = getSubscribedServices($user->id);
            if ($invoiceList)
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Invoice list found', 'toast' => true], ['list' => $invoiceList, "currentInvoiceSummary" => $currentInvoiceSummary]);
            else
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No nvoice list found', 'toast' => true]);
        } catch (\Exception $e) {
            Log::info('getInvoiceList Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function validatePromocode(PromoCodeValidateRequest $request)
    {
        try {
            $user = $request->attributes->get('user');
            $today = date('Y-m-d');
            $promocode = $request->promocode;
            $promocode_data = PromoCode::whereRaw("BINARY promo_code = ?", [$promocode])->where("status", "1")->where('end_date', '>=', $today)->first();

            if ($promocode_data && $promocode_data->toArray()) {
                $promocode_data = $promocode_data->toArray();
                if ($promocode_data['max_users'] > $promocode_data['count']) {
                    unset($promocode_data['created_at']);
                    unset($promocode_data['deleted_at']);
                    unset($promocode_data['updated_at']);
                    unset($promocode_data['count']);
                    unset($promocode_data['max_users']);

                    $promCodeUsed = UserPackageSubscriptionLog::where("promo_code", $promocode)->where("user_id", $user->id)->count();
                    if ($promCodeUsed) {
                        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Promcode already used', 'toast' => true], ['used' => true, "valid" => true]);
                    }
                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Valid promo code', 'toast' => true], ['promocode' => $promocode_data, 'used' => false, "valid" => true]);
                }
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Invalid promcode', 'toast' => true], ['used' => false, "valid" => false]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Invalid promcode', 'toast' => true], ['used' => false, "valid" => false]);
            }
        } catch (\Exception $e) {
            Log::info('validatePromocode Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function downgradePackage(DowngradePackageRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = $request->attributes->get('user');
            $newValidity = $request->validity;
            $user_package_subscription_row = UserPackageSubscription::where("user_id", $user->id)->where("status", "1")->first();
            if ($user_package_subscription_row && $user_package_subscription_row->toArray()) {
                $packageEndDate = Carbon::parse($user_package_subscription_row->end_date);
                $now = Carbon::now();
                $is_expired_package = $packageEndDate->lessThan($now) ? true : false;
                if ($is_expired_package) {
                    return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Package subscription expired', 'toast' => true]);
                }
                $currentPackageId = $user_package_subscription_row->package_id;
                $currentPackageLogId = $user_package_subscription_row->current_subscriptions_log_id;

                $currentPackageLog = UserPackageSubscriptionLog::where('id', $currentPackageLogId)->where('user_id', $user->id)->where('status', '1')->first();
                if (!$currentPackageLog) {
                    return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Package log not found', 'toast' => true]);
                }

                $currentPackageData = $user_package_subscription_row->package_data ? json_decode($user_package_subscription_row->package_data, true) : [];

                $newPackage = Package::where('id', $request->package_id)->first();
                $newPackagePrice = $newPackage->monthly_price;
                if ($newValidity === "1") {
                    $newPackagePrice = $newPackage->monthly_price;
                } else if ($newValidity === "3") {
                    $newPackagePrice = $newPackage->quarterly_price;
                } else if ($newValidity === "12") {
                    $newPackagePrice = $newPackage->yearly_price;
                }

                if ($user_package_subscription_row->price < $newPackagePrice)
                    return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Higher price for new package', 'toast' => true]);

                // storage check 
                $existingStorageServiceSubscribed = false;
                $storageServiceId = config('app.storage_service_id');
                if ($currentPackageData && isset($currentPackageData['services']) && is_array($currentPackageData['services'])) {
                    foreach ($currentPackageData['services'] as $row) {
                        if ($row['service']['id'] == $storageServiceId) {
                            $existingStorageServiceSubscribed = true;
                            break;
                        }
                    }
                }
                $newServices = $newPackage->services ? json_decode($newPackage->services, true) : [];
                if (array_key_exists($storageServiceId, $newServices) && $existingStorageServiceSubscribed) {
                    $newStoragePlanId = $newServices[$storageServiceId];
                    $storageStatusCheck = checkStorageForDowngrade($user, $newStoragePlanId);
                    if (!$storageStatusCheck['status']) {
                        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => $storageStatusCheck['message'], 'toast' => true]);
                    }
                }
                $auger_fee = (float) config('app.auger_fee');

                $user_package_subscription_row->package_id = $request->package_id;
                $user_package_subscription_row->package_data = json_encode(getPackageDetails($newPackage->toArray()));
                $user_package_subscription_row->downgrade = json_encode($user_package_subscription_row->toArray());
                $user_package_subscription_row->price = $newPackagePrice;
                $user_package_subscription_row->auger_price = $newPackagePrice * $auger_fee / 100;
                $user_package_subscription_row->validity = $newValidity;
                if ($newValidity < $user_package_subscription_row->validity) {
                    $currentEndDate = date('Y-m-d', strtotime($user_package_subscription_row->end_date));
                    $newEndDate = date('Y-m-d', strtotime("+$newValidity month"));
                    $smallestDate = ($currentEndDate < $newEndDate) ? $currentEndDate : $newEndDate;
                    $user_package_subscription_row->end_date = date('Y-m-d', strtotime($smallestDate));
                }
                $user_package_subscription_row->save();

                $currentPackageLog->package_id = $request->package_id;
                $currentPackageLog->package_data = json_encode(getPackageDetails($newPackage->toArray()));
                $currentPackageLog->downgrade = json_encode($user_package_subscription_row->toArray());
                $currentPackageLog->price = $newPackagePrice;
                $currentPackageLog->auger_price = $newPackagePrice * $auger_fee / 100;
                $currentPackageLog->validity = $newValidity;
                $currentPackageLog->end_date = $user_package_subscription_row->end_date;
                $currentPackageLog->save();

                $newPackageArr = getPackageDetails($newPackage->toArray());
                foreach ($newPackageArr['services'] as $temp_service) {
                    $service_row = $temp_service['service'];
                    $plan_row = $temp_service['plan'];

                    $user_service_row = UserServiceSubscription::where("service_id", $service_row['id'])->where("user_id", $user->id)->first();
                    if (!$user_service_row) {
                        $user_service_row = new UserServiceSubscription();
                        $user_service_row->service_id = $service_row['id'];
                        $user_service_row->user_id = $user->id;
                    }
                    $user_service_row->package_id = $newPackageArr['id'];
                    $user_service_row->plan_id = $plan_row['id'];
                    $user_service_row->service_plan_data = $plan_row;
                    $user_service_row->start_date = $user_package_subscription_row->start_date;
                    $user_service_row->end_date = $user_package_subscription_row->end_date;
                    $user_service_row->txn_id = $user_package_subscription_row->txn_id;
                    $user_service_row->auger_txn_id = $user_package_subscription_row->auger_txn_id;
                    $user_service_row->payment_mode = "4";
                    $user_service_row->save();
                }
                DB::commit();

                $mailData["view"] = "mail-templates.downgrade-subscription";
                $mailData["title"] = "Package Subscription Downgrade";
                $mailData['projectName'] = config('app.app_name');
                $mailData['username'] = $user->username;
                $mailData['subject'] = "Package Subscription Downgrade";
                $mailData['old'] = $currentPackageData['name'];
                $mailData['new'] = $newPackage->name;
                $mailData['supportMail'] = config('app.support_mail');
                $mailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
                Mail::to($user->email)->send(new SendMail($mailData, $mailData['view']));


                $temp_user = User::where('id', $user->id)->first();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Package subscription downgraded successfully', 'toast' => true, 'data' => ["invoice_id" => $currentPackageLog->id, "package" => $user_package_subscription_row->toArray(), "account_tokens" => $temp_user->account_tokens]]);
            } else {
                $user->package_subscription_id = null;
                $user->save();
                DB::commit();
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Package not subscribed yet', 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('downgradePackage Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function downgradeService(DowngradeServiceSubscriptionRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = $request->attributes->get('user');
            $service_id = $request->service_id;
            $plan_id = $request->plan_id;
            $newValidity = $request->validity;

            $service = Service::where("id", $service_id)->first()->toArray();
            $newPlan = ServicePlan::where("id", $plan_id)->where('service_id', $service_id)->first();
            if (!$newPlan || !$newPlan->toArray()) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid plan for selected service', 'toast' => true]);
            }
            $serviceSubscribedQuery = UserServiceSubscription::query();
            $today = date('Y-m-d');
            $isServiceSubscribed = $serviceSubscribedQuery->where('service_id', $service_id)->where('user_id', $user->id)->where('end_date', '>=', $today)->where("status", "1")->count();
            if (!$isServiceSubscribed) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Service not subscribed yet', 'toast' => true]);
            }
            $serviceSubscribptionRow = $serviceSubscribedQuery->where('service_id', $service_id)->where('user_id', $user->id)->first();
            $existingPlanRow = ServicePlan::where("id", $serviceSubscribptionRow->plan_id)->first();

            $storageServiceId = config('app.storage_service_id');
            if ($service_id == $storageServiceId) {
                $storageStatusCheck = checkStorageForDowngrade($user, $newPlan->id);
                if (!$storageStatusCheck['status']) {
                    return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => $storageStatusCheck['message'], 'toast' => true]);
                }
            }
            $existingPrice = $existingPlanRow->monthly_price;
            if ($serviceSubscribptionRow->validity === "1") {
                $existingPrice = $existingPlanRow->monthly_price;
            } else if ($serviceSubscribptionRow->validity === "3") {
                $existingPrice = $existingPlanRow->quarterly_price;
            } else if ($serviceSubscribptionRow->validity === "12") {
                $existingPrice = $existingPlanRow->yearly_price;
            }

            $newPrice = $newPlan->monthly_price;
            if ($newValidity === "1") {
                $newPrice = $newPlan->monthly_price;
            } else if ($newValidity === "3") {
                $newPrice = $newPlan->quarterly_price;
            } else if ($newValidity === "12") {
                $newPrice = $newPlan->yearly_price;
            }
            if ($newPrice > $existingPrice) {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Higher price for new plan', 'toast' => true]);
            }
            $serviceSubscribptionRow->validity = $newValidity;
            if ($newValidity < $serviceSubscribptionRow->validity) {
                $currentEndDate = date('Y-m-d', strtotime($serviceSubscribptionRow->end_date));
                $newEndDate = date('Y-m-d', strtotime("+$newValidity month"));
                $smallestDate = ($currentEndDate < $newEndDate) ? $currentEndDate : $newEndDate;
                $serviceSubscribptionRow->end_date = date('Y-m-d', strtotime($smallestDate));
            }

            $serviceSubscribptionRow->plan_id = $newPlan->id;
            $serviceSubscribptionRow->service_plan_data = json_encode($newPlan->toArray());
            $serviceSubscribptionRow->save();

            if ($serviceSubscribptionRow->current_subscriptions_log_id) {
                $serviceSubscribedLogQuery = UserServiceSubscriptionLog::query();
                $serviceSubscribedLog = $serviceSubscribedLogQuery->where('id', $serviceSubscribptionRow->current_subscriptions_log_id)->first();
                if ($serviceSubscribedLog && $serviceSubscribedLog->toArray()) {
                    $serviceSubscribptionRow->validity = $newValidity;
                    $serviceSubscribedLog->end_date = $serviceSubscribptionRow->end_date;
                    $serviceSubscribedLog->plan_id = $newPlan->id;
                    $serviceSubscribedLog->package_id = null;
                    $serviceSubscribedLog->service_plan_data = json_encode($newPlan->toArray());
                    $serviceSubscribedLog->save();
                }
            }
            DB::commit();

            $mailData["view"] = "mail-templates.downgrade-subscription";
            $mailData["title"] = $service['name'] . " Service Subscription Downgrade";
            $mailData['projectName'] = config('app.app_name');
            $mailData['subject'] = $service['name'] . " Service Subscription Downgrade";
            $mailData['username'] = $user->username;
            $mailData['old'] = $existingPlanRow->name;
            $mailData['new'] = $newPlan->name;
            $mailData['supportMail'] = config('app.support_mail');
            $mailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
            Mail::to($user->email)->send(new SendMail($mailData, $mailData['view']));

            $updatedUser = User::where('id', $user->id)->first();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Service downgraded successfully', 'toast' => true, 'data' => ["account_tokens" => $updatedUser->account_tokens]]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('downgradeService Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function expireSubscription()
    {
        try {
            DB::beginTransaction();
            $today = date('Y-m-d');

            $expiredPackagesLog = UserPackageSubscriptionLog::where('end_date', '<', $today)->get();
            if ($expiredPackagesLog->isNotEmpty()) {
                $expiredPackagesLog->each(function ($packageSubscription) {
                    $packageSubscriptionRow = UserPackageSubscription::where('current_subscriptions_log_id', $packageSubscription->id)->first();

                    if ($packageSubscriptionRow && $packageSubscriptionRow->toArray()) {
                        $package_data = $packageSubscriptionRow->package_data && json_decode($packageSubscriptionRow->package_data, true) ? json_decode($packageSubscriptionRow->package_data, true) : [];
                        $packageName = $package_data && $package_data['name'] ? $package_data['name'] : null;
                        $moduleName = $package_data && $package_data['module_name'] ? $package_data['module_name'] : 'Module';
                        $endDate = $packageSubscription->end_date;

                        $title = "Your " . $moduleName . " " . $packageName . " subscription ends " . $endDate . ". Renew now.";

                        addNotification($packageSubscriptionRow->user_id, $packageSubscriptionRow->user_id, $title, $title, $packageSubscription->id, "1", "/subscriptions?tab=invoiceSubscriptions");

                        $packageSubscriptionRow->status = "0";
                        $packageSubscriptionRow->save();
                    }

                    $packageSubscription->status = "2";
                    $packageSubscription->save();
                });
            }

            $expiredServices = UserServiceSubscription::where('end_date', '<', $today)->get();
            if ($expiredServices->isNotEmpty()) {
                $expiredServices->each(function ($serviceSubscription) {
                    $serviceSubscriptionLogRow = UserServiceSubscriptionLog::where('id', $serviceSubscription->current_subscriptions_log_id)->first();

                    if ($serviceSubscriptionLogRow && $serviceSubscriptionLogRow->toArray()) {
                        $serviceSubscriptionLogRow->status = "2";
                        $serviceSubscriptionLogRow->save();
                    }

                    $serviceSubscription->status = "0";
                    $serviceSubscription->save();

                    $serviceDetail = Service::where('id', $serviceSubscription->service_id)->first();
                    $serviceName = $serviceDetail ? $serviceDetail->name : 'Service';
                    $endDate = $serviceSubscription->end_date;
                    $msg = "Your " . $serviceName . " subscription ends " . $endDate . ". Renew now.";

                    addNotification($serviceSubscription->user_id, $serviceSubscription->user_id, $msg, $msg, $serviceSubscription->id, "1", "/subscriptions?tab=invoiceSubscriptions");
                });
            }

            DB::commit();

            return generateResponse([
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Expire subscription in progress',
                'toast' => true,
                'data' => [
                    "expired_packages_count" => count($expiredPackagesLog->toArray()),
                    "expired_services_count" => count($expiredServices->toArray())
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('expireSubscription Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse([
                'type' => 'error',
                'code' => 500,
                'status' => false,
                'message' => 'Error while processing',
                'toast' => true
            ]);
        }
    }

    public function expireSubscriptionAlert(Request $request)
    {
        try {
            $today = date("Y-m-d"); // Get today's date and time
            $threeDaysLater = now()->addDays(3);
            $upcomingPackageExpiredSubscriptionsQuery = UserPackageSubscription::query()
                ->where("status", "1")->whereBetween('end_date', [$today, $threeDaysLater]);
            $upcomingPackageExpiredSubscriptions = $upcomingPackageExpiredSubscriptionsQuery->get();

            $upcomingServiceExpiredSubscriptionsQuery = UserServiceSubscription::query()->selectRaw('count(*) AS total_subscriptions, user_id')
                ->where("status", "1")->whereBetween('end_date', [$today, $threeDaysLater])->groupBy('user_id');
            $upcomingServiceExpiredSubscriptions = $upcomingServiceExpiredSubscriptionsQuery->get();

            if ($upcomingPackageExpiredSubscriptions && $upcomingPackageExpiredSubscriptions->toArray()) {
                $packageSubscriptionList = $upcomingPackageExpiredSubscriptions->toArray();
                foreach ($packageSubscriptionList as $packageSubscription) {

                    $title = "Package about to expire";
                    $packageData = Package::where("id", $packageSubscription['package_id'])->first();

                    if (isset($packageData->name)) {
                        $title = "Package " . $packageData->name . " about to expire in upcoming days";
                    }

                    addNotification($packageSubscription['user_id'], $packageSubscription['user_id'], $title, $title, $packageSubscription['id'], "1", "/subscriptions?tab=invoiceSubscriptions");
                }
            }
            if ($upcomingServiceExpiredSubscriptions && $upcomingServiceExpiredSubscriptions->toArray()) {
                $serviceSubscriptionList = $upcomingServiceExpiredSubscriptions->toArray();
                foreach ($serviceSubscriptionList as $serviceSubscription) {
                    if ($serviceSubscription['total_subscriptions'] == "1") {
                        $title = "Service about to expire";
                    } else {
                        $title = $serviceSubscription['total_subscriptions'] . " Services about to expire";
                    }
                    addNotification($serviceSubscription['user_id'], $serviceSubscription['user_id'], $title, $title, "0", "1", "/subscriptions?tab=invoiceSubscriptions");
                }
            }
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Sending alert in progress', 'toast' => true]);
        } catch (\Exception $e) {
            Log::info('expireSubscriptionAlert Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function activatePackageSubscription()
    {
        try {
            DB::beginTransaction();
            $today = date('Y-m-d');
            $todayTempSubscriptions = UserPackageSubscriptionLog::where('start_date', $today)->where("status", "0")->get();
            $todaysSubscriptionsUsers = [];
            $todaysSubscriptions = [];
            if ($todayTempSubscriptions->isNotEmpty()) {
                $todayTempSubscriptions = $todayTempSubscriptions->toArray();
                foreach ($todayTempSubscriptions as $tts) {
                    if (!in_array($tts['user_id'], $todaysSubscriptionsUsers))
                        $todaysSubscriptionsUsers[] = $tts['user_id'];
                    $todaysSubscriptions[$tts['user_id']][] = $tts['id'];
                }
            }
            if ($todaysSubscriptionsUsers) {
                foreach ($todaysSubscriptionsUsers as $tsu) {

                    // activate package subscription log
                    $tsuSubscription = UserPackageSubscriptionLog::where('start_date', $today)->where("user_id", $tsu)->orderBy("end_date", "desc")->first();
                    $tsuSubscription->status = "1";
                    $tsuSubscription->save();

                    // deactivate package subscription log which has same date but end date is smaller that above
                    $tsuOtherTodaysSubscription = UserPackageSubscriptionLog::where("user_id", $tsu)->orderBy("end_date", "desc")->whereNot("id", $tsuSubscription->id)->get();
                    if ($tsuOtherTodaysSubscription->isNotEmpty()) {
                        $tsuOtherTodaysSubscription->each(function ($tots) {
                            $tots->status = "2";
                            $tots->save();
                        });
                    }

                    // activate package subscription row 

                    $user_packages_subcription_count = UserPackageSubscription::where("user_id", $tsu)->count();
                    if (!$user_packages_subcription_count) {
                        $user_packages_subcription = new UserPackageSubscription();
                        $user_packages_subcription->user_id = $tsu;
                    } else {
                        $user_packages_subcription = UserPackageSubscription::where("user_id", $tsu)->first();
                    }
                    $user_packages_subcription->package_id = $tsuSubscription->package_id;
                    $user_packages_subcription->price = $tsuSubscription->price;
                    $user_packages_subcription->auger_price = $tsuSubscription->price * (config('app.auger_fee') / 100);
                    $user_packages_subcription->package_data = $tsuSubscription->package_data;
                    $user_packages_subcription->txn_id = $tsuSubscription->txn_id;
                    $user_packages_subcription->auger_txn_id = $tsuSubscription->auger_transaction_id;
                    $user_packages_subcription->token_value = $tsuSubscription->token_value;
                    $user_packages_subcription->payment_mode = "4";
                    $user_packages_subcription->start_date = $tsuSubscription->start_date;
                    $user_packages_subcription->validity = $tsuSubscription->validity;
                    $user_packages_subcription->status = "1";
                    $user_packages_subcription->current_subscriptions_log_id = $tsuSubscription->id;
                    $user_packages_subcription->end_date = $tsuSubscription->end_date;
                    $user_packages_subcription->promo_code = $tsuSubscription->promo_code;
                    $user_packages_subcription->save();

                    // activateNewPackageServices
                    $user = User::where('id', $tsu)->first();
                    $package = Package::where("id", $tsuSubscription->package_id)->first();
                    $package = getPackageDetails($package->toArray());
                    $this->subscribeServiceBypackage($user, $package, $tsuSubscription->start_date, $tsuSubscription->validity, $tsuSubscription->txn_id);

                    addNotification($user->id, $user->id, $package['name'] . " Package Activation", $package['name'] . " activated to your account", $tsuSubscription->id, "1", "/subscriptions/subscription-package-details/" . $package['id'] . "/print-invoice/" . $tsuSubscription->id);
                }
            }
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Activation subscription in progress', 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('activateSubscription Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function activateServiceSubscription()
    {
        try {
            DB::beginTransaction();
            $today = date('Y-m-d');
            $todayTempSubscriptions = UserServiceSubscriptionLog::where('start_date', $today)->where("status", "0")->get();
            $todaysSubscriptionsUsers = [];
            $todaysSubscriptions = [];
            if ($todayTempSubscriptions->isNotEmpty()) {
                $todayTempSubscriptions = $todayTempSubscriptions->toArray();
                foreach ($todayTempSubscriptions as $tts) {
                    if (!in_array($tts['user_id'], $todaysSubscriptionsUsers))
                        $todaysSubscriptionsUsers[] = $tts['user_id'];
                    $todaysSubscriptions[$tts['user_id']][] = $tts['id'];
                }
            }
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Activation subscription in progress', 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('activateSubscription Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function subscribeExternalService(ExternalServiceSubscriptionRequest $request)
    {
        try {
            DB::beginTransaction();
            $user = $request->attributes->get('user');
            $service = Service::where('id', $request->service_id)->first();
            $price = $request->price;
            $validity = $request->validity;

            // check balance
            $planTokenPrice = $price / getTokenMetricsValues();
            $auger_tokens = $planTokenPrice * (config('app.auger_fee') / 100);
            if (!balanceValidations($user->id, $planTokenPrice))
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Insufficient token balance', 'toast' => true]);


            $admin_user = User::where("role_id", "2")->orderBy("id", "asc")->first();
            if (!$admin_user)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Admin user not found', 'toast' => true]);
            $perticulars = ucfirst($service->name) . " service purchase";
            $lastTokenTransactionLog = TokenTransactionLog::orderBy('id', 'desc')->first();
            $transaction_id = makeTransaction($user, $admin_user, $planTokenPrice, $perticulars, "4", "3", $lastTokenTransactionLog);
            $auger_transaction_id = makeTransaction($user, $admin_user, $auger_tokens, "Auger Fee: " . $perticulars, "5", "3", $lastTokenTransactionLog, $transaction_id);
            DB::commit();

            $txnLog = TokenTransactionLog::where("id", $transaction_id)->first();
            sendTransactionMail(
                $txnLog,
                null,
                'Thank you for your subscription of external service ' . $service->name,
            );
            $augerTxnLog = TokenTransactionLog::where("id", $auger_transaction_id)->first();
            sendTransactionMail(
                $augerTxnLog
            );
            $calendarLink = $service->link;
            $end_date = addMonthsToDate(date("Y-m-d"), $validity);
            addSubscriptionEvent($user->id, ucfirst($service->name) . " Service Subscription", date("Y-m-d"), $end_date, null, $calendarLink);

            $updatedUser = User::where('id', $user->id)->first();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'External service subscribed successfully', 'toast' => true], ["account_tokens" => $updatedUser->account_tokens, "transaction_id" => $transaction_id, "auger_transaction_id" => $auger_transaction_id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('subscribeExternalService Error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
}
