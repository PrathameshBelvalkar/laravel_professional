<?php

namespace App\Http\Controllers\API\V1\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Dashboard\UploadImageRequest;
use App\Http\Requests\Dashboard\StoreImageSelectionRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\user;
use App\Models\Dashboard\DashboardImages;
use App\Models\Dashboard\DashboardImageSelection;

class DashboardImagesController extends Controller
{
  public function uploadImage(UploadImageRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');

      $userFolder = "users/private/{$user->id}/dashboard_images";
      Storage::makeDirectory($userFolder);
      $imagePath = null;
      if ($request->hasFile('images')) {
        $image = $request->file('images');
        $imageName = time() . '_' . uniqid() . '_' . $image->getClientOriginalName();
        $imagePath = $image->storeAs($userFolder, $imageName);
      }
      DB::commit();
      $temporaryUrl = Storage::temporaryUrl($imagePath, now()->addMinutes(60));

      return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Image uploaded successfully!', 'data' => ['imagePath' => $imagePath, 'imageUrl' => $temporaryUrl,], 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error uploading image: ' . $e->getMessage());
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error uploading image', 'toast' => true]);
    }
  }

  public function deleteImage(Request $request)
  {
    DB::beginTransaction();
    try {

      $request->validate(['image_name' => 'required|string',]);
      $user = $request->attributes->get('user');
      $imageName = $request->input('image_name');

      $userFolder = "users/private/{$user->id}/dashboard_images";
      $imagePath = "{$userFolder}/{$imageName}";

      if (Storage::exists($imagePath)) {
        Storage::delete($imagePath);
        DB::commit();

        return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Image deleted successfully!', 'toast' => true]);
      } else {
        return response(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Image not found!', 'toast' => true]);
      }
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Error deleting image: ' . $e->getMessage());
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error deleting image', 'toast' => true]);
    }
  }
  public function getImages(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;
      $dashboardImages = DB::table('dashboard_images')->get(['id', 'images']);
      $defaultImages = $dashboardImages->map(function ($image) {
        return [
          'id' => $image->id,
          'imagePath' => url('dashboard/' . basename($image->images)),
        ];
      })->toArray();

      $user = $request->attributes->get('user');
      $userFolder = "users/private/{$user->id}/dashboard_images";

      $storageImages = Storage::files($userFolder);

      $customImages = array_map(function ($image) {
        return [
          'imagePath' => $image,
          //'temporaryUrl' => Storage::temporaryUrl($image, now()->addMinutes(60)),
          'temporaryUrl' => getFileTemporaryURL($image),
        ];
      }, $storageImages);

      $responseData = [
        'user_id' => $userId,
        'defaultImages' => $defaultImages,
        'customImages' => $customImages,
      ];
      return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Images retrieved successfully!',  'data' => $responseData, 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('Error retrieving images: ' . $e->getMessage());
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error retrieving images', 'toast' => true]);
    }
  }

  public function storeImageSelection(StoreImageSelectionRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');

      if (!$request->filled('custom_image_path') && !$request->filled('default_image_id')) {
        return response(['type' => 'error', 'code' => 422, 'status' => false, 'message' => 'Either default_image_id or custom_image_path is required!', 'toast' => true], 422);
      }

      $data = [
        'user_id' => $user->id,
        'color' => $request->color,
        'is_logo' => $request->is_logo,
        'alignment' => $request->alignment,
      ];

      $imageSelection = DashboardImageSelection::where('user_id', $user->id)->first();

      if ($request->filled('default_image_id')) {
        $data['dashboard_image_id'] = $request->input('default_image_id');
        $data['custom_image_path'] = null;
      } else {
        if ($request->filled('custom_image_path')) {
          $customImagePath = "users/private/{$user->id}/dashboard_images/{$request->input('custom_image_path')}";

          if (!Storage::exists($customImagePath)) {
            return response(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'Custom image not found!', 'toast' => true], 404);
          }

          $data['custom_image_path'] = $customImagePath;
          $data['dashboard_image_id'] = null;
        }
      }

      if ($imageSelection) {
        $imageSelection->update($data);
      } else {
        DashboardImageSelection::create($data);
      }

      DB::commit();
      return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Image selection saved successfully!', 'toast' => true], 200);
    } catch (\Exception $e) {
      DB::rollback();
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error storing image selection', 'toast' => true], 500);
    }
  }

  public function getImageSelection(Request $request)
  {
    try {
      $user = $request->attributes->get('user');

      $selection = DashboardImageSelection::where('user_id', $user->id)->first();

      if (!$selection) {
        return response(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'No image selection found!', 'toast' => true], 404);
      }

      $imageData = [];
      $imageData['id'] = $selection->id;
      $imageData['user_id'] = $user->id;
      if ($selection->dashboard_image_id) {
        $defaultImage = DB::table('dashboard_images')->where('id', $selection->dashboard_image_id)->first();

        $imageData['imagePath'] = url('dashboard/' . basename($defaultImage->images));
      } elseif ($selection->custom_image_path) {
        $imageData['temporaryUrl'] = Storage::temporaryUrl(
          $selection->custom_image_path,
          now()->addMinutes(60)
        );
      }
      $imageData['color'] = $selection->color;
      $imageData['is_logo'] = $selection->is_logo;
      $imageData['alignment'] = $selection->alignment;

      return response(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Image selection retrieved successfully!', 'data' => $imageData, 'toast' => true], 200);
    } catch (\Exception $e) {
      Log::error('Error retrieving image selection: ' . $e->getMessage());
      return response(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error retrieving image selection', 'toast' => true], 500);
    }
  }
}
