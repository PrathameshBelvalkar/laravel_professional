<?php

namespace App\Http\Controllers\API\V1\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetDataRequest;
use App\Http\Requests\Wallet\ApproveCashRequest;
use App\Http\Requests\Wallet\BuyRequest;
use App\Http\Requests\Wallet\CashRequestList;
use App\Http\Requests\Wallet\MakeTokenRequest;
use App\Http\Requests\Wallet\PaypalSettingRequest;
use App\Http\Requests\Wallet\TokenRequestLog;
use App\Http\Requests\Wallet\TransferRequest;
use App\Mail\SendMail;
use App\Models\Payment;
use App\Models\TokenTransactionLog;
use App\Models\User;
use App\Models\Wallet\FinancialAccount;
use App\Models\Wallet\TokenRequest;
use App\Models\Wallet\TokenValueLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{

    public $txn_for = ['', 'Storage', 'Package', 'Service'];
    public $txn_type = ['', 'Buy tokens', 'Transfer', 'Withdraw', 'Consumed', 'Auger Fee', 'Reward'];
    public $txn_status = ['Pending', 'Success', 'Failed'];



    public function tokenLog()
    {
        try {
            DB::beginTransaction();
            $tokenMetrics = getTokenMetricsValues(null);
            $token_value_log = TokenValueLog::create($tokenMetrics);
            DB::commit();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Token value log created', 'toast' => true], ['token_value_log' => $token_value_log->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Wallet dashboard Error : ' . $e->getMessage() . " line no " . $e->getLine() . " " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function dashboard(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $stats = [];
            $stats['live_token_value'] = getTokenMetricsValues();
            $stats['balance'] = $user->account_tokens;
            $stats['auger_fee_percentage'] = config('app.auger_fee');
            $stats['network_commision'] = 0;
            $stats['social_network_points'] = 0;
            $stats['tokens_usd_value'] = $stats['balance'] * $stats['live_token_value'];

            $average24HrsTokenValue = TokenValueLog::where('created_at', '>=', Carbon::now()->subHours(24))
                ->avg('token_value');
            $stats['average_24_hrs_token_value'] = $average24HrsTokenValue ? $average24HrsTokenValue : 0;
            $noOf24HrsTransactionTraders = TokenTransactionLog::where('created_at', '>=', Carbon::now()->subHours(24))
                ->groupBy('sender_id')->pluck("sender_id");
            $average24HrsTokenTransactionVolume = TokenTransactionLog::where('created_at', '>=', Carbon::now()->subHours(24))
                ->sum('txn_tokens');
            $stats['average_24_hrs_token_transaction_volume'] = $average24HrsTokenTransactionVolume ? $average24HrsTokenTransactionVolume : 0;
            $stats['no_of_24_hrs_transaction_traders'] = $noOf24HrsTransactionTraders->isNotEmpty() ? count($noOf24HrsTransactionTraders->toArray()) : 0;
            $stats['paypal_client_ids'] = null;
            $users_financial_account = FinancialAccount::where("user_id", $user->id)->selectRaw('paypal_production_client_id,paypal_sandbox_client_id')->first();
            if ($users_financial_account) {
                $stats['paypal_client_ids'] = $users_financial_account->toArray();
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Wallet stats retrieved', 'toast' => true], ['stats' => $stats]);
        } catch (\Exception $e) {
            Log::info('Wallet dashboard Error : ' . $e->getMessage() . " line no " . $e->getLine() . " " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function buy(BuyRequest $request)
    {
        DB::beginTransaction();
        try {
            $receiver_user = $request->attributes->get('user');
            $payment_id = $request->payment_id;
            $perticulars = isset($request->perticulars) ? $request->perticulars : "Token Purchase";
            $sender_user = User::where("role_id", "2")->first();

            if (!$sender_user)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Sender not found', 'toast' => true]);

            $payment_details = Payment::where("id", $payment_id)->where("user_id", $receiver_user->id)->first();
            if (!$payment_details)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Payment not found', 'toast' => true]);

            if ($payment_details->status != "3")
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Payment is failed/pending', 'toast' => true]);

            if ($payment_details->reference_id != null)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Tokens already purchased for payment id', 'toast' => true]);

            $receiverBuyLog = TokenTransactionLog::where("receiver_id", $receiver_user->id)->where("txn_type", '1')->count();

            $amount = $payment_details->amount;
            if (!$receiverBuyLog) {
                $amount = $payment_details->amount + 0.05;
            }
            $txn_tokens = $amount / getTokenMetricsValues();

            if (!balanceValidations($sender_user->id, $txn_tokens, false))
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Insufficient Tokens', 'toast' => true]);

            $lastTokenTransactionLog = TokenTransactionLog::orderBy('id', 'desc')->first();
            $transaction_id = makeTransaction($sender_user, $receiver_user, $txn_tokens, $perticulars, "1", null, $lastTokenTransactionLog, null);
            $auger_transaction_id = null;

            $payment_details->reference_id = $transaction_id;
            $payment_details->save();

            DB::commit();
            $temp_user = User::where('id', $receiver_user->id)->first();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Token purchased successfully', 'toast' => true, 'data' => ["transaction_id" => $transaction_id, "auger_transaction_id" => $auger_transaction_id, "account_tokens" => $temp_user->account_tokens]]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Wallet Error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function users(Request $request)
    {
        try {
            $user = $request->attributes->get('user');
            $user_id = isset($request->user_id) && is_numeric($request->user_id) ? $request->user_id : null;


            if ($user_id) {
                $user = User::selectRaw('username,email,id')->with('profile')->where("id", $user_id)->first();
                if ($user) {
                    $profile_path = null;
                    if (isset($user->profile->profile_image_path) && $user->profile->profile_image_path)
                        $profile_path = getFileTemporaryURL($user->profile->profile_image_path);
                    $user = $user->toArray();
                    $user['profile_image_path'] = $profile_path;
                    unset($user['profile']);
                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Users retrieved successfully', 'toast' => true], ["user" => $user]);
                } else
                    return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No user found', 'toast' => true]);
            }

            $limit = isset($request->limit) && is_numeric($request->limit) && $request->limit > 0 ? $request->limit : 10;
            $page = isset($request->page) && is_numeric($request->page) && $request->page > 0 ? $request->page : 1;
            $search = isset($request->search) ? $request->search : "";
            $offset = ($page - 1) * $limit;

            $query = User::query();
            if ($search) {
                $query->where('username', 'like', "%$search%");
            }
            $query->offset($offset)->limit($limit);
            $query = $query->where("verify_email", "1")->whereNot("id", $user->id);
            $users = $query->selectRaw("id,username,email")->with('profile')->get();
            DB::commit();
            if ($users->isNotEmpty()) {
                $users->transform(function ($user) {
                    $profile_path = null;
                    if (isset($user->profile->profile_image_path) && $user->profile->profile_image_path)
                        $profile_path = getFileTemporaryURL($user->profile->profile_image_path);
                    $user->profile_image_path = $profile_path;
                    unset($user->profile);
                    return $user;
                });
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Users retrieved successfully', 'toast' => true], ["users" => $users]);
            } else
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Users not found', 'toast' => true], ["users" => []]);
        } catch (\Exception $e) {
            Log::info('wallet users Error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while retrieving users', 'toast' => true]);
        }
    }
    public function transfer(TransferRequest $request)
    {
        DB::beginTransaction();
        try {
            $receiver_id = $request->receiver_id;
            $txn_tokens = $request->txn_tokens;
            $tokenRequest = null;
            if (isset($request->request_id)) {
                $tokenRequest = TokenRequest::where("id", $request->request_id)->where('type', "1")->where("status", "0")->first();
                $txn_tokens = $tokenRequest->txn_tokens;
                $receiver_id = $tokenRequest->to_user_id;
            }
            $auger_tokens = $txn_tokens * (config('app.auger_fee') / 100);

            $perticulars = isset($request->perticulars) ? $request->perticulars : "Token Transfer";
            $sender_user = $request->attributes->get('user');

            if (!balanceValidations($sender_user->id, $txn_tokens))
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Insufficient Tokens', 'toast' => true]);

            $receiver_user = User::where("id", $receiver_id)->first();
            if (!$receiver_user)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Receiver user not found', 'toast' => true]);

            $admin_user = User::where("role_id", "2")->first();
            if (!$admin_user)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Admin user not found', 'toast' => true]);

            $lastTokenTransactionLog = TokenTransactionLog::orderBy('id', 'desc')->first();

            $transaction_id = makeTransaction($sender_user, $receiver_user, $txn_tokens, $perticulars, "2", null, $lastTokenTransactionLog, null);

            $auger_transaction_id = makeTransaction($sender_user, $admin_user, $auger_tokens, "Auger Fee " . $perticulars, "5", null, $lastTokenTransactionLog, $transaction_id);

            if ($tokenRequest) {
                $tokenRequest->transaction_id = $transaction_id;
                $tokenRequest->approved_at = date('Y-m-d H:i:s');
                $tokenRequest->status = "1";
                $tokenRequest->payment_gateway = "3";
                $tokenRequest->save();
            }
            DB::commit();

            $txnLog = TokenTransactionLog::where("id", $transaction_id)->first();
            sendTransactionMail($txnLog, null, null, true, true, "8");
            $augerTxnLog = TokenTransactionLog::where("id", $auger_transaction_id)->first();
            sendTransactionMail($augerTxnLog, null, null, true, true, "8");

            $updated_user = User::where("id", $sender_user->id)->first();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Token transfered successfully', 'toast' => true, 'data' => ["transaction_id" => $transaction_id, "auger_transaction_id" => $auger_transaction_id, 'account_tokens' => $updated_user->account_tokens]]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Wallet transfer error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function request(MakeTokenRequest $request)
    {
        try {
            DB::beginTransaction();
            $type = $request->type;
            $user = $request->attributes->get('user');
            $from_user_id = $user->id;
            $txn_tokens = $request->txn_tokens;
            $to_user_id = isset($request->to_user_id) ? $request->to_user_id : null;
            if ($type == "2") {

                $paypalSettings = getPaypalSettings($user);
                if (!$paypalSettings['status']) {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Before making token to cash request, update paypal client ids.', 'toast' => true]);
                }

                if (!balanceValidations($from_user_id, $txn_tokens))
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Insufficient Tokens', 'toast' => true]);
                $to_user = User::where("role_id", "2")->orderBy("id")->first();
                if (!$to_user)
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Sender not found', 'toast' => true]);
                $to_user_id = $to_user->id;

                $temp_txn_tokens = $txn_tokens + ($txn_tokens * (config('app.auger_fee') / 100));

                $auger_fee =
                    $user->account_tokens = $user->account_tokens - $temp_txn_tokens;
                $user->reserved_tokens = $user->reserved_tokens + $temp_txn_tokens;
                $user->save();
            } else {
                $to_user = User::where("id", $to_user_id)->first();
            }

            $tokenRequest = new TokenRequest();
            $tokenRequest->type = $type;
            $tokenRequest->from_user_id = $from_user_id;
            $tokenRequest->to_user_id = $to_user_id;
            $tokenRequest->txn_tokens = $txn_tokens;
            $tokenRequest->token_value = getTokenMetricsValues();
            $tokenRequest->save();

            $title = $description = $type == "1" ? "Token request from " . $user->username : "Token to cash conversion request from " . $user->username;
            // add token request notification
            $authToken = $request->header('authToken');
            addNotification(
                $to_user_id,
                $from_user_id,
                $title,
                $title,
                $tokenRequest->id,
                '8',
                '#',
                null,
                $authToken
            );

            DB::commit();

            // send token request mail 
            $data['view'] = 'mail-templates.token-request';
            $data['logoUrl'] = asset('assets/images/logo/logo-dark.png');
            $data['title'] = $title;
            $subject = $type == "1" ? "Token request" : "Token to cash conversion request";
            $data['subject'] = $subject;
            $data['transaction_link'] = "#";
            $data['linkTitle'] = "View";
            $data['username'] = $to_user->username;
            $message = $user->username . " has requested SBC" . number_format($txn_tokens, 6, ".", ",") . " to you";
            $usdValue = $txn_tokens * getTokenMetricsValues();
            $usdValue = number_format($usdValue, 2, ".", ",");
            if ($type == "2")
                $message = $user->username . " has made token to cash conversion request for SBC " . number_format($txn_tokens, 6, ".", ",") . "(approx. $ {$usdValue})";
            $data['message'] = $message;
            $data['projectName'] = config('app.app_name');
            $data['supportMail'] = config('app.support_mail');
            Mail::to($to_user->email)->send(new SendMail($data, $data['view']));


            $updated_user = User::where("id", $user->id)->first();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Token request sent successfully', 'toast' => true], ['account_tokens' => $updated_user->account_tokens]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Wallet request error : ' . $e->getMessage() . " line no " . $e->getLine() . " file " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function logs(GetDataRequest $request)
    {
        try {
            $user = $request->attributes->get('user');

            $limit = isset($request->limit) ? $request->limit : 10;
            $page = isset($request->page) && $request->page ? $request->page : 1;
            $search_keyword = isset($request->search_keyword) && $request->search_keyword ? $request->search_keyword : "";
            $offset = ($page - 1) * $limit;

            $allTransactionCount = $searchTransactionCount = DB::table("token_transaction_logs as ttl")->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)->orWhere('receiver_id', $user->id);
            })->count();

            $tempTransactionQuery = DB::table("token_transaction_logs as ttl");
            $tempTransactionQuery->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)->orWhere('receiver_id', $user->id);
            })->offset($offset)->limit($limit)->leftJoin("users as su", "ttl.sender_id", "=", "su.id")->leftJoin("users as ru", "ttl.receiver_id", "=", "ru.id")->orderBy("ttl.updated_at", "desc");

            if ($search_keyword) {
                $tempTransactionQuery->where(function ($query) use ($search_keyword) {
                    $query->where('su.username', "like", "%$search_keyword%")->orWhere('ru.username', "like", "%$search_keyword%")->orWhere('su.email', "like", "%$search_keyword%")->orWhere('ru.email', "like", "%$search_keyword%");
                });
                $searchTransactionCount = DB::table("token_transaction_logs as ttl")->where(function ($query) use ($user) {
                    $query->where('sender_id', $user->id)->orWhere('receiver_id', $user->id);
                })->where(function ($query) use ($search_keyword) {
                    $query->where('su.username', "like", "%$search_keyword%")->orWhere('ru.username', "like", "%$search_keyword%")->orWhere('su.email', "like", "%$search_keyword%")->orWhere('ru.email', "like", "%$search_keyword%");
                })->count();
            }

            $temp_transaction_logs = $tempTransactionQuery->selectRaw("ttl.*,su.email as sender_email,ru.email as receiver_email,su.username as sender_username,ru.username as receiver_username")->get();
            if (!$temp_transaction_logs)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No logs available', 'toast' => true, 'data' => []]);

            $temp_transaction_logs = $temp_transaction_logs->toArray();

            $transaction_logs = [];

            foreach ($temp_transaction_logs as $ttl) {


                $temp['txn_type'] = $ttl->sender_id == $user->id ? 'Debit' : 'Credit';
                $temp['txn_amount'] = $ttl->txn_tokens;
                $temp['id'] = $ttl->id;
                $temp['token_value'] = $ttl->token_value;
                $temp['hash_key'] = $ttl->hash_key;
                $temp['time'] = date("F,d-Y H:i:s A", strtotime($ttl->created_at));
                $temp['perticulars'] = $ttl->perticulars;
                $temp['txn_status'] = isset($this->txn_status[$ttl->txn_status]) ? $this->txn_status[$ttl->txn_status] : "";
                $temp['txn_type'] = isset($this->txn_type[$ttl->txn_type]) ? $this->txn_type[$ttl->txn_type] : "";
                $temp['txn_for'] = isset($this->txn_for[$ttl->txn_for]) ? $this->txn_for[$ttl->txn_for] : "";
                $temp['txn_user'] = $ttl->sender_id == $user->id ? $ttl->receiver_username : $ttl->sender_username;

                $transaction_logs[] = $temp;
            }

            if (!$transaction_logs)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No logs available', 'toast' => true, 'data' => []]);

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Log retrieved successfully', 'toast' => true, 'data' => ["logs" => $transaction_logs, "searchTransactionCount" => $searchTransactionCount, "allTransactionCount" => $allTransactionCount]]);
        } catch (\Exception $e) {
            Log::info('Wallet logs Error : ' . $e->getMessage() . " @line_no " . $e->getLine() . " in file " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function paypalSettings(PaypalSettingRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $paypal_sandbox_client_id = $request->paypal_sandbox_client_id;
            $paypal_production_client_id = $request->paypal_production_client_id;
            $users_financial_account = FinancialAccount::where("user_id", $user->id)->first();
            if (!$users_financial_account) {
                $users_financial_account = new FinancialAccount();
                $users_financial_account->user_id = $user->id;
            }
            $users_financial_account->paypal_sandbox_client_id = $paypal_sandbox_client_id;
            $users_financial_account->paypal_production_client_id = $paypal_production_client_id;
            $users_financial_account->save();
            DB::commit();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Paypal information updated', 'toast' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Wallet paypalSettings error : ' . $e->getMessage() . " line no " . $e->getLine() . " file " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function requestLogs(TokenRequestLog $request)
    {
        try {
            $user = $request->attributes->get('user');
            $limit = isset($request->limit) ? $request->limit : 10;
            $type = "";
            if ($request->type == "token") {
                $type = "1";
            } elseif ($request->type == "cash") {
                $type = "2";
            }
            $page = isset($request->page) && $request->page ? $request->page : 1;
            $search_keyword = isset($request->search_keyword) && $request->search_keyword ? $request->search_keyword : "";
            $offset = ($page - 1) * $limit;


            $transactionCountQuery = DB::table('token_requests as tr')->where(function ($query) use ($user) {
                $query->where('to_user_id', $user->id)->orWhere('from_user_id', $user->id);
            });
            $allTransactionCountQuery = $searchTransactionCountQuery = $transactionCountQuery;

            $tempRequestLogsQuery = DB::table("token_requests as tr");

            $tempRequestLogsQuery->where(function ($query) use ($user) {
                $query->where('to_user_id', $user->id)->orWhere('from_user_id', $user->id);
            })->offset($offset)->limit($limit)->leftJoin("users as su", "tr.from_user_id", "=", "su.id")->leftJoin("users as ru", "tr.to_user_id", "=", "ru.id")->orderBy("tr.updated_at", "desc");

            if ($search_keyword) {
                $tempRequestLogsQuery->where(function ($query) use ($search_keyword) {
                    $query->where('su.username', "like", "%$search_keyword%")->orWhere('ru.username', "like", "%$search_keyword%")->orWhere('su.email', "like", "%$search_keyword%")->orWhere('ru.email', "like", "%$search_keyword%");
                });
                $searchTransactionCountQuery->where(function ($query) use ($search_keyword) {
                    $query->where('su.username', "like", "%$search_keyword%")->orWhere('ru.username', "like", "%$search_keyword%")->orWhere('su.email', "like", "%$search_keyword%")->orWhere('ru.email', "like", "%$search_keyword%");
                });
            }
            if ($type) {
                $tempRequestLogsQuery->where("type", $type);
                $allTransactionCountQuery->where("type", $type);
                $searchTransactionCountQuery->where("type", $type);
            }
            if ($request->has('pending')) {
                $tempRequestLogsQuery->where("tr.status", "0");
                $allTransactionCountQuery->where("tr.status", "0");
                $searchTransactionCountQuery->where("tr.status", "0");
            }
            $temp_request_logs = $tempRequestLogsQuery->selectRaw("tr.*,su.email as sender_email,ru.email as receiver_email,su.username as sender_username,ru.username as receiver_username,tr.type as request_type")->get();

            if (!$temp_request_logs)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No logs available', 'toast' => true, 'data' => []]);

            $temp_request_logs = $temp_request_logs->toArray();

            $request_logs = [];

            foreach ($temp_request_logs as $tr) {

                $temp['txn_amount'] = $tr->txn_tokens;
                $temp['id'] = $tr->id;
                $temp['transaction_id'] = $tr->transaction_id;
                $temp['requested_at'] = date("F,d-Y H:i:s A", strtotime($tr->created_at));
                $temp['approved_at'] = $tr->approved_at ? date("F,d-Y H:i:s A", strtotime($tr->approved_at)) : null;
                $temp['comment'] = $tr->comment;
                $temp['status'] = $tr->status ? "Approved" : "Pending";
                $temp['txn_user'] = $tr->from_user_id == $user->id ? $tr->receiver_username : $tr->sender_username;
                $temp['paid_amount'] = $tr->paid_amount;
                $temp['request_type'] = $tr->request_type == "1" ? "Token" : "Cash";
                $temp['token_value'] = $tr->token_value;
                $request_logs[] = $temp;
            }

            if (!$request_logs)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No logs available', 'toast' => true, 'data' => []]);

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Log retrieved successfully', 'toast' => true, 'data' => ["logs" => $request_logs, "searchTransactionCount" => $searchTransactionCountQuery->count(), "allTransactionCount" => $allTransactionCountQuery->count()]]);
        } catch (\Exception $e) {
            Log::info('Wallet requestLogs Error : ' . $e->getMessage() . " @line_no " . $e->getLine() . " in file " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function cashRequestList(CashRequestList $request)
    {
        try {
            $from_date_time = isset($request->from_date_time) ? $request->from_date_time : null;
            $end_date_time = isset($request->end_date_time) ? $request->end_date_time : null;
            $limit = isset($request->limit) ? $request->limit : null;
            $page = isset($request->page) && $request->page ? $request->page : null;
            $search_keyword = isset($request->search_keyword) && $request->search_keyword ? $request->search_keyword : "";
            $offset = $page ? ($page - 1) * $limit : null;
            $user_id = $request->user_id;
            $type = "2";
            $status = "0";

            $requestLogQuery = TokenRequest::query();
            $requestLogQuery->where("from_user_id", $user_id)->where("type", $type)->where("status", $status);

            if ($from_date_time) {
                $requestLogQuery->where("token_requests.created_at", ">=", $from_date_time);
            }
            if ($end_date_time) {
                $requestLogQuery->where("token_requests.created_at", "<=", $end_date_time);
            }
            if ($limit) {
                $requestLogQuery->limit($limit);
            }
            if ($offset) {
                $requestLogQuery->offset($offset);
            }
            $tempList = $requestLogQuery->get();
            if ($tempList->isNotEmpty()) {
                $tempList = $tempList->toArray();
                $allTokens = 0;
                $allUSD = 0;
                foreach ($tempList as $tempListRow) {
                    $allTokens += $tempListRow['txn_tokens'];
                    $tokenValueOfRow = $tempListRow['token_value'];
                    $usd = $tempListRow['txn_tokens'] * $tokenValueOfRow;
                    $allUSD += $usd;
                }
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Token to cash request found', 'toast' => true], ["list" => $tempList, "allTokens" => $allTokens, "allUSD" => $allUSD]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No token to cash request found', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('Wallet cashRequestList Error : ' . $e->getMessage() . " @line_no " . $e->getLine() . " in file " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function approveCashRequest(ApproveCashRequest $request)
    {
        try {
            DB::beginTransaction();
            $user_id = $request->user_id;
            $paypal_response = $request->paypal_response;
            $request_ids = $request->request_ids;
            $request_ids = explode(",", $request_ids);
            if (!is_array($request_ids)) {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Provide request logs', 'toast' => true]);
            }

            $paypal_response = json_decode($request->paypal_response, true);
            $paypal_status = isset($paypal_response['status']) ? $paypal_response['status'] : null;
            $paypal_state = isset($paypal_response['state']) ? $paypal_response['state'] : null;
            $comment = isset($request->comment) && $request->comment ? $request->comment : null;
            $payment_status = false;
            $payment_txn_id = null;
            $amount = 0;
            if ($paypal_status == "COMPLETED") {
                $payment_status = true;
                $amount = $paypal_response['purchase_units'][0]['amount']['value'];
                $payment_txn_id = $paypal_response['purchase_units'][0]['payments']['captures'][0]['id'];
            } else if ($paypal_state == "approved") {
                $payment_status = true;
                $amount = $paypal_response['transactions'][0]['amount']['total'];
                $payment_txn_id = $paypal_response['transactions'][0]['related_resources'][0]['sale']['id'];
            }
            if ($payment_status && $payment_txn_id && $amount) {
                $responseString = "payment_txn_id = " . $payment_txn_id;
                $updateQueryResponse = TokenRequest::whereIn("id", $request_ids)->where("from_user_id", $user_id)->where("status", "0")->update([
                    'payment_gateway' => "1",
                    'transaction_id' => $payment_txn_id,
                    'status' => "1",
                    'paid_amount' => $amount,
                    'approved_at' => date("Y-m-d H:i:s"),
                    'comment' => $comment
                ]);
                if (!$updateQueryResponse) {
                    return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Provide request logs', 'toast' => true]);
                }
                $all_txn_tokens = 0;
                $allTokenCount = TokenRequest::whereIn("id", $request_ids)->where("from_user_id", $user_id)->selectRaw("sum(txn_tokens) as all_txn_tokens")->first();
                if ($allTokenCount) {
                    $all_txn_tokens = $allTokenCount->all_txn_tokens;
                }
                $temp_txn_tokens = $all_txn_tokens + ($all_txn_tokens * (config('app.auger_fee') / 100));
                $responseString .= " allTokenCount " . $allTokenCount . " with auger fee " . $temp_txn_tokens;

                $fromUser = User::where("id", $user_id)->first();
                $responseString .= " before fromUser->reserved_tokens " . $fromUser->reserved_tokens;
                $fromUser->reserved_tokens = $fromUser->reserved_tokens - $temp_txn_tokens;
                $fromUser->save();

                $responseString .= " after fromUser->reserved_tokens " . $fromUser->reserved_tokens;
                $toUser = User::where("role_id", "2")->orderBy("id")->first();
                $responseString .= " before toUser->account_tokens " . $toUser->account_tokens;
                $toUser->account_tokens = $toUser->account_tokens + $temp_txn_tokens;
                $responseString .= " after toUser->account_tokens " . $toUser->account_tokens;
                $toUser->save();


                $lastTokenTransactionLog = TokenTransactionLog::orderBy('id', 'desc')->first();

                $tokenTransactionLog = new TokenTransactionLog();
                $txn_id = $lastTokenTransactionLog ? $lastTokenTransactionLog->txn_id + 1 : 1;

                $tokenTransactionLog->sender_id = $fromUser->id;
                $tokenTransactionLog->receiver_id = $toUser->id;
                $tokenTransactionLog->txn_id = $txn_id;
                $tokenTransactionLog->hash_key = Hash::make(
                    $fromUser->id . $toUser->id . $txn_id
                );
                $tokenTransactionLog->token_value = getTokenMetricsValues();
                $tokenTransactionLog->perticulars = "Token to cash request approved";
                $tokenTransactionLog->txn_tokens = $all_txn_tokens;
                $tokenTransactionLog->txn_status = '1';
                $tokenTransactionLog->txn_type = "3";
                $tokenTransactionLog->txn_for = "5";
                $tokenTransactionLog->parent_txn_id = null;
                $tokenTransactionLog->previous_txn_id = $lastTokenTransactionLog
                    ? $lastTokenTransactionLog->txn_id
                    : null;
                $tokenTransactionLog->save();


                $augerTokenTransactionLog = new TokenTransactionLog();
                $txn_id = $tokenTransactionLog ? $tokenTransactionLog->txn_id + 1 : 1;
                $augerTokenTransactionLog->sender_id = $fromUser->id;
                $augerTokenTransactionLog->receiver_id = $toUser->id;
                $augerTokenTransactionLog->txn_id = $txn_id;
                $augerTokenTransactionLog->hash_key = Hash::make(
                    $fromUser->id . $toUser->id . $txn_id
                );
                $augerTokenTransactionLog->token_value = getTokenMetricsValues();
                $augerTokenTransactionLog->perticulars = "Auger Fee: Token to cash request approved";
                $augerTokenTransactionLog->txn_tokens = $all_txn_tokens * (config('app.auger_fee') / 100);
                $augerTokenTransactionLog->txn_status = '1';
                $augerTokenTransactionLog->txn_type = "5";
                $augerTokenTransactionLog->txn_for = "5";
                $augerTokenTransactionLog->parent_txn_id = $tokenTransactionLog->id;
                $augerTokenTransactionLog->previous_txn_id = $tokenTransactionLog->txn_id;
                $augerTokenTransactionLog->save();

                addNotification(
                    $user_id,
                    $toUser->id,
                    "Token to cash request approved",
                    "Token to cash request approved",
                    null,
                    '8',
                    '#',
                    null,
                );

                DB::commit();
                Log::info($responseString);
                $authToken = $request->header('authToken');
                sendSocketNotification($user_id, "Token to cash request approved", "Token to cash request approved", "8", $fromUser->username, $authToken);
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Token to cash request transaction done', 'toast' => true]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Invalid paypal response', 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::info('Wallet approveCashRequest Error : ' . $e->getMessage() . " @line_no " . $e->getLine() . " in file " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
}
