<?php

namespace App\Http\Controllers\API\V1\Coin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Requests\Coin\MakeInvestmentRequest;
use App\Models\TokenTransactionLog;
use App\Models\coin\CoinInvestment;
use App\Models\User;
use App\Mail\SendMail;
use Illuminate\Support\Facades\Mail;

class InvestmentController extends Controller
{
   
    public function makeInvestment(MakeInvestmentRequest $request)
    {
        
        DB::beginTransaction();
        try {
            $user = $request->attributes->get('user');
            $userdata = getTabledata('users','id',$user->id);
            $userprofiledata = getTabledata('user_profiles','user_id',$user->id);
            $email = $userdata->email;
            $phone_no = $userprofiledata->phone_number;
            $country =  $userprofiledata->country;;
            $domain = getDomainFromEmail($userdata->email);
            
            $packageTokenPrice = $request->investment_amount / getTokenMetricsValues();
            $token_value = getTokenMetricsValues();
            $auger_tokens = $packageTokenPrice * (config('app.auger_fee') / 100);
           
            if (!balanceValidations($user->id, $packageTokenPrice)) {
                DB::rollBack();
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Insufficient token balance', 'toast' => true], ["required_tokens" => $packageTokenPrice + $auger_tokens, "available_tokens" => $user->account_tokens, "token_value" => $token_value, "price" => $request->investment_amount]);
            }
            $admin_user = User::where("role_id", "1")->first();
           
            $perticulars = "Investment";
            $lastTokenTransactionLog = TokenTransactionLog::orderBy('id', 'desc')->first();
            $transaction_id = makeTransaction($user, $admin_user, $packageTokenPrice, $perticulars, "4", "3", $lastTokenTransactionLog);
            $auger_transaction_id = makeTransaction($user, $admin_user, $auger_tokens, "Auger Fee " . $perticulars, "5", "3", $lastTokenTransactionLog, $transaction_id);
    
            $investment = new CoinInvestment();
            $investment->user_id = $user->id;
            $investment->transaction_id = $transaction_id;
            $investment->year_id = $request->year_id;
            $investment->coin_id = $request->coin_id;
            $investment->investment_amount = $request->investment_amount;
            $investment->notes = $request->notes ?? '';
            $investment->investment_date = now();
            $investment->save();
            $coin_nm = getTabledata('coin','id',$request->coin_id);
            $notifyuser = "Your investment in ". $coin_nm->coin_name ." was successful!";
            $notifyadmin = "A new investment has been made in ". $coin_nm->coin_name;
         
            if($investment && $userdata){
                $admin_id = getadmindetails();
                addNotification($user->id, $user->id, $notifyuser, null, null, '13', '/portfolio');
                addNotification($admin_id, $admin_id, $notifyadmin, null, null, '14', '/admin-manage-coinexchange/coinDetails/'.$request->coin_id,'1');

                if($phone_no && $country){
                    $phoneCode = getPhoneCodeByCountryId($country);
                    $phonenumber = "+" . $phoneCode . $phone_no;
                    $commessage = "Your investment $". $request->investment_amount . " in ". $coin_nm->coin_name ." was successful!";
                    $sendsms =  send_sms($phonenumber,$commessage);
                }
            if($domain != 'noitavonne.com'){
                $subject = "Investment Successful in ".$coin_nm->coin_name;
                $emailData['subject'] = $subject;
                $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
                $emailData['title'] = $subject;
                $emailData['view'] = 'mail-templates.user-investment';
                $emailData['username'] = $userdata->username;
                $emailData['coinName'] = $coin_nm->coin_name;
                $emailData['investmentAmount'] = $request->investment_amount;
                $emailData['date'] = date('j F Y');
                $emailData['projectName'] = config('app.app_name');
                $emailData['supportMail'] = config('app.support_mail');
                Mail::to($userdata->email)->send(new SendMail($emailData, $emailData['view']));
            }
          }
        
            
            if ($investment) {
                DB::commit();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Investment made successfully.', 'toast' => true, 'data' => ["investment"=> $investment,"required_tokens" => $packageTokenPrice + $auger_tokens, "available_tokens" => $user->account_tokens]]);
            } else {
                DB::rollBack();
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Something went wrong', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('Coin make investment API error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            DB::rollBack();
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }


    public function getUserInvestment(Request $request)
{
    try {
        $page = null;
        $user = $request->attributes->get('user');
    
        $investquery = DB::table('coin_investment')
            ->join('coin', 'coin_investment.coin_id', '=', 'coin.id')
            ->select('coin.id', 'coin.coin_name', 'coin.coin_symbol', 'coin.created_at', 'coin.coin_logo', DB::raw('SUM(coin_investment.investment_amount) as total_investment'))
            ->where('coin_investment.user_id', $user->id)
            ->groupBy('coin.id', 'coin.coin_name', 'coin.coin_symbol', 'coin.created_at', 'coin.coin_logo')
            ->orderBy('coin_investment.created_at', 'desc');

        $getTotalCount = $investquery->count();

        if ($request->filled('search_keyword')) {
            $searchKeyword = $request->search_keyword;
            $keywords = explode(' ', $searchKeyword);
            $investquery->where(function ($query) use ($keywords) {
                foreach ($keywords as $keyword) {
                    $query->where(function ($query) use ($keyword) {
                        $query->where("coin.coin_name", "like", "%{$keyword}%")
                            ->orWhere("coin.coin_symbol", "like", "%{$keyword}%")
                            ->orWhere("coin.description", "like", "%{$keyword}%")
                            ->orWhere("coin.price", "like", "%{$keyword}%");
                    });
                }
            });
        }

        if ($request->filled('page') && $request->filled('limit')) {
            $page = (int)$request->page;
            $limit = (int)$request->limit;
            $start = ($page - 1) * $limit;
    
            $investquery->skip($start)->take($limit);
        }

        $investment = $investquery->get();

        $result = [];
        if ($investment->isNotEmpty()) {
            foreach ($investment as $value) {
                if (!empty($value->coin_logo)) {
                    $value->coin_logo = getFileTemporaryURL("public/" . $value->coin_logo);
                } else {
                    $value->coin_logo = asset('assets/default/images/coin-logo.png');
                }
                $result[] = (array)$value;
            }
        }

        if (!empty($result)) {
            return generateResponse(['type' => 'success','code' => 200,'status' => true,'message' => 'Coin data retrieved successfully','toast' => false,'data' => ["coin" => $result,"page" => $page,"count" => $getTotalCount]]);
        } else {
            return generateResponse(['type' => 'error','code' => 200,'status' => false,'message' => 'Coin data not found','toast' => true,
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Getcoin API error: ' . $e->getMessage());
        return generateResponse(['type' => 'error','code' => 200,'status' => false,'message' => 'Error while processing','toast' => true,]);
    }
}

    

    public function coinInvestor(Request $request)
{
    try {
        $user = $request->attributes->get('user');
        $role_id = $user->role_id;

        if ($role_id == '1' || $role_id == '2') {
            if (isset($request->coin_id)) {
                $coin_id = $request->input('coin_id');
                $investquery = DB::table('coin_investment')
                    ->join('users', 'coin_investment.user_id', '=', 'users.id')
                    ->join('user_profiles', 'coin_investment.user_id', '=', 'user_profiles.user_id')
                    ->select(
                        'users.id as user_id',
                        'users.username',
                        'users.email',
                        'user_profiles.phone_number',
                        'user_profiles.profile_image_path',
                        DB::raw('SUM(coin_investment.investment_amount) as investment_amount')
                    )
                    ->where('coin_investment.coin_id', $coin_id)
                    ->groupBy('users.id', 'users.username', 'users.email', 'user_profiles.phone_number', 'user_profiles.profile_image_path')
                    ->orderBy('investment_amount', 'desc');

                if ($request->filled('search_keyword')) {
                    $searchKeyword = $request->search_keyword;
                    $keywords = explode(' ', $searchKeyword);
                    $investquery->where(function ($query) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            $query->where(function ($query) use ($keyword) {
                                $query->where("users.username", "like", "%{$keyword}%")
                                    ->orWhere("users.email", "like", "%{$keyword}%")
                                    ->orWhere("user_profiles.phone_number", "like", "%{$keyword}%");
                            });
                        }
                    });
                }

                if ($request->filled('page') && $request->filled('limit')) {
                    $page = (int) $request->page;
                    $limit = (int) $request->limit;
                    $start = ($page - 1) * $limit;
                    $investquery->skip($start)->take($limit);
                }

                $getInvestor = $investquery->get();
                $totalinvestors = $investquery->count();
                 $result = [];
            if ($getInvestor->isNotEmpty()) {
                foreach ($getInvestor as $value) {
                    $value->profile_image_path = getFileTemporaryURL($value->profile_image_path);
                    $result[] = (array)$value;
                }
            }
    
                if ($totalinvestors != 0) {
                    return generateResponse([
                        'type' => 'success', 
                        'code' => 200, 
                        'status' => true, 
                        'message' => 'Coin investors retrieved successfully', 
                        'toast' => true, 
                        'data' => [
                            'total_investors' => $totalinvestors,
                            'investors' => $result
                        ]
                    ]);
                } else {
                    return generateResponse([
                        'type' => 'error', 
                        'code' => 200, 
                        'status' => false, 
                        'message' => 'Investors data not found', 
                        'toast' => true,
                    ]);
                }
            }
        } else {
            return generateResponse([
                'type' => 'error',
                'code' => 403,
                'status' => false,
                'message' => 'You don\'t have privilege to perform the task',
                'toast' => true
            ]);
        }
    } catch (\Exception $e) {
        Log::error('Error while retrieving coin data: ' . $e->getMessage());
        return generateResponse([
            'type' => 'error', 
            'code' => 200, 
            'status' => false, 
            'message' => 'Error while processing', 
            'toast' => true
        ]);
    }
}

public function getInvestmentData(Request $request)
{
    try {
        if ($request->has('coin_id')) {
            $coin_id = $request->input('coin_id');
            $investquery = CoinInvestment::where('coin_id', $coin_id)->orderBy('created_at', 'desc');
            $getInvestor = $investquery->get();
            $totalinvestors = $investquery->count();
            $result = [];

            if ($totalinvestors != 0) {
                foreach ($getInvestor as $investor) {
                    $result[] = ['date' => $investor->created_at, 'data' => $investor->investment_amount];
                }
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Coin investment retrieved successfully', 'toast' => true, 'data' => ['total_investors' => $totalinvestors, 'investors' => $result]]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Investors data not found', 'toast' => true]);
            }
        } else {
            return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'coin_id parameter is missing', 'toast' => true]);
        }
    } catch (\Exception $e) {
        Log::error('Error while retrieving coin data: ' . $e->getMessage());
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
}



}
