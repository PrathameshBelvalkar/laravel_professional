<?php

namespace App\Http\Controllers\API\V1\Admin;

use services;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Subscription\Package;
use App\Models\Subscription\Service;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Admin\AddPackageRequest;
use App\Http\Requests\Admin\AddServiceRequest;

class SubscriptionController extends Controller
{
    public function getService(Request $request)
    {
        DB::beginTransaction();
        try {
            $query = Service::query();

            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where('name', 'like', "%$searchTerm%")
                    ->orWhere('is_external_app
                    ', 'like', "%$searchTerm%");
            }

            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            $query->offset($offset)->limit($limit);

            $products = $query->get();

            if ($products->isEmpty()) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Service data not found', 'toast' => true]);
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Services data retrieved successfully', 'toast' => false, 'data' => ["users" => $products]]);
        } catch (\Exception $e) {
            Log::error('public API error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function addService(AddServiceRequest $request)
    {
        DB::beginTransaction();
        try {
            $service = new Service();
           
            if ($request->name) {
                $service->name = $request->name;
            }
            if ($request->description) {
                $service->description = $request->description;
            }
            if ($request->is_external_app) {
                $service->is_external_app = $request->is_external_app;
            }
            if ($request->link) {
                $service->link = $request->link;
            }
            if ($request->trial_period) {
                $service->trial_period = $request->trial_period;
            }
            if ($request->key) {
                $service->key = $request->key;
            }
            if ($request->hasFile('files')) {
                $uploadfile = $request->file('files');
                $fileName = $service->id . '.' . $uploadfile->getClientOriginalExtension();
                $filePath = "users/private/{$service->id}/services/{$fileName}";
                Storage::put($filePath, file_get_contents($uploadfile));
                $service->logo = $filePath;
            }
            
            DB::commit();
            $service->save();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'New Service Added', 'toast' => true, 'data' => ["profile" => $service->toArray()]]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::info('profile Error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function deleteService(Request $request)
    {
        $service = Service::find($request->id);

        if (!$service) {
            return response()->json(['error' => 'service not found'], 404);
        }

        $service->delete();

        return response()->json(['message' => 'service deleted successfully'], 200);
    }
    public function updateService(AddServiceRequest $request)
    {
        try {
            $service = Service::find($request->id);
            

            if ($service) {
                $service = service::where('id', $service->id)->first();
                if (!$service) {
                    $service = new service();
                    $service->id;
                }
                if (isset($request->name)) {
                    $service->name = $request->name;
                }
                if (isset($request->description)) {
                    $service->description = $request->description;
                }
                if (isset($request->link)) {
                    $service->link = $request->link;
                }
                if (isset($request->trial_period)) {
                    $service->trial_period = $request->trial_period;
                }
                if (isset($request->key)) {
                    $service->key = $request->key;
                }
                if (isset($request->is_external_app)) {
                    $service->is_external_app = $request->is_external_app;
                }
                if (($request->hasFile('files'))) {
                    $uploadfile = $request->file('files');
                    $fileName = $service->id . '.' . $uploadfile->getClientOriginalExtension();
                    $filePath = "users/private/{$service->id}/services/{$fileName}";
                    Storage::put($filePath, file_get_contents($uploadfile));
                    $service->logo = $filePath;
                }
                $service->save();
                DB::commit();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Service updated', 'toast' => true, 'data' => ["profile" => $service->toArray()]]);
            } else {
                return generateResponse(['type' => 'Error', 'code' => 400, 'status' => false, 'message' => 'Service not  found', 'toast' => true]);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::info('profile Error : ' . $e->getMessage() . '' . $e->getFile() . '' . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function inactiveService(Request $request)
    {
        try {
            $serviceId = $request->id;
            $service = Service::findOrFail($serviceId);

            if ($request->status === '0') {
                $service->status = '0';
            } elseif ($request->status === '1') {
                $service->status = '1';
            } else {
                return response()->json(['error' => 'Value must be 0 or 1'], 400);
            }
            $service->save();

            return response()->json(['message' => 'User  status changed'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while processing the request'], 500);
        }
    }

    public function addPackage(AddPackageRequest $request)
    {
        DB::beginTransaction();
        try {
            $package = new Package();
           
            if ($request->name) {
                $package->name = $request->name;
            }
            if ($request->monthly_price) {
                $package->monthly_price = $request->monthly_price;
            }
            if ($request->quarterly_price) {
                $package->quarterly_price = $request->quarterly_price;
            }
            if ($request->yearly_price) {
                $package->yearly_price = $request->yearly_price;
            }
            if ($request->key) {
                $package->key = $request->key;
            }
            if ($request->services) {
                $package->services = $request->services;
            }
            if ($request->type === '1' || $request->type === '2' || $request->type === '3') {
                $package->type = $request->type;
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'The value must be 1, 2, or 3', 'toast' => true]);
            }
            
            if ($request->hasFile('files')) {
                $uploadfile = $request->file('files');
                $fileName = $package->id . '.' . $uploadfile->getClientOriginalExtension();
                $filePath = "users/private/{$package->id}/services/{$fileName}";
                Storage::put($filePath, file_get_contents($uploadfile));
                $package->thumbnail = $filePath;
            }
            
            DB::commit();
            $package->save();
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'New package Added', 'toast' => true, 'data' => ["profile" => $package->toArray()]]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::info('profile Error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function getPackage(Request $request)
    {
        DB::beginTransaction();
        try {
            $query = Package::query();

            if ($request->filled('search')) {
                $searchTerm = $request->input('search');
                $query->where('name', 'like', "%$searchTerm%")
                    ->orWhere('key', 'like', "%$searchTerm%");
            }

            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            $query->offset($offset)->limit($limit);

            $products = $query->get();

            if ($products->isEmpty()) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Package  not found', 'toast' => true]);
            }

            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Package data retrieved successfully', 'toast' => false, 'data' => ["users" => $products]]);
        } catch (\Exception $e) {
            Log::error('public API error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function deletePackage(Request $request)
    {
        $package = Package::find($request->id);

        if (!$package) {
            return response()->json(['error' => 'Package not found'], 404);
        }

        $package->delete();

        return response()->json(['message' => 'Package deleted successfully'], 200);
    }
    public function updatePackage(AddPackageRequest $request)
    {
        try {
            $package = Package::find($request->id);
            

            if ($package) {
                $package = package::where('id', $package->id)->first();
                if (!$package) {
                    $package = new package();
                    $package->id;
                }
                if (isset($request->name)) {
                    $package->name = $request->name;
                }
                if (isset($request->monthly_price)) {
                    $package->monthly_price = $request->monthly_price;
                }
                if (isset($request->quarterly_price)) {
                    $package->quarterly_price = $request->quarterly_price;
                }
                if (isset($request->yearly_price)) {
                    $package->yearly_price = $request->yearly_price;
                }
                if (isset($request->key)) {
                    $package->key = $request->key;
                }
                if ($request->services) {
                    $package->services = $request->services;
                }
                if ($request->type === '1' || $request->type === '2' || $request->type === '3') {
                    $package->type = $request->type;
                } else {
                    return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'The value must be 1, 2, or 3', 'toast' => true]);
                }
                if (($request->hasFile('files'))) {
                    $uploadfile = $request->file('files');
                    $fileName = $package->id . '.' . $uploadfile->getClientOriginalExtension();
                    $filePath = "users/private/{$package->id}/services/{$fileName}";
                    Storage::put($filePath, file_get_contents($uploadfile));
                    $package->thumbnail = $filePath;
                }
                $package->save();
                DB::commit();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Package updated', 'toast' => true, 'data' => ["profile" => $package->toArray()]]);
            } else {
                return generateResponse(['type' => 'Error', 'code' => 400, 'status' => false, 'message' => 'Package not  found', 'toast' => true]);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::info('profile Error : ' . $e->getMessage() . '' . $e->getFile() . '' . $e->getLine());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    
}
