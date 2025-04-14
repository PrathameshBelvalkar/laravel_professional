<?php

namespace App\Http\Controllers\API\V1\ThreeD;

use Illuminate\Http\Request;
use App\Models\ThreeDproduct;
use App\Models\ThreeDCategory;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ThreeD\AddThreedproductRequest;
use App\Http\Requests\ThreeD\UpdateThreedproductRequest;


class ThreeDproductController extends Controller
{
  public function getThreedproduct(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $product_id = $request->product_id;

      $threedproduct = ThreeDproduct::where('id', $product_id)->where('user_id', $user->id)->first();

      if (!$threedproduct) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Product not found.', 'toast' => true]);
      }

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Product retrieved successfully.', 'toast' => true], ['product' => $threedproduct]);
    } catch (\Exception $e) {
      Log::error('Error while retrieving 3D product. ' . $e->getMessage());

      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }



  public function addThreedproduct(AddThreedproductRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      $category_id = $request->category_id;

      $category = ThreeDCategory::where('id', $category_id)->first();

      $validator = Validator::make($request->all(), [
        'model_file' => ['required', 'file', function ($attribute, $value, $fail) {
          $allowedExtensions = ['3dm', '3ds', '3mf', 'amf', 'bim', 'brep', 'dae', 'fbx', 'fcstd', 'gltf', 'ifc', 'iges', 'step', 'stl', 'obj', 'off', 'ply', 'wrl', 'glb'];
          $extension = strtolower($value->getClientOriginalExtension());

          if (!in_array($extension, $allowedExtensions)) {
            $fail("The {$attribute} must be a file of type: " . implode(', ', $allowedExtensions) . '.');
          }
        }],
      ]);

      if ($validator->fails()) {
        $allowedExtensions = ['3dm', '3ds', '3mf', 'amf', 'bim', 'brep', 'dae', 'fbx', 'fcstd', 'gltf', 'ifc', 'iges', 'step', 'stl', 'obj', 'off', 'ply', 'wrl', 'glb'];
        $message = "The file must be one of the following types: " . implode(', ', $allowedExtensions);
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => $message, 'toast' => true]);
      }

      if ($category) {

        $threedproduct = new Threedproduct();

        if ($request->price <= 0) {
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Price must be greater than zero.', 'toast' => true]);
        }

        $threedproduct->category_id = $category->id;
        $threedproduct->user_id = $user->id;
        $threedproduct->model_name = $request->model_name;
        $threedproduct->price = $request->price;
        $threedproduct->description = $request->description;
        $threedproduct->features = $request->features;

        if ($request->hasFile('model_file')) {
          $file = $request->file('model_file');
          $fileFullName = $file->getClientOriginalName();
          $filePath = "users/private/{$user->id}/FileManager/3D/{$fileFullName}";

          Storage::put($filePath, file_get_contents($file));
          $threedproduct->model_file = $filePath;
        }
        if ($request->hasFile('model_thumbnail')) {
          $file = $request->file('model_thumbnail');
          $fileName = $file->getClientOriginalName();
          $filePath = "users/private/{$user->id}/FileManager/3D/thumbnail/{$fileName}";

          Storage::put($filePath, file_get_contents($file));
          $threedproduct->model_thumbnail = $filePath;
        }

        $threedproduct->save();

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Product added successfully.', 'toast' => true], ['product' => $threedproduct]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Category not found.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::error('Error while adding 3D product. ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function updateThreedproduct(UpdateThreedproductRequest $request)
  {
    try {
      $user = $request->attributes->get('user');
      $product_id = $request->product_id;

      $threedproduct = ThreeDproduct::where('id', $product_id)->where('user_id', $user->id)->first();

      if (!$threedproduct) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Product not found.', 'toast' => true]);
      }
      if ($request->price <= 0) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Price must be greater than zero.', 'toast' => true]);
      }

      if (isset($request->model_name)) {
        $threedproduct->model_name = $request->model_name;
      }

      if (isset($request->price)) {
        $threedproduct->price = $request->price;
      }

      if (isset($request->description)) {
        $threedproduct->description = $request->description;
      }

      if (isset($request->features)) {
        $threedproduct->features = $request->features;
      }

      if ($request->hasFile('model_thumbnail')) {
        $file = $request->file('model_thumbnail');
        $fileName = $file->getClientOriginalName();
        $filePath = "users/private/{$user->id}/FileManager/3D/thumbnail/{$fileName}";


        if ($threedproduct->model_thumbnail) {
          Storage::delete($threedproduct->model_thumbnail);
        }

        Storage::put($filePath, file_get_contents($file));
        $threedproduct->model_thumbnail = $filePath;
      }

      $threedproduct->save();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Product updated successfully.', 'toast' => true], ['product' => $threedproduct]);
    } catch (\Exception $e) {
      Log::error('Error while updating 3D product. ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function deleteThreedproduct(Request $request,)
  {
    try {
      $user = $request->attributes->get('user');
      $product_id = $request->product_id;

      $threedproduct = Threedproduct::where('id', $product_id)->where('user_id', $user->id)->first();

      if (!$threedproduct) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Product not found.', 'toast' => true]);
      }

      if ($threedproduct->model_file) {
        Storage::delete($threedproduct->model_file);
      }
      if ($threedproduct->model_thumbnail) {
        Storage::delete($threedproduct->model_thumbnail);
      }

      $threedproduct->delete();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Product deleted successfully.', 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('Error while deleting 3D product. ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
}
