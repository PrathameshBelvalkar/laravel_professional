<?php

namespace App\Http\Controllers\API\V1\Wallet;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetDataRequest;
use App\Http\Requests\Wallet\External\AddExternalWalletRequest;
use App\Http\Requests\Wallet\External\RemoveExternalWalletRequest;
use App\Models\Wallet\ExternalWallet;
use App\Models\Wallet\ExternalWalletMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ExternalWalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(GetDataRequest $request)
    {
        try {
            $user = $request->attributes->get('user');
            $limit = isset($request->limit) ? $request->limit : 10;
            $page = isset($request->page) && $request->page ? $request->page : 1;
            $order_by = isset($request->order_by) ? "ew." . $request->order_by : "ew.updated_at";
            $order = isset($request->order) ? $request->order : "desc";
            $offset = ($page - 1) * $limit;
            $walletQuery = DB::table("external_wallets as ew");
            $walletQuery->selectRaw('ew.id,ew.external_wallet_masters_id,ew.funding_wallet_key,ew.trading_wallet_key,m.name,m.logo,m.shortname');
            $wallets = $walletQuery->limit($limit)
                ->offset($offset)
                ->orderBy($order_by, $order)
                ->where("user_id", $user->id)
                ->where('ew.status', '1')
                ->leftJoin("external_wallet_masters as m", "ew.external_wallet_masters_id", "=", "m.id")
                ->get();
            if (!$wallets->isEmpty()) {
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Wallets found', 'toast' => true], ['wallets' => $wallets->toArray()]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No wallet found', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('ExternalWalletController Error in file index ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AddExternalWalletRequest $request)
    {
        try {
            DB::beginTransaction();
            $external_wallet_masters_id = $request->external_wallet_masters_id;
            $key = $request->key;
            $type = $request->type;

            $user = $request->attributes->get('user');

            $wallet = ExternalWallet::where('user_id', $user->id)->where("external_wallet_masters_id", $external_wallet_masters_id)->first();
            if (!$wallet)
                $wallet = new ExternalWallet();
            $wallet->external_wallet_masters_id = $external_wallet_masters_id;
            $wallet->user_id = $user->id;
            $wallet->$type = $key;
            $wallet->status = "1";
            $wallet->save();

            DB::commit();

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Wallet added', 'toast' => true], ['wallet' => $wallet->toArray()]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('AddressController Error in file store ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ExternalWallet $externalWallet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ExternalWallet $externalWallet)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ExternalWallet $externalWallet)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RemoveExternalWalletRequest $request, string $id)
    {
        $validator = Validator::make(['id' => $id], [
            'id' => ['required', Rule::exists("external_wallets", "id")->where("status", "1")->whereNull("deleted_at")],
        ]);
        if ($validator->fails()) {
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid wallet ID', 'toast' => true]);
        }
        try {
            DB::beginTransaction();
            $user = $request->attributes->get('user');
            $type = $request->type;
            $wallet = ExternalWallet::where('user_id', $user->id)->where("id", $id)->first();
            if ($wallet) {
                $wallet->$type = null;
                $wallet->save();
                DB::commit();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'External wallet deleted', 'toast' => true]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No wallet found', 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('AddressController Error in file destroy ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function externalWalletMasterList(GetDataRequest $request)
    {
        try {
            $user = $request->attributes->get('user');
            $limit = isset($request->limit) ? $request->limit : 10;
            $page = isset($request->page) && $request->page ? $request->page : 1;
            $search_keyword = isset($request->search_keyword) ? $request->search_keyword : "";
            $offset = ($page - 1) * $limit;

            $externalWalletMasterQuery = ExternalWalletMaster::query();
            if ($search_keyword) {
                $externalWalletMasterQuery->where(function ($query) use ($search_keyword) {
                    $query->where('name', 'like', "%{$search_keyword}%")
                        ->orWhere('shortname', 'like', "%{$search_keyword}%");
                });
            }
            $externalWalletMasterQuery->limit($limit)->offset($offset);

            $list = $externalWalletMasterQuery->selectRaw('id,name,shortname,logo')->get();
            if ($list->isNotEmpty()) {
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Wallet list retreived', 'toast' => true], ['list' => $list->toArray()]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No wallets found', 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('Add Payment Error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
}
