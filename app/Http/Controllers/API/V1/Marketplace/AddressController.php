<?php

namespace App\Http\Controllers\API\V1\Marketplace;

use App\Models\Marketplace\Address;
use App\Http\Controllers\Controller;
use App\Http\Requests\Store\AddAddressRequest;
use App\Http\Requests\Store\GetAddressRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Country;

class AddressController extends Controller
{
  /**
   * Display a listing of the resource.
   */
  // public function index(GetAddressRequest $request)
  // {
  //   try {
  //     $user = $request->attributes->get('user');
  //     $limit = isset($request->limit) ? $request->limit : 10;
  //     $page = isset($request->page) && $request->page ? $request->page : 1;
  //     $search = isset($request->search) ? $request->search : "";
  //     $order_by = isset($request->order_by) ? $request->order_by : "updated_at";
  //     $order = isset($request->order) ? $request->order : "desc";
  //     $offset = ($page - 1) * $limit;

  //     $query = DB::table('addresses')->select(['id', 'zipcode', 'state', 'country', 'city', 'address_line_2', 'address_line_1', 'updated_at', 'country_code', 'phone_number']);

  //     if (!empty($search)) {
  //       $query->where(function ($query) use ($search) {
  //         $query->where('address_line_1', 'LIKE', "%$search%")
  //           ->orWhere('state', 'LIKE', "%$search%")
  //           ->orWhere('city', 'LIKE', "%$search%");
  //       });
  //     }

  //     $addresses = $query->limit($limit)
  //       ->offset($offset)
  //       ->orderBy($order_by, $order)
  //       ->where("user_id", $user->id)
  //       ->whereNull('deleted_at')
  //       ->get();

  //     if (!$addresses->isEmpty()) {
  //       return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Addresses found', 'toast' => true], ['addresses' => $addresses]);
  //     } else {
  //       return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No addresses found', 'toast' => true]);
  //     }
  //   } catch (\Exception $e) {
  //     Log::info('AddressController Error in file index ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
  //     return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
  //   }
  // }

  public function index(GetAddressRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      $limit = isset($request->limit) ? $request->limit : 10;
      $page = isset($request->page) && $request->page ? $request->page : 1;
      $search = isset($request->search) ? $request->search : "";
      $order_by = isset($request->order_by) ? $request->order_by : "updated_at";
      $order = isset($request->order) ? $request->order : "desc";
      $offset = ($page - 1) * $limit;

      $query = DB::table('addresses')
        ->join('countries', 'addresses.country_code', '=', 'countries.phonecode')
        ->select([
          'addresses.id',
          'addresses.zipcode',
          'addresses.state',
          'addresses.country',
          'addresses.city',
          'addresses.address_line_2',
          'addresses.address_line_1',
          'addresses.updated_at',
          'addresses.country_code',
          'addresses.phone_number',
          'countries.shortname as country_shortname'
        ])
        ->where("addresses.user_id", $user->id)
        ->whereNull('addresses.deleted_at');

      if (!empty($search)) {
        $query->where(function ($query) use ($search) {
          $query->where('addresses.address_line_1', 'LIKE', "%$search%")
            ->orWhere('addresses.state', 'LIKE', "%$search%")
            ->orWhere('addresses.city', 'LIKE', "%$search%");
        });
      }

      $addresses = $query->limit($limit)
        ->offset($offset)
        ->orderBy($order_by, $order)
        ->get();

      if (!$addresses->isEmpty()) {
        return generateResponse(
          ['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Addresses found', 'toast' => true],
          ['addresses' => $addresses]
        );
      } else {
        return generateResponse(
          ['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No addresses found', 'toast' => true]
        );
      }
    } catch (\Exception $e) {
      Log::info('AddressController Error in file index ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse(
        ['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]
      );
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
  public function store(AddAddressRequest $request)
  {
    try {
      DB::beginTransaction();
      $address_line_1 = $request->address_line_1;
      $city = $request->city;
      $state = $request->state;
      $country = $request->country;
      $phone_number = $request->phone_number;
      $country_code = $request->country_code;
      $zipcode = $request->zipcode;
      $location = isset($request->location) ? $request->location : null;

      $user = $request->attributes->get('user');

      $address = new Address();
      $address->address_line_1 = $address_line_1;
      $address->city = $city;
      $address->state = $state;
      $address->country = $country;
      $address->phone_number = $phone_number;
      $address->country_code = $country_code;
      $address->zipcode = $zipcode;
      $address->location = $location;
      $address->user_id = $user->id;
      $address->save();

      DB::commit();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Address added', 'toast' => true], ['address' => $address->toArray()]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('AddressController Error in file store ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  /**
   * Display the specified resource.
   */
  // public function show(Request $request, string $id)
  // {
  //     $validator = Validator::make(['id' => $id], [
  //         'id' => 'required|integer',
  //     ]);
  //     if ($validator->fails()) {
  //         return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid address ID', 'toast' => true]);
  //     }
  //     try {
  //         $user = $request->attributes->get('user');
  //         $address = Address::where("id", $id)->where('user_id', $user->id)->select(['zipcode', 'state', 'country', 'city', 'address_line_2', 'address_line_1', 'updated_at', 'country_code', 'phone_number'])->first();
  //         if ($address && $address->toArray()) {         // Return the address data in a JSON response
  //             return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Address found', 'toast' => true], ['address' => $address->toArray()]);
  //         } else {
  //             return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Address not found', 'toast' => true]);
  //         }
  //     } catch (\Exception $e) {
  //         Log::info('AddressController Error in file show ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
  //         return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
  //     }
  // }
  public function show(Request $request, string $id)
  {
    $validator = Validator::make(['id' => $id], [
      'id' => 'required|integer',
    ]);

    if ($validator->fails()) {
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Invalid address ID',
        'toast' => true
      ]);
    }

    try {
      $user = $request->attributes->get('user');

      $address = DB::table('addresses')
        ->join('countries', 'addresses.country_code', '=', 'countries.phonecode')
        ->select([
          'addresses.zipcode',
          'addresses.state',
          'addresses.country',
          'addresses.city',
          'addresses.address_line_2',
          'addresses.address_line_1',
          'addresses.updated_at',
          'addresses.country_code',
          'addresses.phone_number',
          'countries.shortname as country_shortname'
        ])
        ->where('addresses.id', $id)
        ->where('addresses.user_id', $user->id)
        ->whereNull('addresses.deleted_at')
        ->first();

      if ($address) {
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Address found',
          'data' => ['address' => $address],
          'toast' => true
        ]);
      } else {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Address not found',
          'toast' => true
        ]);
      }
    } catch (\Exception $e) {
      Log::info('AddressController Error in file show ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error while processing',
        'toast' => true
      ]);
    }
  }

  /**
   * Show the form for editing the specified resource.
   */
  public function edit(string $id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {

    $validator = Validator::make(['id' => $id], [
      'id' => 'required|integer',
    ]);
    if ($validator->fails()) {
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid address ID', 'toast' => true]);
    }
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $address = Address::where("id", $id)->where('user_id', $user->id)->first();
      if ($address && $address->toArray()) {
        $address->address_line_1 = isset($request->address_line_1) ? $request->address_line_1 : $address->address_line_1;
        $address->address_line_2 = isset($request->address_line_2) ? $request->address_line_2 : $address->address_line_2;
        $address->city = isset($request->city) ? $request->city : $address->city;
        $address->state = isset($request->state) ? $request->state : $address->state;
        $address->country = isset($request->country) ? $request->country : $address->country;
        $address->country_code = isset($request->country_code) ? $request->country_code : $address->country_code;
        $address->phone_number = isset($request->phone_number) ? $request->phone_number : $address->phone_number;
        $address->zipcode = isset($request->zipcode) ? $request->zipcode : $address->zipcode;
        $address->location = isset($request->location) ? $request->location : $address->location;
        $address->save();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Address updated', 'toast' => true], ['address' => $address->toArray()]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Address not found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('AddressController Error in file update ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(Request $request, string $id)
  {
    $validator = Validator::make(['id' => $id], [
      'id' => 'required|integer',
    ]);
    if ($validator->fails()) {
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid address ID', 'toast' => true]);
    }
    try {
      DB::beginTransaction();
      $user = $request->attributes->get('user');
      $address = Address::where("id", $id)->where('user_id', $user->id)->first();
      if ($address && $address->toArray()) {
        $address->delete();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Address deleted', 'toast' => true]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Address not found', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::info('AddressController Error in file delete ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
}
