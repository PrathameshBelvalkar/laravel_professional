<?php

use App\Mail\SendMail;
use App\Models\Country;
use App\Models\Payment;
use App\Models\Subscription\AffiliateMaster;
use App\Models\Subscription\AffiliateReward;
use App\Models\TokenTransactionLog;
use App\Models\User;
use App\Models\Wallet\FinancialAccount;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;

if (!function_exists('getTokenMetricsValues')) {
    function getTokenMetricsValues($key = 'token_value')
    {
        $new_user_life_value = config('app.new_user_life_value');
        $new_user_registration = User::where('verify_email', '1')->count();
        $aggregation_cost = config('app.aggregation_cost');
        $user_login = $new_user_registration * 30;
        $login_rewards = $new_user_life_value / $user_login;
        $token_value = $login_rewards;
        $total_token_supply = $new_user_registration * $new_user_life_value;
        $user_login = $new_user_registration * 30;
        $login_rewards = $new_user_life_value / $user_login;
        $token_value = $login_rewards;
        $total_coin_supply = $total_token_supply / $new_user_life_value;
        $coin_price =
            ((($new_user_life_value / 16) * $total_coin_supply) / 16) * 0.01;
        $reflextions_variable = $token_value * $total_token_supply; //issue
        $market_cap = $coin_price * $total_coin_supply;
        $coins_in_circulation = $total_coin_supply * 0.79;
        $coin_value = $coins_in_circulation * $coin_price;
        $subsription_totals = $new_user_registration * 30;
        $staking_account = $subsription_totals * 0.38;
        $rev_share = $subsription_totals - $staking_account - $aggregation_cost; //issue
        $network_fees =
            $login_rewards * $coins_in_circulation * $coin_value * 1.29;
        $market_value_assumptions = $network_fees + $coin_value;
        $liquidity_pool = $market_value_assumptions + $staking_account;
        $y_ebita = $rev_share * 12;
        $marketing_budget = $aggregation_cost * $new_user_registration;
        $platform_earnings_projections = $y_ebita - $marketing_budget;
        $input_100 = $login_rewards * 30;

        $token_metrics = [
            'new_user_life_value' => $new_user_life_value,
            'new_user_registration' => $new_user_registration,
            'token_value' => $token_value,
            'aggregation_cost' => $aggregation_cost,
            'total_token_supply' => $total_token_supply,
            'total_coin_supply' => $total_coin_supply,
            'coin_price' => $coin_price,
            'market_cap' => $market_cap,
            'coins_in_circulation' => $coins_in_circulation,
            'reflextions_variable' => $reflextions_variable,
            'coin_value' => $coin_value,
            'user_login' => $user_login,
            'login_rewards' => $login_rewards,
            'subsription_totals' => $subsription_totals,
            'staking_account' => $staking_account,
            'rev_share' => $rev_share,
            'network_fees' => $network_fees,
            'market_value_assumptions' => $market_value_assumptions,
            'liquidity_pool' => $liquidity_pool,
            'y_ebita' => $y_ebita,
            'marketing_budget' => $marketing_budget,
            'platform_earnings_projections' => $platform_earnings_projections,
            'input_100' => $input_100,
        ];
        if (!empty($key) && isset($token_metrics[$key])) {
            return $token_metrics[$key];
        }

        return $token_metrics;
    }
}
if (!function_exists('makeTransaction')) {
    function makeTransaction(
        $sender_user,
        $receiver_user,
        $txn_tokens,
        $perticulars,
        $txn_type,
        $txn_for,
        $lastTokenTransactionLog,
        $parent_txn_id = null
    ) {
        $receiver_user->account_tokens =
            $receiver_user->account_tokens + $txn_tokens;
        $sender_user->account_tokens =
            $sender_user->account_tokens - $txn_tokens;
        $receiver_user->save();
        $sender_user->save();

        $tokenTransactionLog = new TokenTransactionLog();

        if ($lastTokenTransactionLog) {
            $txn_id = $lastTokenTransactionLog->txn_id + 1;
        } else {
            $txn_id = 1;
        }

        $tokenTransactionLog->sender_id = $sender_user->id;
        $tokenTransactionLog->receiver_id = $receiver_user->id;
        $tokenTransactionLog->txn_id = $txn_id;
        $tokenTransactionLog->hash_key = Hash::make(
            $sender_user->id . $receiver_user->id . $txn_id
        );
        $tokenTransactionLog->token_value = getTokenMetricsValues();
        $tokenTransactionLog->perticulars = $perticulars;
        $tokenTransactionLog->txn_tokens = $txn_tokens;
        $tokenTransactionLog->txn_status = '1';
        $tokenTransactionLog->txn_type = $txn_type;
        $tokenTransactionLog->txn_for = $txn_for;
        $tokenTransactionLog->parent_txn_id = $parent_txn_id;
        $tokenTransactionLog->previous_txn_id = $lastTokenTransactionLog
            ? $lastTokenTransactionLog->txn_id
            : null;

        $tokenTransactionLog->save();
        return $tokenTransactionLog->id;
    }
}
if (!function_exists('updateTransaction')) {
    function updateTransaction($transactionId, $txnTokens, $particulars, $txnStatus)
    {
        // Find the transaction log by ID
        $transactionLog = TokenTransactionLog::find($transactionId);

        if ($transactionLog) {
            // Update the transaction log details
            $transactionLog->txn_tokens = $txnTokens;
            $transactionLog->perticulars = $particulars;
            $transactionLog->txn_status = $txnStatus;

            // Save the updated transaction log
            $transactionLog->save();
        } else {
        }
    }
}
if (!function_exists('balanceValidations')) {
    function balanceValidations($sender_user_id, $txn_tokens, $is_auger = true)
    {
        $auger_tokens = 0;
        if ($is_auger) {
            $auger_tokens = $txn_tokens * (config('app.auger_fee') / 100);
        }
        $all_txn_tokens = $txn_tokens + $auger_tokens;

        $sender_user = User::where('id', $sender_user_id)->first();

        if ($sender_user->account_tokens < $all_txn_tokens) {
            return false;
        }
        return true;
    }
}
if (!function_exists('getTxnUsers')) {
    function getTxnUsers($txn_id)
    {
        $txn = TokenTransactionLog::where('id', $txn_id)->first();
        $temp['sender_username'] = null;
        $temp['receiver_username'] = null;
        $temp['sender_contact'] = null;
        $temp['receiver_contact'] = null;
        $temp['sender_email'] = null;
        $temp['receiver_email'] = null;
        if ($txn) {
            $sender_user = User::where('id', $txn->sender_id)->first();
            $receiver_user = User::where('id', $txn->receiver_id)->first();
            $temp['sender_contact'] = getUsersContact($sender_user->id);
            $temp['receiver_contact'] = getUsersContact($receiver_user->id);
            $temp['sender_username'] = $sender_user->username;
            $temp['receiver_username'] = $receiver_user->username;
            $temp['sender_email'] = $sender_user->email;
            $temp['receiver_email'] = $receiver_user->email;
        }

        return $temp;
    }
}
if (!function_exists('sendTransactionMail')) {
    function sendTransactionMail(
        $tokenTransactionLog,
        $attachment = null,
        $message = '',
        $sms = false,
        $webNotification = false,
        $module = "1"
    ) {
        try {
            $txn_users = getTxnUsers($tokenTransactionLog->id);

            $data['projectName'] = config('app.app_name');
            $data['supportMail'] = config('app.support_mail');
            $data['subject'] = $data['title'] =
                'Wallet Transaction - ' . $tokenTransactionLog->perticulars;
            $data['view'] = 'mail-templates.wallet-transaction';
            $data['logoUrl'] = asset('assets/images/logo/logo-dark.png');
            $data['username'] = $txn_users['sender_username'];
            $data['transaction_link'] = '#';
            $data['linkTitle'] = 'View';
            $no_of_tokens = number_format(
                $tokenTransactionLog->txn_tokens,
                2,
                '.',
                ','
            );
            $data['sub_message'] = '';
            if (
                $tokenTransactionLog->txn_for == '2' &&
                $tokenTransactionLog->txn_type != '5'
            ) {
                $data['sub_message'] =
                    $no_of_tokens .
                    ' token(s) transfered to ' .
                    $txn_users['receiver_username'] .
                    "'s wallet as fee for package subscription";
            }
            if (
                $tokenTransactionLog->txn_for == '2' &&
                $tokenTransactionLog->txn_type == '5'
            ) {
                $data['sub_message'] = $no_of_tokens . ' token(s) transfered to ' . $txn_users['receiver_username'] . "'s wallet as auger fee for package subscription";
            }
            $data['message'] = $no_of_tokens . ' token(s) debited from your wallet to ' . $txn_users['receiver_username'];
            if ($message && $message != '') {
                $data['message'] = $message;
            }

            if ($attachment) {
                Mail::to($txn_users['sender_email'])->send(
                    new SendMail($data, $data['view'], $attachment->output())
                );
            } else {
                Mail::to($txn_users['sender_email'])->send(
                    new SendMail($data, $data['view'])
                );
            }
            $data['message'] = $no_of_tokens . ' token(s) credited to your wallet from ' . $txn_users['sender_username'];
            if ($message && $message != '') {
                $data['message'] = $message;
            }
            $data['username'] = $txn_users['receiver_username'];
            $data['transaction_link'] = '#';

            Mail::to($txn_users['receiver_email'])->send(
                new SendMail($data, $data['view'])
            );
            if ($sms) {
                sendTransactionSMS($tokenTransactionLog);
            }
            if ($webNotification) {
                sendTransactionWebNotification($tokenTransactionLog, $module);
            }
            return [
                'type' => 'success',
                'code' => 200,
                'status' => true,
                'message' => 'Transaction mail sent successfully',
                'toast' => true,
            ];
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return [
                'type' => 'error',
                'code' => 200,
                'status' => false,
                'message' => 'Error in sending transaction mail',
                'toast' => true,
            ];
        }
    }
}
if (!function_exists('addPayment')) {
    function addPayment(
        $payer_user,
        $user,
        $purpose,
        $mode,
        $status,
        $payment_txn_id,
        $payment_response,
        $amount,
        $note = null
    ) {
        $token_value = getTokenMetricsValues();
        $payer_id = $payer_user->id;
        $user_id = $user->id;

        $payment = new Payment();
        $payment->note = $note;
        $payment->purpose = $purpose;
        $payment->status = $status;
        $payment->payment_txn_id = $payment_txn_id;
        $payment->payment_response = $payment_response;
        $payment->token_value = $token_value;
        $payment->payer_id = $payer_id;
        $payment->user_id = $user_id;
        $payment->amount = $amount;
        $payment->mode = $mode;
        $payment->save();

        return $payment->id;
    }
}
if (!function_exists('buyTokens')) {
    function buyTokens($payment_id, $receiver_user)
    {
        try {
            $perticulars = 'Token Purchase';
            $sender_user = User::where('role_id', '2')
                ->orderBy('id', 'asc')
                ->first();

            if (!$sender_user) {
                return [
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Sender not found',
                    'toast' => true,
                ];
            }

            $payment_details = Payment::where('id', $payment_id)
                ->where('user_id', $receiver_user->id)
                ->first();
            if (!$payment_details) {
                return [
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Payment not found',
                    'toast' => true,
                ];
            }

            if ($payment_details->status != '3') {
                return [
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Payment is failed/pending',
                    'toast' => true,
                ];
            }

            if ($payment_details->reference_id != null) {
                return [
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Tokens already purchased for payment id',
                    'toast' => true,
                ];
            }

            $receiverBuyLog = TokenTransactionLog::where(
                'receiver_id',
                $receiver_user->id
            )
                ->where('txn_type', '1')
                ->count();

            $amount = $payment_details->amount;
            if (!$receiverBuyLog) {
                $amount = $payment_details->amount + 0.05;
            }
            $txn_tokens = $amount / getTokenMetricsValues();

            if (!balanceValidations($sender_user->id, $txn_tokens, false)) {
                return [
                    'type' => 'error',
                    'code' => 200,
                    'status' => false,
                    'message' => 'Insufficient Tokens',
                    'toast' => true,
                ];
            }

            $lastTokenTransactionLog = TokenTransactionLog::orderBy(
                'id',
                'desc'
            )->first();
            $transaction_id = makeTransaction(
                $sender_user,
                $receiver_user,
                $txn_tokens,
                $perticulars,
                '1',
                null,
                $lastTokenTransactionLog,
                null
            );
            $auger_transaction_id = null;

            $payment_details->reference_id = $transaction_id;
            $payment_details->save();
            $response = ['status' => true, "message" => "token purchased successfully.", "transaction_id" => $transaction_id];
            return $response;
        } catch (\Exception $e) {
            return false;
        }
    }
}
if (!function_exists('makeAffliateTransactions')) {
    function makeAffliateTransactions($user_id, $price)
    {
        $affliate_user = User::where('id', $user_id)->first();
        $affliate_row = AffiliateReward::where(
            'affiliate_id',
            $user_id
        )->first();

        if (
            $affliate_user &&
            $affliate_user->toArray() &&
            $affliate_row &&
            $affliate_row->toArray()
        ) {
            $affliate_master = AffiliateMaster::where(
                'id',
                $affliate_row->affiliate_master_id
            )->first();
            if ($affliate_master && $affliate_master->toArray()) {
                $admin_user = User::where('role_id', '2')
                    ->orderBy('id', 'asc')
                    ->first();

                $token_value = getTokenMetricsValues();
                if (!$affliate_row->affiliate_txn_id) {
                    $affliate_master_price = $affliate_master->affiliate_value;
                    if ($affliate_master->type == '1') {
                        $affliate_master_price =
                            ($price * $affliate_master->affiliate_value) / 100;
                    }
                    $affliate_master_token =
                        $affliate_master_price / $token_value;
                    $perticulars = 'Affliate Reward';
                    $lastTokenTransactionLog = TokenTransactionLog::orderBy(
                        'id',
                        'desc'
                    )->first();
                    if (
                        balanceValidations(
                            $admin_user->id,
                            $affliate_master_token,
                            false
                        )
                    ) {
                        $affliate_transaction_id = makeTransaction(
                            $admin_user,
                            $affliate_user,
                            $affliate_master_token,
                            $perticulars,
                            '6',
                            null,
                            $lastTokenTransactionLog
                        );
                        $affliate_row->affiliate_txn_id = $affliate_transaction_id;
                        $txnLog = TokenTransactionLog::where(
                            'id',
                            $affliate_transaction_id
                        )->first();
                        $tempAffliate_master_token = number_format(
                            $affliate_master_token,
                            6,
                            '.',
                            ','
                        );
                        $message = "You have got Affliate reward of SBC $tempAffliate_master_token , Explore our web app to more information";
                        sendTransactionMail($txnLog, null, $message);
                    }
                }
                if (!$affliate_row->refered_txn_id) {
                    $refered_master_price = $affliate_master->refered_value;
                    if ($affliate_master->type == '1') {
                        $refered_master_price =
                            ($price * $affliate_master->refered_value) / 100;
                    }
                    $refered_master_token =
                        $refered_master_price / $token_value;
                    $refered_user = User::where(
                        'id',
                        $affliate_row->refered_id
                    )->first();
                    $perticulars = 'Affliate Reward';
                    $lastTokenTransactionLog = TokenTransactionLog::orderBy(
                        'id',
                        'desc'
                    )->first();
                    if (
                        balanceValidations(
                            $admin_user->id,
                            $refered_master_token,
                            false
                        )
                    ) {
                        $refered_transaction_id = makeTransaction(
                            $admin_user,
                            $refered_user,
                            $refered_master_token,
                            $perticulars,
                            '6',
                            null,
                            $lastTokenTransactionLog
                        );
                        $affliate_row->refered_txn_id = $refered_transaction_id;
                        $txnLog = TokenTransactionLog::where(
                            'id',
                            $refered_transaction_id
                        )->first();
                        $tempRefered_master_token = number_format(
                            $refered_master_token,
                            6,
                            '.',
                            ','
                        );
                        $message = "You have got Affliate reward of SBC $tempRefered_master_token , Explore our web app to more information";
                        sendTransactionMail($txnLog, null, $message);
                    }
                }
                $affliate_row->save();
            }
        }
    }
}
if (!function_exists('sendTransactionSMS')) {
    function sendTransactionSMS($tokenTransactionLog)
    {
        $txn_users = getTxnUsers($tokenTransactionLog->id);

        $txn_tokens = number_format(
            $tokenTransactionLog->txn_tokens,
            6,
            '.',
            ','
        );

        $sender_msg =
            $txn_tokens .
            ' tokens transfered to ' .
            $txn_users['receiver_username'] .
            ' for ' .
            $tokenTransactionLog->perticulars;
        $receiver_msg =
            $txn_tokens .
            ' tokens credited to your account from ' .
            $txn_users['sender_username'] .
            ' for ' .
            $tokenTransactionLog->perticulars;

        if (
            isset($txn_users['sender_contact']) &&
            $txn_users['sender_contact']['phone_no'] &&
            $txn_users['sender_contact']['country_code']
        ) {
            send_sms(
                '+' .
                $txn_users['sender_contact']['country_code'] .
                $txn_users['sender_contact']['phone_no'],
                $sender_msg
            );
        }
        if (
            isset($txn_users['receiver_contact']) &&
            $txn_users['receiver_contact']['phone_no'] &&
            $txn_users['receiver_contact']['country_code']
        ) {
            send_sms(
                '+' .
                $txn_users['receiver_contact']['country_code'] .
                $txn_users['receiver_contact']['phone_no'],
                $receiver_msg
            );
        }
    }
}

if (!function_exists('sendTransactionWebNotification')) {
    function sendTransactionWebNotification($tokenTransactionLog, $module = "1")
    {
        $txn_users = getTxnUsers($tokenTransactionLog->id);
        $txn_tokens = number_format(
            $tokenTransactionLog->txn_tokens,
            6,
            '.',
            ','
        );
        $sender_msg =
            $txn_tokens .
            ' tokens transfered to ' .
            $txn_users['receiver_username'] .
            ' for ' .
            $tokenTransactionLog->perticulars;
        $receiver_msg =
            $txn_tokens .
            ' tokens credited to your account from ' .
            $txn_users['sender_username'] .
            ' for ' .
            $tokenTransactionLog->perticulars;
        $title = 'Wallet Transaction ' . $tokenTransactionLog->perticulars;
        addNotification(
            $tokenTransactionLog->sender_id,
            $tokenTransactionLog->sender_id,
            $title,
            $sender_msg,
            $tokenTransactionLog->id,
            $module,
            '#'
        );
        addNotification(
            $tokenTransactionLog->receiver_id,
            $tokenTransactionLog->sender_id,
            $title,
            $receiver_msg,
            $tokenTransactionLog->id,
            $module,
            '#'
        );
    }
}
if (!function_exists('getSocialPoints')) {
    function getSocialPoints($sso_user_id)
    {
        $post_data['cloud_user_id'] = $sso_user_id;
        $url = config("app.sso_url") . "persona-points";
        $res = makeCURLCall($url, $post_data);
        if ($res['status']) {
            return $res['points'];
        }
        return 0;
    }
}
if (!function_exists('getPaypalSettings')) {
    function getPaypalSettings($user, $isUserObject = true)
    {
        $status = ['status' => false, "message" => "Fetching paypal settings", "data" => null];
        if (!$isUserObject)
            $user = User::where("id", $user)->first();

        $users_financial_account = FinancialAccount::where("user_id", $user->id)->selectRaw('paypal_production_client_id,paypal_sandbox_client_id')->first();
        if ($users_financial_account) {
            if ($users_financial_account->paypal_production_client_id && $users_financial_account->paypal_sandbox_client_id) {
                $status = ['status' => true, "message" => "Paypal settings fetched", "data" => $users_financial_account->toArray()];
            }
        }
        return $status;
    }
}
