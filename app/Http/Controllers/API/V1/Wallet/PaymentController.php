<?php

namespace App\Http\Controllers\API\V1\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\AddPaymentRequest;

use App\Models\Payment;
use App\Models\TokenTransactionLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function add(AddPaymentRequest $request)
    {
        DB::beginTransaction();
        try {
            $payer_user = $request->attributes->get('user');
            if (isset($request->user_id)) {
                $user = User::where("id", $request->user_id)->first();
            } else {
                $user = $payer_user;
            }

            if (!$payer_user || !$user)
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'User not founds', 'toast' => true]);

            $payment_status = false;
            $payment_txn_id = "";
            $payment_response = $request->payment_response;
            $amount = 0;
            if ($request->mode == '2') {

                $paypal_response = json_decode($request->payment_response, true);
                $paypal_status = isset($paypal_response['status']) ? $paypal_response['status'] : null;
                $paypal_state = isset($paypal_response['state']) ? $paypal_response['state'] : null;

                if ($paypal_status == "COMPLETED") {
                    $payment_status = true;
                    $amount = $paypal_response['purchase_units'][0]['amount']['value'];
                    $payment_txn_id = $paypal_response['purchase_units'][0]['payments']['captures'][0]['id'];
                } else if ($paypal_state == "approved") {
                    $payment_status = true;
                    $amount = $paypal_response['transactions'][0]['amount']['total'];
                    $payment_txn_id = $paypal_response['transactions'][0]['related_resources'][0]['sale']['id'];
                }
            }
            if ($payment_status) {
                $note = isset($request->note) ? $request->note : null;
                $payment_id = addPayment($user, $user, "1", "1", "3", $payment_txn_id, $payment_response, $amount, $note);
                $transaction_response = null;
                if (isset($request->buyTokens)) {
                    $transaction_response = buyTokens($payment_id, $user);
                }
                DB::commit();
                if (isset($transaction_response['status']) && $transaction_response['status'] && isset($request->buyTokens)) {

                    $txnLog = TokenTransactionLog::where("id", $transaction_response['transaction_id'])->first();
                    if ($txnLog && $txnLog->toArray())
                        sendTransactionMail($txnLog, null, null, true, true, "8");
                }
                $tempUser = User::where("id", $user->id)->first();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Payment added successfully', 'toast' => true], ["payment_id" => $payment_id, "account_tokens" => $tempUser->account_tokens]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Payment not completed', 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Add Payment Error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
}
