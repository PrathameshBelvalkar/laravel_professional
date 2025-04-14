<?php

namespace App\Http\Controllers\API\V1\Qr;

use App\Models\QrCode;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\qrcode\QrCodeAddRequest;
use App\Http\Requests\qrcode\QrCodeUpdateRequest;
use App\Http\Requests\qrcode\AddQrSkuRequest;
use App\Models\QR\QRScan;
use App\Models\QR\QrSku;
use App\Models\Subscription\Service;
use App\Models\Subscription\UserServiceSubscription;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode as GenerateQR;

class QrController extends Controller
{
  public function addqr(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $qrService = Service::where("key", "qr")->first();
      $qrSubscription = UserServiceSubscription::selectRaw("
            JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.name')) AS service_name,
            JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.features')), '$.QR.value')) AS QR_value,
            JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.features')), '$.Dynamic.value')) AS Dynamic_value,
            JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.features')), '$.\"ProductQr\".value')) AS Product_QR_value,
            start_date,
            end_date
        ")
        ->where("user_id", $userId)
        ->where("service_id", $qrService->id)
        ->first();
      $subscriptionExpired = false;
      if ($qrSubscription) {
        $qrValue = $qrSubscription->QR_value;
        $dynamicValue = $qrSubscription->Dynamic_value;
        $productQrValue = $qrSubscription->Product_QR_value;
        if (now()->gt($qrSubscription->end_date)) {
          $subscriptionExpired = true;
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Your Subscription plan expired.',
            'toast' => true
          ]);
        }
      } else {
        $qrValue = 10;
        $productQrValue = 0; // Default to 0 if no subscription
      }
      $firstQrCode = QRCode::where('user_id', $userId)->orderBy('created_at', 'asc')->first();

      // Check if this is the user's first QR code
      $isFirstQr = false;
      if (!$firstQrCode) {
        $isFirstQr = true;
      } else {
        $firstQrDate = $firstQrCode->created_at;
        $diffInDays = now()->diffInDays($firstQrDate);
        if ($diffInDays > 14 && !$qrSubscription) {
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Your 14 Day Trial Period has ended.',
            'toast' => true
          ]);
        }
      }

      $generatedCount = QRCode::where('user_id', $userId)->count();
      if ($generatedCount >= $qrValue) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'You have reached the limit of ' . $qrValue . ' QR codes.',
          'toast' => true
        ], ['limit' => true]);
      }

      if (in_array($request->qrcode_type, ['siloproductqr', 'productqr'])) {
        if (empty($request->qr_name) || empty($request->qrcode_data)) {
          $message = $request->qrcode_type == 'siloproductqr'
            ? 'You have to add product name'
            : 'You have to add product url';

          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => $message,
            'toast' => true
          ], ['validation' => true]);
        }

        $productQrCount = QRCode::where('user_id', $userId)
          ->whereIn('qrcode_type', ['siloproductqr', 'productqr'])
          ->count();

        if ($productQrCount >= $productQrValue) {
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'You have reached the limit of ' . $productQrValue . ' product QR codes.',
            'toast' => true
          ], ['limit' => true]);
        }
      }
      $existingQrCode = QRCode::where('user_id', $userId)
        ->where('qr_name', $request->qr_name)
        ->first();

      if ($existingQrCode) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'The QR name "' . $request->qr_name . '" is already in use. Please choose a different name.',
          'toast' => true
        ]);
      }
      $qrcode = new QrCode();
      $qrcode->user_id = $userId;
      $qrcode->qrcode_id = Str::random(10, 'alnum');
      $qrcode->qr_name = $request->qr_name;
      $qrcode->qrcode_data = $request->qrcode_data;

      if ($request->qrcode_type === 'pdf') {
        $qrcode->qrscan_type = '1';
        $qrcode->pdf_path = $request->pdf_path;
        $qrcode->file_key = $request->file_key;
      } elseif ($request->qrcode_type === 'scan') {
        $qrcode->qrscan_type = '2';
      } else {
        $qrcode->qrscan_type = '0';
      }
      $qrcode->qrcode_type = $request->qrcode_type;
      $base64Data = "";
      if ($request->qrcode_type === 'scan') {
        $base64Data = $this->generateQr($request->qrcode_data);
      } else {
        $base64Data = $request->base64_data;
      }
      $base64Data = preg_replace('#data:image/[^;]+;base64,#', '', $base64Data);
      $fileData = base64_decode($base64Data);
      $fileName = "_" . uniqid() . '_' . time() . '.png';
      $filePath = "users/private/{$user->id}/qrcodes/{$qrcode->qrcode_type}{$fileName}";
      Storage::put($filePath, $fileData);

      $qrcode->file_path = $filePath;
      if ($request->has("product_price")) {
        $qrcode->product_price = $request->get("product_price");
      }
      if ($request->has("product_stock")) {
        $qrcode->product_stock = $request->get("product_stock");
      }
      $qrcode->save();
      if ($isFirstQr) {
        $authToken = $request->header('authToken');
        addNotification($userId, $userId, "Congratulations!", "You have just created your first QR", null, "2", "/collections", null, $authToken);
      }

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'QR code added', 'toast' => true], ['eventData' => $qrcode]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error processing the QR', 'toast' => true], ['line' => $e->getLine(), 'message' => $e->getMessage()]);
    }
  }


  public function updateqr(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $id = $request->qr_id;
      $qrcode = QrCode::where('id', $id)
        ->where('user_id', $user->id)
        ->first();

      if (!$qrcode) {
        throw new \Exception("QR code not found for user");
      }

      $base64Data = $request->base64_data;
      $base64Data = preg_replace('#data:image/[^;]+;base64,#', '', $base64Data);
      $fileData = base64_decode($base64Data);

      $filePath = $qrcode->file_path;

      Storage::put($filePath, $fileData);

      DB::commit();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Qr code updated', 'toast' => true], ['eventData' => $qrcode]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Update QR Code Error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error updating the QR code', 'toast' => true]);
    }
  }
  public function deleteqr(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $id = $request->input('qr_id');

      if ($request->deleteIds !== null) {
        $deleteIds = $request->input('deleteIds');
        $qrcodes = QrCode::withTrashed()->where('user_id', $user->id)->whereIn('id', $deleteIds)->get();

        foreach ($qrcodes as $qrcode) {
          if ($qrcode->trashed()) {
            continue;
          }

          $qrcode->delete();
        }

        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'QR Codes Deleted', 'toast' => true]);
      } elseif ($id) {
        $qrcode = QrCode::withTrashed()->where('user_id', $user->id)->where('id', $id)->first();

        if ($qrcode) {
          if ($qrcode->trashed()) {
            DB::rollBack();
            log::info($qrcode);
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'QR already deleted', 'toast' => true]);
          }

          $qrcode->delete();
          DB::commit();
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'QR Deleted', 'toast' => true]);
        } else {
          DB::rollBack();
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'QR not found', 'toast' => true]);
        }
      } else {
        DB::rollBack();
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid request', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('QR code delete error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error processing the event', 'toast' => true]);
    }
  }

  public function fetchqr(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;

      // Base query for fetching QR codes
      $query = QrCode::where('user_id', $userId)
        ->where('qrcode_type', '!=', 'productqr')
        ->where('qrcode_type', '!=', 'siloproductqr')
        ->where('qrcode_type', '!=', 'scan');

      // Apply filters if present
      if ($request->has('searchquery')) {
        $searchQuery = $request->input('searchquery');
        $query->where('qr_name', 'LIKE', '%' . $searchQuery . '%');
      }

      if ($request->has('qr_type')) {
        $qrType = $request->input('qr_type');
        if ($qrType && $qrType !== 'all') {
          $query->where('qrcode_type', $qrType);
        }
      }

      if ($request->has('qrscan_type')) {
        $qrScanType = $request->input('qrscan_type');
        if ($qrScanType !== null && $qrScanType !== 'all') {
          if ($qrScanType === '0') {
            $query->where('qrscan_type', '0');
          } else {
            $query->where('qrscan_type', $qrScanType);
          }
        }
      }

      // Apply sorting
      $sortBy = $request->input('sortby', 'newest');
      if ($sortBy === 'newest') {
        $query->orderBy('created_at', 'desc');
      } elseif ($sortBy === 'oldest') {
        $query->orderBy('created_at', 'asc');
      }

      // Get the total count with the applied filters
      $dataTotal = $query->count();

      // Pagination logic
      $paginate = $request->input('paginate', null);
      if ($paginate) {
        $perPage = intval($paginate);
        $qrcodes = $query->paginate($perPage);
      } else {
        $qrcodes = $query->get();
      }

      // Format the data
      $formattedFiles = $qrcodes->map(function ($qrcode) {
        $base64 = base64_encode(Storage::get($qrcode->file_path));

        return [
          'id' => $qrcode->id,
          'qrcode_id' => $qrcode->qrcode_id,
          'qr_name' => $qrcode->qr_name,
          'type' => $qrcode->qrcode_type,
          'path' => $qrcode->file_path,
          'scans' => $qrcode->scans,
          'isactive' => $qrcode->qrscan_type,
          'timestamp' => $qrcode->created_at->format('Y-m-d H:i:s'),
          'path' => 'data:image/png;base64,' . $base64,
          'downloadFile' => $base64,
          'pdf_link' => $qrcode->file_key,
        ];
      });

      // Return the response
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'QR fetched successfully.',
        'toast' => true
      ], [
        'filesData' => $formattedFiles,
        'recordsTotal' => $dataTotal,
      ]);
    } catch (\Exception $e) {
      Log::info('File add error : ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error Fetching the QR',
        'toast' => true
      ]);
    }
  }


  public function showfiles(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $file_id = $request->input('qr_id');

      $qrcode = QrCode::where('user_id', $userId)->where('id', $file_id)->first();

      if ($qrcode) {
        $filePath = storage_path('app/' . $qrcode->qrcode_img);
        if (file_exists($filePath)) {
          return response()->file($filePath);
        }
      }

      return generateResponse(['type' => 'error', 'code' => 200, 'status' => true, 'message' => 'Files not Found.', 'toast' => true]);
    } catch (\Exception $e) {
      Log::info('File add error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error Fetching the files', 'toast' => true]);
    }
  }

  public function fileqr(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $qrcode_type = "pdf";

      if ($request->file('file')->getSize() > 51200 * 1024) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'File limit exceeds 50MB.',
          'toast' => true
        ]);
      }

      $request->validate([
        'file' => 'required|file|mimes:jpeg,png,gif,pdf|max:51200',
      ]);

      $qrService = Service::where("key", "qr")->first();

      $qrSubscription = UserServiceSubscription::selectRaw("
            JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.name')) AS service_name,
            JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.features')), '$.QR.value')) AS QR_value,
            JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.features')), '$.Dynamic.value')) AS Dynamic_value,
            JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.features')), '$.\"ProductQr\".value')) AS Product_QR_value
        ")
        ->where("user_id", $user->id)
        ->where("service_id", $qrService->id)
        ->first();

      $dynamicValue = $qrSubscription ? $qrSubscription->Dynamic_value : 1;

      $generatedCount = QRCode::where('user_id', $user->id)->where('qrscan_type', '1')->count();

      if ($generatedCount >= $dynamicValue) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'You have reached the limit of ' . $dynamicValue . ' Dynamic QR codes. Please upgrade your plan to add more.',
          'toast' => true
        ], ['limit' => true]);
      }

      $file = $request->file('file');
      $key = strtoupper(Str::random(10));
      $originalName = preg_replace('/[^a-zA-Z0-9.]/', '', $file->getClientOriginalName());
      $formattedName = 'siloqr' . now()->format('YmdHis') . '.' . $file->getClientOriginalExtension();
      $filePath = "users/private/{$user->id}/qrcodes/{$qrcode_type}/{$key}";
      $filePath = Storage::put($filePath, $file);
      $tempurl = getFileTemporaryURL($filePath);

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'File uploaded successfully',
        'toast' => true
      ], ['filePath' => $filePath, 'key' => $key, 'tempurl' => $tempurl]);
    } catch (\Exception $e) {
      Log::info('File add error: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error Fetching the files',
        'toast' => true
      ]);
    }
  }

  public function getEmbedImage(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $qrcodeId = $request->input('qr_id');
      $qrcode = QrCode::where('user_id', $userId)->where('qrcode_id', $qrcodeId)->first();
      // dd($qrcode);
      $dataUrl = "";
      if ($qrcode) {
        $filePath = Storage::get($qrcode->file_path);
        $imageData = base64_encode($filePath);
        $dataUrl = $imageData;
        // return response()->json(['data_url' => $dataUrl]);
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Image Fetched succesfully', 'toast' => true], ['base_64' => $dataUrl]);
      }
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Files not Found.', 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('Error fetching file: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error Fetching the files', 'toast' => true]);
    }
  }


  public function coutQrByUser(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $generatedCount = QRCode::where('user_id', $userId)->count();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'QR count fetched successfully', 'toast' => true], ['count' => $generatedCount]);
    } catch (\Exception $e) {
      Log::info('Get file by key error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error retrieving Count', 'toast' => true]);
    }
  }

  public function getSubscriptionByUser(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $qrService = Service::where("key", "qr")->first();

      $qrSubscription = UserServiceSubscription::selectRaw("
            service_plan_data,
            JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.name')) AS service_name,
            JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.features')), '$.QR.value')) AS QR_value,
            JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.features')), '$.Dynamic.value')) AS Dynamic_value,
            JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.features')), '$.\"ProductQr\".value')) AS Product_QR_value,
            start_date,
            end_date
        ")
        ->where('user_id', $userId)
        ->where('service_id', $qrService->id)
        ->first();

      $qrValue = 10;
      $dynamicValue = 1;
      $productQrValue = 0;
      $subscriptionExpired = false;
      $soonExpire = false;

      if ($qrSubscription) {
        $qrValue = $qrSubscription->QR_value;
        $dynamicValue = $qrSubscription->Dynamic_value;
        $productQrValue = $qrSubscription->Product_QR_value;

        if (now()->gt($qrSubscription->end_date)) {
          $subscriptionExpired = true;
        }

        $daysToEnd = now()->diffInDays($qrSubscription->end_date, false);
        if ($daysToEnd > 0 && $daysToEnd <= 5) {
          $soonExpire = true;
        }
      }

      $generatedCount = QRCode::where('user_id', $userId)->count();
      $productQrCount = QRCode::where('user_id', $userId)->where(function ($query) {
        $query->where('qrcode_type', 'productqr')
          ->orWhere('qrcode_type', 'siloproductqr');
      })->count();
      $dynamicQrCount = QRCode::where('user_id', $userId)->where('qrcode_type', 'pdf')->count();
      $isDynamicExceed = $dynamicQrCount >= $dynamicValue;
      $isProductQrExceed = $productQrCount >= $productQrValue;

      if ($generatedCount >= $qrValue) {
        $message = 'Upgrade your Plan.';
        $deleteSome = false;
        $upgradeModel = true;
        if ($dynamicQrCount < $dynamicValue || $productQrCount < $productQrValue) {
          $remainingDynamic = $dynamicValue - $dynamicQrCount;
          $remainingProduct = $productQrValue - $productQrCount;
          $message = "You have exceeded the overall QR limit but you still have $remainingDynamic dynamic QR(s) and $remainingProduct product QR(s) remaining. You have to delete some of your QR's.";
          $deleteSome = true;
          $upgradeModel = false;
        }

        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => $message,
          'toast' => true
        ], [
          'upgradeModel' => $upgradeModel,
          'subscription' => $qrSubscription,
          'deleteSome' => $deleteSome
        ]);
      }

      if ($qrSubscription) {
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Subscription fetched successfully',
          'toast' => true
        ], [
          'subscription' => $qrSubscription,
          'subscription_end' => $subscriptionExpired,
          'isdynamicexceed' => $isDynamicExceed,
          'isProductQrExceed' => $isProductQrExceed,
          'soon_expire' => $soonExpire
        ]);
      } else {
        $firstQrCode = QRCode::where('user_id', $userId)->orderBy('created_at', 'asc')->first();
        $trialEnd = false;
        $trialEndDate = null;

        if ($firstQrCode) {
          $firstQrDate = $firstQrCode->created_at;
          $trialEndDate = $firstQrDate->copy()->addDays(14)->format('Y-m-d H:i:s');
          $diffInDays = now()->diffInDays($firstQrDate);

          if ($diffInDays > 14) {
            $trialEnd = true;
          }
        }

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => false,
          'message' => 'Subscription not found',
          'toast' => true
        ], [
          'subscription' => $qrSubscription,
          'subscription_end' => $subscriptionExpired,
          'trial_end' => $trialEnd,
          'trial_end_date' => $trialEndDate
        ]);
      }
    } catch (\Exception $e) {
      Log::info('Get file by key error: ' . $e->getMessage());
      Log::info('Get file by key error: ' . $e->getLine());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error receiving type',
        'toast' => true
      ]);
    }
  }

  public function getQrFacts(Request $request)
  {
    try {
      $facts = [
        "QR Origins" => "QR codes were invented by Masahiro Hara in 1994 for Toyota's manufacturing process.",
        "Record Data Density" => "QR codes can store up to 4,296 alphanumeric characters or 7,089 numeric characters.",
        "Invisible QR Codes" => "Transparent QR codes have been developed that can be embedded in images or printed on glass.",
        "Space Art QR" => "A massive QR code covering over 27,000 square meters was carved into a wheat field in Italy in 2019.",
        "Edible Codes" => "Bakeries use edible ink to print QR codes on cakes and pastries for personalized messages.",
        "QR Grave Markers" => "Some cemeteries use QR codes on gravestones to provide digital memorials about the deceased.",
        "Art Authentication" => "Galleries and museums use QR codes to authenticate artworks and provide detailed information.",
        "Interactive Menus" => "Restaurants use QR codes on menus for detailed descriptions, nutritional info, and ordering.",
        "QR in Space" => "NASA uses QR codes on spacecraft components for tracking and maintenance.",
        "QR Charity" => "Charities use QR codes for instant donations by scanning, making giving more accessible."
      ];

      $randomFactKey = array_rand($facts);
      $title = $randomFactKey;
      $message = $facts[$randomFactKey];

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'fact get successfully',
        'toast' => true
      ], [
        'title' => $title,
        'message' => $message
      ]);
    } catch (\Exception $e) {
      Log::info('Get file by key error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Something went wrong', 'toast' => true]);
    }
  }

  public function getUserFiles(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $qrName = QRCode::where('user_id', $userId)->pluck('qr_name');
      $qrcodes = QRCode::where('user_id', $userId)->get(['id', 'qrcode_id']);
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'fact get successfully',
        'toast' => true
      ], [
        'files' => $qrName,
        'files_data' => $qrcodes,
      ]);
    } catch (\Exception $e) {
      Log::info('Get User QR File: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Something went wrong', 'toast' => true]);
    }
  }

  public function setScan(Request $request)
  {
    try {
      $qrCodeId = $request->qr_id;

      $qrCode = QrCode::where('id', $qrCodeId)
        ->first();

      if (!$qrCode) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'QR code not found', 'toast' => true]);
      }

      $qrCode->scans += 1;
      $qrCode->save();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Scan count updated', 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('Get scan issue: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Something went wrong', 'toast' => true]);
    }
  }

  public function getProductQr(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $search = $request->search;
      $qrCodes = QrCode::where('user_id', $userId)
        ->where(function ($query) {
          $query->where('qrcode_type', 'productqr')
            ->orWhere('qrcode_type', 'siloproductqr');
        })
        ->when($search, function ($query, $search) {
          return $query->where('qr_name', 'LIKE', '%' . $search . '%');
        })
        ->orderByDesc('created_at')
        ->get();
      $transformedData = $qrCodes->map(function ($qrCode) {
        $base64 = base64_encode(Storage::get($qrCode->file_path));
        $category = [
          ['label' => 'Other', 'value' => 'other']
        ];
        if ($qrCode->qrcode_type == 'siloproductqr') {
          $category = [
            ['label' => 'SiloMarketPlace', 'value' => 'SiloMarketPlace']
          ];
        }

        return [
          'id' => $qrCode->id,
          'name' => $qrCode->qr_name,
          'img' => 'data:image/png;base64,' . $base64,
          'sku' => "-",
          'price' => $qrCode->product_price ? "$ " . $qrCode->product_price : "-",
          'stock' => $qrCode->product_stock ? $qrCode->product_stock : "-",
          'category' => $category,
          'fav' => false,
          'check' => false,
          'downloadable_image' => $base64
        ];
      });

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'QR code fetched successfully',
        'toast' => true
      ], [
        'products' => $transformedData
      ]);
    } catch (\Exception $e) {
      Log::error('Get Product issue: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Something went wrong',
        'toast' => true
      ]);
    }
  }

  public function subscriptionContains(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $qrService = Service::where("key", "qr")->first();
      $qrSubscription = UserServiceSubscription::selectRaw("
            service_plan_data,
            JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.name')) AS service_name,
            JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.features')), '$.QR.value')) AS QR_value,
            JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.features')), '$.Dynamic.value')) AS Dynamic_value,
            JSON_UNQUOTE(JSON_EXTRACT(JSON_UNQUOTE(JSON_EXTRACT(service_plan_data, '$.features')), '$.\"ProductQr\".value')) AS Product_QR_value
        ")
        ->where('user_id', $userId)
        ->where('service_id', $qrService->id)
        ->whereNull('deleted_at')
        ->first();
      if ($qrSubscription) {
        //Max Count of each subscription
        $qrValue = $qrSubscription->QR_value;
        $dynamicValue = $qrSubscription->Dynamic_value;
        $productQrValue = $qrSubscription->Product_QR_value;

        //Every Count to get the percentage
        $generatedCountstatic = QRCode::where('user_id', $userId)->whereNull('deleted_at')->count(); //  
        $productQrCount = QrCode::where('user_id', $userId)
          ->where(function ($query) {
            $query->where('qrcode_type', 'productqr')
              ->orWhere('qrcode_type', 'siloproductqr');
          })
          ->whereNull('deleted_at')
          ->count();
        $dynamicQrCount = QrCode::where('user_id', $userId)->where('qrcode_type', 'pdf')->whereNull('deleted_at')->count();
        // dd($productQrCount);
        //Now get Every percentage
        // ALl Qr Count
        $allQrPercentage = number_format(($generatedCountstatic / $qrValue) * 100, 1);
        $allDynamicQrPercentage = number_format(($dynamicQrCount / $dynamicValue) * 100, 1);
        $allProductQrPercentage = number_format(($productQrCount / $productQrValue) * 100, 1);

        // Important Message 
        $impmessage = "Your " . $qrValue . " QR code contain " . $productQrValue . " product QR and " . $dynamicValue . " dynamic QR";

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'subscription not Found.', 'toast' => true], ["static_qr" => $qrValue, "dynamic_qr" => $dynamicValue, "product_qr" => $productQrValue, "static_qr_percentage" => $allQrPercentage, "dynamic_qr_percentage" => $allDynamicQrPercentage, "product_qr_percentage" => $allProductQrPercentage, "imp_message" => $impmessage]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'subscription not Found.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::error('Error Getting What subscription contains' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Something went wrong',
        'toast' => true
      ]);
    }
  }
  private function generateQr($text)
  {
    try {
      $qrCode = GenerateQR::format('png')->size(200)->margin(1)->generate($text);
      $base64QrCode = base64_encode($qrCode);
      $qrCodeImage = "data:image/png;base64," . $base64QrCode;
      return $qrCodeImage;
    } catch (\Exception $e) {
      return false;
    }
  }
  public function fetchScan(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $query = QrCode::where('user_id', $userId)
        ->where('qrcode_type', 'scan');
      if ($request->has('searchquery')) {
        $searchQuery = $request->input('searchquery');
        $query->where('qr_name', 'LIKE', '%' . $searchQuery . '%');
      }

      $sortBy = $request->input('sortby', 'newest');
      if ($sortBy === 'newest') {
        $query->orderBy('created_at', 'desc');
      } elseif ($sortBy === 'oldest') {
        $query->orderBy('created_at', 'asc');
      }

      $dataTotal = $query->count();

      $paginate = $request->input('paginate', 3);
      if ($paginate) {
        $perPage = intval($paginate);
        $qrcodes = $query->paginate($perPage);
      } else {
        $qrcodes = $query->get();
      }

      $formattedFiles = $qrcodes->map(function ($qrcode) {
        $base64 = base64_encode(Storage::get($qrcode->file_path));

        return [
          'id' => $qrcode->id,
          'qrcode_id' => $qrcode->qrcode_id,
          'qr_name' => $qrcode->qr_name,
          'type' => $qrcode->qrcode_type,
          'path' => $qrcode->file_path,
          'scans' => $qrcode->scans,
          'isactive' => $qrcode->qrscan_type,
          'timestamp' => $qrcode->created_at->format('Y-m-d H:i:s'),
          'path' => 'data:image/png;base64,' . $base64,
          'downloadFile' => $base64,
        ];
      });

      // Return the response
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Scan QR fetched successfully.',
        'toast' => true
      ], [
        'filesData' => $formattedFiles,
        'recordsTotal' => $dataTotal,
      ]);
    } catch (\Exception $e) {
      Log::info('File add error : ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error Fetching the Scan QR',
        'toast' => true
      ]);
    }
  }
  // public function testNotification(Request $request)
  // {
  //     try {
  //         if ($request->hasHeader('authToken')) {
  //             $authToken = $request->header('authToken');
  //             $decoded = JWT::decode($authToken, new Key(config('app.enc_key'), 'HS256'));
  //             $cloudUserId = $decoded->cloudUserId;
  //             addNotification($cloudUserId, $cloudUserId, "Test!", "This is Test Notification", null, "2", "/collections", null);
  //             $url = 'http://localhost:3005/notification';
  //             $data = [
  //                 'type' => "notification_1",
  //                 'user_id' => $cloudUserId,
  //                 'title' => 'Test Notification',
  //                 'body' => 'This is Test Notification',
  //             ];
  //             $headers = [
  //                 'authToken' => $authToken,
  //             ];
  //             $response = Http::withHeaders($headers)->post($url, $data);
  //             if ($response->successful()) {
  //                 $responseData = $response->json();
  //                 return generateResponse([
  //                     'type' => 'success',
  //                     'code' => 200,
  //                     'status' => true,
  //                     'message' => 'Notification added successfully',
  //                     'toast' => true
  //                 ], ['notification_data' => $responseData]);
  //             } else if ($response->failed()) {
  //                 $responseData = $response->json();
  //                 return generateResponse([
  //                     'type' => 'error',
  //                     'status' => false,
  //                     'message' => 'Failed to add notification',
  //                     'toast' => true
  //                 ], ['code' => $response->status(), 'notification_data' => $responseData]);
  //             }
  //         }
  //     } catch (\Exception $e) {
  //         Log::info('Notification Error' . $e->getMessage());
  //         return generateResponse([
  //             'type' => 'error',
  //             'code' => 200,
  //             'status' => false,
  //             'message' => 'Error adding notification',
  //             'toast' => true
  //         ]);
  //     }
  // }
  public function testNotification(Request $request)
  {
    try {
      $authToken = $request->header('authToken');
      $to_user_ids = $request->get('to_user_ids');
      $module = $request->get('module');
      $decoded = JWT::decode($authToken, new Key(config('app.enc_key'), 'HS256'));
      $cloudUserId = $decoded->cloudUserId;
      addNotification($to_user_ids, $cloudUserId, "Test!", "This is Test Notification", null, $module, "/collections", null, $authToken);
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Notification added successfully',
        'toast' => true
      ]);
    } catch (\Exception $e) {
      Log::info('Notification Error' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error adding notification',
        'toast' => true
      ]);
    }
  }
  public function testBulkNotifications(Request $request)
  {
    try {
      $authToken = $request->header('authToken');
      $decoded = JWT::decode($authToken, new Key(config('app.enc_key'), 'HS256'));
      $cloudUserId = $decoded->cloudUserId;
      // $to_user_ids = [13901, 13731, 14100, 14101, 14102, 14103, 14104];
      $ConnectionandFollowers = getConnectionsAndFollowerUserIds($cloudUserId);
      addNotificationsBulk($ConnectionandFollowers['connection_user_ids'], $cloudUserId, "Test!", "This is Test Notification", null, "2", "/collections", null, $authToken);

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Notification added successfully in bulk',
        'toast' => true
      ], ['data' => $ConnectionandFollowers]);
    } catch (\Exception $e) {
      Log::info('Notification Error' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error adding notification',
        'toast' => true
      ]);
    }

    // $to_user_ids = [1, 3];
    // $from_user_id = 2;
    // $title = 'New Message';
    // $description = 'You have a new message from John Doe.';
    // $reference_id = 123;
    // $module = 'chat';
    // $link = '/chat/123';
    // $is_admin = 0;
    // $auth_token = 'your_auth_token';

    // $notifications = addNotificationsBulk($to_user_ids, $from_user_id, $title, $description, $reference_id, $module, $link, $is_admin, $auth_token);
  }
  public function storeScan(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;

      $qrData = $request->input('qr_data');

      if (!$qrData) {
        return generateResponse([
          'type' => 'error',
          'code' => 400,
          'status' => false,
          'message' => 'No QR data provided',
          'toast' => true
        ]);
      }

      $qrScan = QRScan::firstOrCreate(
        ['user_id' => $userId],
        ['scanned_data' => json_encode([])]
      );

      $scannedData = json_decode($qrScan->scanned_data, true);

      $newData = [
        'id' => count($scannedData) + 1,
        'scanned_data' => $qrData,
      ];

      $scannedData[] = $newData;

      $qrScan->scanned_data = json_encode($scannedData);
      $qrScan->save();

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'QR scanned successfully',
        'toast' => true
      ]);
    } catch (\Exception $e) {
      Log::info('Scanning QR Error: ' . $e->getMessage());

      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error adding scanned QR',
        'toast' => true
      ]);
    }
  }
  public function getScannedDataByUserId(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;

      $qrScan = QRScan::where('user_id', $userId)->first();

      if (!$qrScan) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'No scanned data found for this user',
          'toast' => true
        ]);
      }

      $scannedData = json_decode($qrScan->scanned_data, true);
      $scannedDataWithType = array_map(function ($item) {
        $item['type'] = $this->detectQRType($item['scanned_data']);
        return $item;
      }, $scannedData);

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Scanned data retrieved successfully',
        'toast' => true
      ], ['scanned_data' => $scannedDataWithType]);
    } catch (\Exception $e) {
      Log::info('Error fetching scanned data: ' . $e->getMessage());

      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error fetching scanned data',
        'toast' => true
      ]);
    }
  }

  private function detectQRType($data)
  {
    if (filter_var($data, FILTER_VALIDATE_URL)) {
      return 'link';
    } elseif (preg_match('/^WIFI:/', $data)) {
      return 'wifi';
    } elseif (preg_match('/^BEGIN:VCARD/', $data)) {
      return 'contact_info';
    } elseif (preg_match('/^geo:/', $data)) {
      return 'map';
    } else {
      return 'text';
    }
  }

  //QR SKU 

  public function addQRSku(AddQrSkuRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');

      $qrSku = new QrSku();

      $qrSku->user_id = $user->id;
      $qrSku->sku_id = Str::uuid();
      $qrSku->product_name = $request->product_name;
      $qrSku->brand = $request->brand;
      $qrSku->stock = $request->stock;
      $qrSku->sku_code = $request->sku_code;
      $qrSku->category = $request->category;
      $qrSku->sub_category = $request->sub_category;
      $qrSku->material = $request->material;
      $qrSku->color = $request->color;
      $qrSku->size = $request->size;
      $qrSku->weight = $request->weight;
      $qrSku->price = $request->price;
      $qrSku->cost_price = $request->cost_price;
      $qrSku->currency = $request->currency;
      $qrSku->quantity_in_stock = $request->quantity_in_stock;
      $qrSku->reorder_level = $request->reorder_level;
      $qrSku->supplier = $request->supplier;
      $qrSku->minimum_order_quantity = $request->minimum_order_quantity;
      $qrSku->short_description = $request->short_description;
      $qrSku->full_description = $request->full_description;

      $imageData = [];
      if ($request->hasFile('file_path')) {
        foreach ($request->file('file_path') as $index => $image) {
          $imageName = $user->id . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
          $imagePath = "users/private/{$user->id}/QRCodes/SKU/{$imageName}";
          Storage::put($imagePath, file_get_contents($image));
          $imageData[] = [
            'id' => $index + 1,
            'path' => $imagePath,
          ];
        }
        $qrSku->file_path = json_encode($imageData);
      }

      if ($request->hasFile('sku_pdf')) {
        $file = $request->file('sku_pdf');
        $fileName = $file->getClientOriginalName();
        $sku_pdf_Path = "users/private/{$user->id}/QRCodes/SKU/{$fileName}";
        Storage::put($sku_pdf_Path, file_get_contents($file));
        //  $sku_pdf_Path = substr($sku_pdf_Path, strlen('users/'));
        $qrSku->sku_pdf = $sku_pdf_Path;
      }

      $qrSku->save();

      DB::commit();
      return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'SKU details added successfully', 'toast' => true, 'data' => $qrSku]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error adding SKU: ' . $e->getMessage());
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error adding SKU details: ' . $e->getMessage(), 'toast' => true]);
    }
  }
  public function getQrSku(Request $request)
  {
    try {

      $sku_id = $request->sku_id;
      $qrSku = QrSku::where('sku_id', $sku_id)->first();

      if (!$qrSku) {
        return response(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'SKU not found',], 404);
      }

      $filePaths = json_decode($qrSku->file_path, true);
      foreach ($filePaths as &$file) {
        $file['temporary_url'] = getFileTemporaryURL($file['path']);
      }
      $qrSku->file_path = $filePaths;

      if ($qrSku->sku_pdf) {
        $pdfPath = $qrSku->sku_pdf;
        $qrSku->sku_pdf_url = getFileTemporaryURL($pdfPath);
      }
      return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'SKU details retrieved successfully', 'data' => $qrSku,], 200);
    } catch (\Exception $e) {
      Log::error('Error retrieving SKU: ' . $e->getMessage());
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error retrieving SKU details: ' . $e->getMessage(),], 500);
    }
  }
  public function updateQrSku(Request $request)
  {
    DB::beginTransaction();
    try {

      $user = $request->attributes->get('user');
      $qrSku = $request->id;
      $qrSku = QrSku::where('id', $qrSku)->where('user_id', $user->id)->first();
      if (!$qrSku) {
        return response(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'SKU not found',], 404);
      }

      $qrSku->product_name = $request->product_name ?? $qrSku->product_name;
      $qrSku->brand = $request->brand ?? $qrSku->brand;
      $qrSku->stock = $request->stock ?? $qrSku->stock;
      $qrSku->sku_code = $request->sku_code ?? $qrSku->sku_code;
      $qrSku->category = $request->category ?? $qrSku->category;
      $qrSku->sub_category = $request->sub_category ?? $qrSku->sub_category;
      $qrSku->material = $request->material ?? $qrSku->material;
      $qrSku->color = $request->color ?? $qrSku->color;
      $qrSku->size = $request->size ?? $qrSku->size;
      $qrSku->weight = $request->weight ?? $qrSku->weight;
      $qrSku->price = $request->price ?? $qrSku->price;
      $qrSku->cost_price = $request->cost_price ?? $qrSku->cost_price;
      $qrSku->currency = $request->currency ?? $qrSku->currency;
      $qrSku->quantity_in_stock = $request->quantity_in_stock ?? $qrSku->quantity_in_stock;
      $qrSku->reorder_level = $request->reorder_level ?? $qrSku->reorder_level;
      $qrSku->supplier = $request->supplier ?? $qrSku->supplier;
      $qrSku->minimum_order_quantity = $request->minimum_order_quantity ?? $qrSku->minimum_order_quantity;
      $qrSku->short_description = $request->short_description ?? $qrSku->short_description;
      $qrSku->full_description = $request->full_description ?? $qrSku->full_description;

      $imageData = [];
      if ($request->hasFile('file_path')) {
        $user = $request->attributes->get('user');

        $existingFilePaths = json_decode($qrSku->file_path, true) ?? [];
        foreach ($existingFilePaths as $file) {
          Storage::delete($file['path']);
        }

        foreach ($request->file('file_path') as $index => $image) {
          $imageName = $user->id . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
          $imagePath = "users/private/{$user->id}/QRCodes/SKU/{$imageName}";
          Storage::put($imagePath, file_get_contents($image));
          $imageData[] = [
            'id' => $index + 1,
            'path' => $imagePath,
          ];
        }
        $qrSku->file_path = json_encode($imageData);
      }

      $qrSku->save();

      DB::commit();
      return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'SKU updated successfully', 'data' => $qrSku,], 200);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error updating SKU: ' . $e->getMessage());
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error updating SKU: ' . $e->getMessage(),], 500);
    }
  }
}
