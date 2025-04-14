<?php

namespace App\Http\Controllers\API\V1\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Influencer;
use App\Models\Marketplace\MarketPlaceLive;
use App\Models\Marketplace\LiveProductPin;
use App\Models\Marketplace\MarketplaceViews;
use App\Models\Marketplace\InfluencerConnection;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\MarketplaceProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use function Laravel\Prompts\table;

class MarketPlaceLiveController extends Controller
{
  public function createmarketplacelivestream(Request $request)
  {
    DB::beginTransaction();
    $livestream = new MarketPlaceLive();
    try {
      $existingLiveStream = MarketPlaceLive::where('stream_title', $request->stream_title)->first();
      if ($existingLiveStream) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'The live stream title has already been taken.',
          'toast' => true,
          'data' => []
        ]);
      }

      $user = $request->attributes->get('user');
      $livestream->user_id = $user->id;
      $livestream->broadcast_id = Str::uuid();
      if (!$request->filled('stream_title')) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Live stream title not provided',
          'toast' => true,
          'data' => []
        ]);
      }
      $livestream->stream_title = $request->stream_title;
      if ($request->filled('product_id')) {
        $productIdsJson = $request->product_id;
        $productIdsArray = json_decode($productIdsJson, true);
        if (is_array($productIdsArray)) {
          $formattedProductIds = implode(',', $productIdsArray);
          $livestream->product_ids = $formattedProductIds;
        } else {
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Invalid product IDs format',
            'toast' => true,
            'data' => []
          ]);
        }
      } else {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Products Not Selected',
          'toast' => true,
          'data' => []
        ]);
      }
      if (!$request->filled('stream_key_id')) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Stream key id not provided',
          'toast' => true,
          'data' => []
        ]);
      }
      $existingLiveStream = MarketPlaceLive::where('stream_key_id', $request->stream_key_id)->first();
      if ($existingLiveStream) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'The stream key id has already been taken.',
          'toast' => true,
          'data' => []
        ]);
      }
      $livestream->stream_key_id = $request->stream_key_id;
      $livestream->stream_name = $request->stream_name;
      $livestream->stream_key = hash('sha256', Str::random(32));
      $livestream->playback_url_key = hash('sha256', Str::random(32));
      if ($request->hasFile('stream_banner')) {
        $imageFile = $request->file('stream_banner');
        if (in_array($imageFile->getClientOriginalExtension(), ['jpeg', 'jpg', 'png'])) {
          $fileName = $imageFile->getClientOriginalName();
          $imagepath = "users/private/{$user->id}/influencer/{$request->stream_key_id}/banners/{$fileName}";
          Storage::put($imagepath, file_get_contents($imageFile));
          $livestream->stream_banner = $imagepath;
        } else {
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'Only JPEG, JPG, and PNG files are allowed.',
            'toast' => true
          ]);
        }
      }
      if ($request->filled('schedule_date_time')) {
        $livestream->schedule_date_time = $request->schedule_date_time;
      } else {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Schedule date and time not provided',
          'toast' => true
        ]);
      }
      $livestream->save();
      DB::commit();

      $newlivestream = MarketPlaceLive::where('id', $livestream->id)->first();
      $livestream_data = [
        'id' => $newlivestream->id,
        'stream_title' => $newlivestream->stream_title,
        'stream_key_id' => $newlivestream->stream_key_id,
        'stream_key' => $newlivestream->stream_key,
        'playback_url_key' => $newlivestream->playback_url_key,
        'broadcasted_id' => $newlivestream->broadcast_id,
        'schedule_date_time' => $newlivestream->schedule_date_time,
        'navigation_url' => "live-streams/create/" . $newlivestream->stream_key_id
      ];

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Live stream created successfully.',
        'toast' => true,
        'data' => ['livestream_data' => $livestream_data]
      ]);
    } catch (\Exception $e) {
      Log::info('Error while creating live stream: ' . $e->getMessage());
      DB::rollBack();
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error creating live stream.',
        'toast' => true
      ]);
    }
  }

  public function updatemarketplacelivestream(Request $request)
  {
    try {
      if (!$request->filled('stream_key_id')) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Stream key id not provided',
          'toast' => true,
          'data' => []
        ]);
      }

      if (!$request->filled('product_id')) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Products not provided',
          'toast' => true,
          'data' => []
        ]);
      }

      $livestream = MarketPlaceLive::where('stream_key_id', $request->stream_key_id)->first();

      if (!$livestream) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Live stream not found',
          'toast' => true,
          'data' => []
        ]);
      }

      $existingProductIds = $livestream->product_ids ? explode(',', $livestream->product_ids) : [];

      $productIdsJson = $request->product_id;
      $newProductIdsArray = json_decode($productIdsJson, true);

      if (!is_array($newProductIdsArray)) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Invalid product IDs format',
          'toast' => true,
          'data' => []
        ]);
      }

      $mergedProductIds = array_unique(array_merge($existingProductIds, $newProductIdsArray));

      $livestream->product_ids = implode(',', $mergedProductIds);

      if ($request->filled('schedule_date_time')) {
        $livestream->schedule_date_time = $request->schedule_date_time;
      }
      $livestream->save();

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Live stream updated successfully.',
        'toast' => true,
        'data' => []
      ]);
    } catch (\Exception $e) {
      Log::info('Error while updating live stream: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error updating live stream.',
        'toast' => true,
        'data' => []
      ]);
    }
  }
  public function deletemarketplacelivestream(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $stream_id = $request->stream_id;
      $LiveStream = MarketPlaceLive::where('user_id', $userId)->where('id', $stream_id)->first();

      if (!$LiveStream) {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Id not found.',
          'toast' => true
        ]);
      }

      $LiveStream->delete();
      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Live stream deleted successfully.', 'toast' => true]);
    } catch (\Exception $e) {
      Log::info('Error while deleting live stream: ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error deleting live stream.', 'toast' => true]);
    }
  }

  public function getmarketplacelivestream(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $searchTitle = $request->input('stream_title');
      $orderBy = $request->input('order_by');

      $liveStream = MarketPlaceLive::where('user_id', $userId);

      if ($searchTitle) {
        $liveStream->where('stream_title', 'like', '%' . $searchTitle . '%');
      }

      if ($request->filled('stream_key_id')) {
        $liveStream = MarketPlaceLive::where('user_id', $userId)
          ->where('stream_key_id', $request->stream_key_id)
          ->leftJoin('marketplace_views', 'marketplace_live.broadcast_id', '=', 'marketplace_views.broadcast_id')
          ->select(
            'marketplace_live.*',
            DB::raw('COALESCE(marketplace_views.view_count, 0) as view_count')
          )
          ->first();

        if (!$liveStream) {
          return generateResponse([
            'type' => 'error',
            'code' => 200,
            'status' => false,
            'message' => 'No matching live stream found for the provided stream_key_id',
            'toast' => true,
            'data' => []
          ]);
        }
        $liveStream->schedule_date_time = Carbon::parse($liveStream->schedule_date_time)
          ->format('Y-m-d h:i A');
        $playbackStream = "users/private/" . $userId . "/streamdeck/manifest/" . $liveStream->playback_url_key . "/stream.m3u8";
        $playbackStreamtemp = getFileTemporaryURL($playbackStream);

        $bannerStreamTemp = null;
        if ($liveStream->stream_banner) {
          $liveStream->stream_banner = getFileTemporaryURL($liveStream->stream_banner);
        }

        $productIds = explode(',', $liveStream->product_ids);
        $products = DB::table('marketplace_products')
          ->whereIn('marketplace_products.id', $productIds)
          ->select('marketplace_products.id', 'marketplace_products.product_name', 'marketplace_products.price', 'marketplace_products.thumbnail')
          ->get();

        foreach ($products as $product) {
          $product->thumbnail = url('storage/' . $product->thumbnail);

          $specifications = DB::table('product_specifications')
            ->where('product_id', $product->id)
            ->select('title', 'description')
            ->get();

          $product->description = $specifications;
        }

        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'Live stream retrieved successfully',
          'toast' => true,
          'data' => [
            'livestream' => $liveStream,
            'playbackStream' => $playbackStreamtemp,
            'product_data' => $products
          ]
        ]);
      } else {
        switch ($orderBy) {
          case 'oldest':
            $liveStream->orderBy('created_at');
            break;
          case 'newest':
            $liveStream->orderByDesc('created_at');
            break;
          case 'az':
            $liveStream->orderBy('stream_title');
            break;
          case 'za':
            $liveStream->orderByDesc('stream_title');
            break;
          default:
            $liveStream->orderByDesc('created_at');
            break;
        }

        $liveStreams = $liveStream->get();

        if (!$liveStreams) {
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No data available', 'toast' => true, 'data' => []]);
        }

        // Add product data without specifications to each live stream
        foreach ($liveStreams as $stream) {

          $stream->view_count = DB::table('marketplace_views')->where('broadcast_id', $stream->broadcast_id)->value('view_count') ?? 0;

          $stream->schedule_date_time = Carbon::parse($stream->schedule_date_time)
            ->format('Y-m-d h:i A');
          $productIds = explode(',', $stream->product_ids);
          $products = DB::table('marketplace_products')
            ->whereIn('marketplace_products.id', $productIds)
            ->select('marketplace_products.id', 'marketplace_products.product_name', 'marketplace_products.price', 'marketplace_products.thumbnail')
            ->get();

          foreach ($products as $product) {
            $product->thumbnail = url('storage/' . $product->thumbnail);
            $stream->stream_banner = getFileTemporaryURL($stream->stream_banner);
          }

          $stream->product_data = $products;
        }

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'All data retrieved successfully', 'toast' => true, 'data' => ["livestreams" => $liveStreams]]);
      }
    } catch (\Exception $e) {
      Log::info('Error while getting data : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }

  public function updateLiveStreamUrl(Request $request)
  {
    try {
      $responseUrl = $request->live_url;
      $stream_title = $request->stream_name;
      $stream_status = $request->stream_status;
      $livestream = MarketPlaceLive::where('stream_title', $stream_title)->first();

      if ($livestream) {
        $livestream->stream_url_live = $responseUrl;
        $livestream->stream_status = '1';
        $livestream->save();
      } else {
        return generateResponse([
          'type' => 'error',
          'code' => 200,
          'status' => false,
          'message' => 'Stream title not found',
          'toast' => true,
        ]);
      }

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Stream URL updated successfully',
        'toast' => false,
      ]);
      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Stream URL added successfully',
        'toast' => false,
      ]);
    } catch (\Exception $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Failed to update live stream URL',
        'toast' => true,
      ]);
    }
  }
  public function setStreamStatus(Request $request)
  {
    try {
      $identifierKey = $request->identifier_key;
      $streamStatus = $request->stream_status;
      $stream = MarketPlaceLive::where('stream_title', $identifierKey)
        ->orWhere('stream_key_id', $identifierKey)
        ->first();

      if (!$stream) {
        return response()->json([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'Stream not found',
          'toast' => true,
        ], 404);
      }
      $stream->stream_status = $streamStatus;
      $stream->save();

      return response()->json([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Stream status updated successfully',
        'toast' => true,
      ], 200);
    } catch (\Exception $e) {
      Log::error('Error during seting stream status: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 200,
        'status' => false,
        'message' => 'Error while processing',
        'toast' => true,
      ]);
    }
  }
  public function makeInfluencer(Request $request)
  {
    try {
      $validatedData = $request->validate([
        'social_media_links' => 'required',
        'signature_base64' => 'required'
      ]);

      $user = $request->attributes->get('user');
      $userId = $user->id;

      $influencer = new Influencer();
      $influencer->social_media_links = json_encode($validatedData['social_media_links']);
      $influencer->signature = $validatedData['signature_base64'];
      $influencer->user_id = $userId;
      $influencer->save();

      $userToUpdate = User::find($userId);
      if ($userToUpdate) {
        $userToUpdate->is_influencer = '1';
        $userToUpdate->save();
      }

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Influencer successfully created',
        'toast' => true,
      ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
      return generateResponse([
        'type' => 'error',
        'code' => 422,
        'status' => false,
        'message' => 'Validation error',
        'errors' => $e->errors(),
        'toast' => true,
      ]);
    } catch (\Exception $e) {
      Log::error('Error during setting influencer status: ' . $e->getMessage());

      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error while processing',
        'toast' => true,
      ]);
    }
  }
  public function checkInfluencer(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $influencer = User::find($userId);
      if ($influencer && $influencer->is_influencer === '1') {
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'The User is an Influencer',
          'toast' => true,
          'data' => [
            'is_influencer' => true,
          ],
        ]);
      } else {
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'The User is not an Influencer',
          'toast' => true,
          'data' => [
            'is_influencer' => false,
          ],
        ]);
      }
    } catch (\Exception $e) {
      Log::error('Error during getting influencer status: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Error while processing',
        'toast' => true,
      ]);
    }
  }

  public function addProductPin(Request $request)
  {
    DB::beginTransaction();
    try {
      $request->validate([
        'broadcast_id' => 'required|uuid|exists:marketplace_live,broadcast_id',
        'product_id'   => 'required|exists:marketplace_products,id',
      ]);

      $broadcastId = $request->broadcast_id;
      $productId = $request->product_id;
      $authToken = $request->header('authToken');
      $productPin = LiveProductPin::where('broadcast_id', $broadcastId)->first();

      if ($productPin) {
        $productPin->product_ids = $productId;
        $productPin->save();
        $message = 'Product ID updated successfully.';
      } else {
        $productPin = new LiveProductPin();
        $productPin->broadcast_id = $broadcastId;
        $productPin->product_ids = $productId;
        $productPin->save();
        $message = 'Product pin created successfully.';
      }

      $product = DB::table('marketplace_products')
        ->where('id', $productId)
        ->select('id', 'product_name', 'thumbnail', 'price', 'discount_percentage', 'product_short_name')
        ->first();

      if ($product && $product->thumbnail) {
        $product->thumbnail = url('storage/' . $product->thumbnail);
        $product->broadcast_id = $broadcastId;
      }
      try {
        $socketUrl = config("app.socket_url") . '/pinproduct';
        $headers = [
          'authToken' => $authToken,
        ];
        $response = Http::withHeaders($headers)->post($socketUrl, $product);

        if ($response->successful()) {
          DB::commit();
          return generateResponse([
            'type' => 'success',
            'code' => 200,
            'status' => true,
            'message' => $message,
            'toast' => true,
            'data' => [
              'broadcast_id' => $productPin->broadcast_id,
              'product_id' => $productId,
              'product' => $product,
              'response' => json_decode($response->body())
            ]
          ]);
        } else {
          Log::error('Failed to send socket notification: ' . $response->body());
          DB::rollBack();
          return generateResponse([
            'type' => 'error',
            'code' => 500,
            'status' => false,
            'message' => 'Failed to add or update product pin.',
            'toast' => true
          ]);
        }
      } catch (\Exception $e) {
        Log::error('Socket notification error: ' . $e->getMessage());
        DB::rollBack();
        return generateResponse([
          'type' => 'error',
          'code' => 500,
          'status' => false,
          'message' => 'Failed to add or update product pin.',
          'toast' => true
        ]);
      }
    } catch (\Exception $e) {
      Log::error('Error in addProductPin: ' . $e->getMessage());
      DB::rollBack();
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Failed to add or update product pin.',
        'toast' => true,
      ]);
    }
  }

  public function getProductPin(Request $request)
  {
    try {
      $broadcastId = $request->broadcast_id;
      $productPin = LiveProductPin::where('broadcast_id', $broadcastId)->first();

      if (!$productPin) {
        return generateResponse([
          'type' => 'error',
          'code' => 404,
          'status' => false,
          'message' => 'No product pin found for the given broadcast ID.',
          'toast' => true,
          'data' => []
        ]);
      }

      $productId = $productPin->product_ids;

      $product = DB::table('marketplace_products')
        ->where('id', $productId)
        ->select('id', 'product_name', 'thumbnail', 'price', 'discount_percentage', 'product_short_name')
        ->first();

      if ($product && $product->thumbnail) {
        $product->thumbnail = url('storage/' . $product->thumbnail);
        $product->broadcast_id = $broadcastId;
      }

      return generateResponse([
        'type' => 'success',
        'code' => 200,
        'status' => true,
        'message' => 'Product pin retrieved successfully.',
        'toast' => true,
        'data' => [
          'broadcast_id' => $productPin->broadcast_id,
          'product_id' => $productId,
          'product' => $product,
        ]
      ]);
    } catch (\Exception $e) {
      Log::error('Error in getProductPin: ' . $e->getMessage());
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Failed to retrieve product pin.',
        'toast' => true,
        'data' => []
      ]);
    }
  }

  public function addViews(Request $request)
  {
    $request->validate([
      'broadcast_id' => 'required|uuid|exists:marketplace_live,broadcast_id',
    ]);

    $broadcastId = $request->broadcast_id;

    DB::beginTransaction();

    try {

      $view = MarketplaceViews::where('broadcast_id', $broadcastId)->first();

      if ($view) {
        $view->view_count = ($view->view_count ?? 0) + 1;
        $view->save();
      } else {
        $view = MarketplaceViews::create([
          'broadcast_id' => $broadcastId,
          'view_count' => 1,
        ]);
      }

      $socketUrl = config("app.socket_url") . '/views/' . $broadcastId;
      Log::info('Sending data to socket server', [
        'url' => $socketUrl,
        'data' => [
          'broadcast_id' => $broadcastId,
          'view_count' => $view->view_count,
        ]
      ]);
      $response = Http::get($socketUrl);

      if ($response->successful()) {
        DB::commit();
        return generateResponse([
          'type' => 'success',
          'code' => 200,
          'status' => true,
          'message' => 'View count updated and socket notified successfully',
          'toast' => true,
          'data' => [
            'view' => $view,
            'response' => json_decode($response->body())
          ]
        ]);
      } else {
        DB::rollBack();
        return generateResponse([
          'type' => 'error',
          'code' => 500,
          'status' => false,
          'message' => 'Failed to add or update count.',
          'toast' => true
        ]);
      }
    } catch (\Exception $e) {
      Log::error('Error in addViews: ' . $e->getMessage());
      DB::rollBack();
      return generateResponse([
        'type' => 'error',
        'code' => 500,
        'status' => false,
        'message' => 'Failed to add or update count.',
        'toast' => true,
      ]);
    }
  }

  public function getProductCount(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      if (!$user) {
        return response(['type' => 'error', 'status' => false, 'code' => 401, 'toast' => true, 'message' => 'User not authenticated.',], 401);
      }
      $userId = $user->id;

      $distinctProductIds = DB::table('marketplace_live')->selectRaw('GROUP_CONCAT(product_ids) as all_product_ids')->where('user_id', $userId)->first();

      $productIdsArray = collect(explode(',', $distinctProductIds->all_product_ids))->unique()->count();

      return response(['type' => 'success', 'status' => true, 'code' => 200, 'message' => 'Total distinct streamed product count retrieved successfully.', 'toast' => true, 'data' => ['total_products' => $productIdsArray],]);
    } catch (\Exception $e) {
      return response(['type' => 'error', 'status' => false, 'code' => 500, 'message' => 'An error occurred while fetching product count.', 'error' => $e->getMessage(), 'toast' => true,], 500);
    }
  }

  public function getStreamProducts(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;

      $limit = $request->input('limit', 10);
      $page = $request->input('page', 1);
      $search = $request->input('search', '');
      $offset = ($page - 1) * $limit;

      $streamedProductIds = DB::table('marketplace_live')->where('user_id', $userId)->pluck('product_ids')->map(function ($ids) {
        return explode(',', $ids);
      })->flatten()->unique()->toArray();

      $products = MarketplaceProducts::whereIn('id', $streamedProductIds)->when($search, function ($query) use ($search) {
        $query->where('product_name', 'like', "%$search%");
      })
        ->select('id', 'product_name', 'price', 'thumbnail', DB::raw("(SELECT COUNT(*) FROM marketplace_live WHERE FIND_IN_SET(marketplace_products.id, marketplace_live.product_ids) AND user_id = $userId) as stream_count"))->whereNull('deleted_at')->distinct()->limit($limit)->offset($offset)->get();

      $formattedProducts = $products->map(function ($product) {
        return [
          'id' => $product->id,
          'product_name' => $product->product_name,
          'price' => $product->price,
          'thumbnail' => $product->thumbnail ? url('storage/' . $product->thumbnail) : null,
          'stream_count' => $product->stream_count ?? 0,
          'order' => null,
          'revenue' => null,
          'average_growth' => null,
        ];
      });

      $total = count($streamedProductIds);

      return response(['type' => 'success', 'status' => true, 'code' => 200, 'message' => 'Stream products retrieved successfully.', 'toast' => true, 'data' => $formattedProducts, 'page' => $page, 'total' => $total,], 200);
    } catch (\Exception $e) {
      Log::error('Error while fetching stream products: ' . $e->getMessage());
      return response(['type' => 'error', 'status' => false, 'code' => 500, 'message' => 'An error occurred while fetching stream products.', 'toast' => true, 'error' => $e->getMessage(),], 500);
    }
  }

  public function StopAllStreams(Request $request)
  {
    try {
      MarketplaceLive::query()->update(['stream_status' => "0"]);
      return response([
        'type' => 'success',
        'status' => true,
        'code' => 200,
        'message' => 'All streams stopped successfully.',
        'toast' => true
      ], 200);
    } catch (\Exception $e) {
      Log::error('Error while stopping streams: ' . $e->getMessage());
      return response([
        'type' => 'error',
        'status' => false,
        'code' => 500,
        'message' => 'Error while stopping streams.',
        'toast' => true,
        'error' => $e->getMessage(),
      ], 500);
    }
  }

  public function toggleFollow(Request $request)
  {
    try {
      DB::beginTransaction();

      $user = $request->attributes->get('user');
      $requestUserId = $request->user_id;
      $status = $request->status;

      if ($user->id == $requestUserId) {
        return response(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'You cannot follow yourself.', 'toast' => true]);
      }

      $targetUser = User::findOrFail($requestUserId);
      if ($targetUser->is_influencer !== '1') {
        return response(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'You can only follow influencers.', 'toast' => true]);
      }

      $connection = InfluencerConnection::firstOrNew(['user_id' => $requestUserId]);
      $followers = $connection->followed_by ? json_decode($connection->followed_by, true) : [];

      if ($status == 1) {  // Follow
        if (!in_array($user->id, $followers)) {
          $followers[] = $user->id;
        }
      } elseif ($status == 2) {  // Unfollow
        if (($key = array_search($user->id, $followers)) !== false) {
          unset($followers[$key]);
        }
      } else {
        return response()->json(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Invalid status provided.', 'toast' => true]);
      }

      $connection->followed_by = json_encode(array_values($followers));
      $connection->save();

      DB::commit();
      return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Follow status updated successfully.', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollBack();
      Log::error('toggleFollow Error: ' . $e->getMessage());
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'An error occurred while processing your request.', 'toast' => true]);
    }
  }

  public function getFollowerInfo(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;

      if ($user->is_influencer != '1') {
        return response(['type' => 'error', 'code' => 403, 'status' => false, 'message' => 'User is not an influencer.', 'toast' => true]);
      }
      $username = User::where('id', $userId)->value('username');

      $userProfile = UserProfile::where('user_id', $userId)->first();

      $profileImagePath = null;
      $profileImageTemporaryUrl = null;

      if ($userProfile && !empty($userProfile->profile_image_path)) {
        $profileImagePath = $userProfile->profile_image_path;
        $profileImageTemporaryUrl = getFileTemporaryURL($profileImagePath);
      }

      $connection = InfluencerConnection::where('user_id', $userId)->first();
      $followers = $connection ? json_decode($connection->followed_by, true) : [];

      $totalFollowers = count($followers);
      $isFollowedByOthers = $totalFollowers > 0;

      return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User follower info retrieved successfully.', 'data' => ['username' => $username, 'profile_image_path' => $profileImageTemporaryUrl, 'total_followers' => $totalFollowers, 'is_followed' => $isFollowedByOthers], 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('getFollowerInfo Error: ' . $e->getMessage());
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Failed to retrieve follower info.', 'toast' => true]);
    }
  }

  public function getInfluencerDashboard(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      if (!$user) {
        return response(['type' => 'error', 'status' => false, 'code' => 401, 'toast' => true, 'message' => 'User not authenticated.'], 401);
      }

      $userId = $user->id;
      if ($user->is_influencer != '1') {
        return response(['type' => 'error', 'code' => 403, 'status' => false, 'message' => 'User is not an influencer.', 'toast' => true]);
      }

      $months = $request->month;  // e.g., 3, 6, 9 for month-wise filtering
      $currentYear = $request->current_year;  // true/false for current year data
      $year = $request->year;  // specific year e.g., 2024

      $startDate = null;
      $endDate = now();

      if ($months) {
        $startDate = now()->subMonths($months);
      } elseif ($currentYear) {
        $startDate = now()->startOfYear();
      } elseif ($year) {
        $startDate = now()->setYear($year)->startOfYear();
        $endDate = now()->setYear($year)->endOfYear();
      } else {
        $startDate = now()->startOfYear();
      }

      $followers = InfluencerConnection::where('user_id', $userId)
        ->when($startDate, function ($query) use ($startDate, $endDate) {
          return $query->whereBetween('created_at', [$startDate, $endDate]);
        })
        ->first();
      $followersList = $followers ? json_decode($followers->followed_by, true) : [];
      $totalFollowers = count($followersList);

      $distinctProductIds = DB::table('marketplace_live')
        ->selectRaw('GROUP_CONCAT(product_ids) as all_product_ids')
        ->where('user_id', $userId)
        ->when($startDate, function ($query) use ($startDate, $endDate) {
          return $query->whereBetween('created_at', [$startDate, $endDate]);
        })
        ->first();

      $productIdsArray = $distinctProductIds && $distinctProductIds->all_product_ids
        ? collect(explode(',', $distinctProductIds->all_product_ids))->unique()->count()
        : 0;

      $totalViewers = DB::table('marketplace_views')
        ->join('marketplace_live', 'marketplace_views.broadcast_id', '=', 'marketplace_live.broadcast_id')
        ->where('marketplace_live.user_id', $userId)
        ->when($startDate, function ($query) use ($startDate, $endDate) {
          return $query->whereBetween('marketplace_views.created_at', [$startDate, $endDate]);
        })
        ->sum('marketplace_views.view_count');

      // Sales Income
      $influencerProductIds = DB::table('marketplace_products')->where('user_id', $userId)->pluck('id')->toArray();
      $totalSalesIncome = DB::table('marketplace_product_purchase_details')
        ->where('payment_status', '1')
        ->whereIn('product_id', $influencerProductIds)
        ->when($startDate, function ($query) use ($startDate, $endDate) {
          return $query->whereBetween('created_date_time', [$startDate, $endDate]);
        })
        ->sum(DB::raw('quantity * price'));

      $highestMonthlyIncome = DB::table('marketplace_product_purchase_details')
        ->selectRaw('SUM(quantity * price) as monthly_income, DATE_FORMAT(created_date_time, "%Y-%m") as month')
        ->where('payment_status', '1')
        ->whereIn('product_id', $influencerProductIds)
        ->when($startDate, function ($query) use ($startDate, $endDate) {
          return $query->whereBetween('created_date_time', [$startDate, $endDate]);
        })
        ->groupBy('month')
        ->orderByDesc('monthly_income')
        ->value('monthly_income');

      // Monthly Data for Streams, Viewers, and Revenue
      $totalStreamCount = DB::table('marketplace_live')
        ->where('user_id', $userId)
        ->when($startDate, function ($query) use ($startDate, $endDate) {
          return $query->whereBetween('created_at', [$startDate, $endDate]);
        })
        ->selectRaw('COUNT(*) as stream_count, MONTH(created_at) as month, YEAR(created_at) as year')
        ->groupBy('year', 'month')
        ->get();

      $totalViewerCount = DB::table('marketplace_views')
        ->join('marketplace_live', 'marketplace_views.broadcast_id', '=', 'marketplace_live.broadcast_id')
        ->where('marketplace_live.user_id', $userId)
        ->when($startDate, function ($query) use ($startDate, $endDate) {
          return $query->whereBetween('marketplace_views.created_at', [$startDate, $endDate]);
        })
        ->selectRaw('SUM(marketplace_views.view_count) as view_count, MONTH(marketplace_views.created_at) as month, YEAR(marketplace_views.created_at) as year')
        ->groupBy('year', 'month')
        ->get();

      $totalRevenue = DB::table('marketplace_product_purchase_details')
        ->whereIn('product_id', $influencerProductIds)
        ->where('payment_status', '1')
        ->when($startDate, function ($query) use ($startDate, $endDate) {
          return $query->whereBetween('created_date_time', [$startDate, $endDate]);
        })
        ->selectRaw('SUM(quantity * price) as revenue, MONTH(created_date_time) as month, YEAR(created_date_time) as year')
        ->groupBy('year', 'month')
        ->get();

      // Aggregate monthly data
      $months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];
      $year = $startDate ? $startDate->year : date('Y');
      $completeStreamCount = [];
      $completeViewerCount = [];
      $completeRevenue = [];

      foreach ($months as $monthNumber => $monthName) {
        $completeStreamCount[] = [
          'month' => $monthName,
          'year' => $year,
          'stream_count' => (int) optional($totalStreamCount->firstWhere('month', $monthNumber))->stream_count,
        ];
        $completeViewerCount[] = [
          'month' => $monthName,
          'year' => $year,
          'view_count' => (int) optional($totalViewerCount->firstWhere('month', $monthNumber))->view_count,
        ];
        $completeRevenue[] = [
          'month' => $monthName,
          'year' => $year,
          'revenue' => (float) optional($totalRevenue->firstWhere('month', $monthNumber))->revenue,
        ];
      }

      return response([
        'type' => 'success',
        'status' => true,
        'code' => 200,
        'message' => 'Influencer dashboard data retrieved successfully.',
        'toast' => true,
        'data' => [
          'total_followers' => $totalFollowers,
          'total_products' => $productIdsArray,
          'total_sell_income' => $totalSalesIncome,
          'total_income' => $totalSalesIncome,
          'total_viewers' => $totalViewers,
          'highest_monthly_income' => $highestMonthlyIncome,
          'total_stream_count' => $completeStreamCount,
          'total_visitors' => $completeViewerCount,
          'total_revenue' => $completeRevenue
        ],
      ]);
    } catch (\Exception $e) {
      Log::error('getInfluencerDashboard Error: ' . $e->getMessage());
      return response(['type' => 'error', 'status' => false, 'code' => 500, 'message' => 'An error occurred while fetching dashboard data.', 'error' => $e->getMessage(), 'toast' => true], 500);
    }
  }
}
