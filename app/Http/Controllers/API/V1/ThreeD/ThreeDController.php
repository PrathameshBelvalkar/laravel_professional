<?php

namespace App\Http\Controllers\API\V1\ThreeD;

use App\Http\Controllers\Controller;
use App\Http\Requests\ThreeD\UploadThreeDRequest;
use App\Models\ThreeD;
use App\Models\ThreeD\ThreeDExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ThreeDController extends Controller
{
  public function uploadThreeDFile(UploadThreeDRequest $request)
  {
    DB::beginTransaction();

    try {
      $user = $request->attributes->get('user');
      $threeD = new ThreeD();
      $threeD->user_id = $user->id;

      $validator = Validator::make($request->all(), [
        'file_path' => [
          'required',
          'file',
          function ($attribute, $value, $fail) {
            $allowedExtensions = ['3dm', '3ds', '3mf', 'amf', 'bim', 'brep', 'dae', 'fbx', 'fcstd', 'gltf', 'ifc', 'iges', 'step', 'stl', 'obj', 'off', 'ply', 'wrl', 'glb'];
            $extension = strtolower(pathinfo($value->getClientOriginalName(), PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions)) {
              $fail("The {$attribute} must be a file of type: " . implode(', ', $allowedExtensions) . '.');
            }
          }
        ],
      ]);

      if ($validator->fails()) {
        $allowedExtensions = ['3dm', '3ds', '3mf', 'amf', 'bim', 'brep', 'dae', 'fbx', 'fcstd', 'gltf', 'ifc', 'iges', 'step', 'stl', 'obj', 'off', 'ply', 'wrl', 'glb'];
        $message = "The file must be one of the following types: " . implode(', ', $allowedExtensions);
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => $message, 'toast' => true]);
      }

      if ($request->hasFile('file_path') && $request->file('file_path')->isValid()) {
        $file = $request->file('file_path');
        $fileFullName = $file->getClientOriginalName();
        $fileName = pathinfo($fileFullName, PATHINFO_FILENAME);
        $filePath = "users/private/{$user->id}/3D/{$fileFullName}";

        Storage::put($filePath, file_get_contents($file));
        $fileSize = $file->getSize();

        $threeD->file_path = $filePath;
        $threeD->file_format = $file->getClientOriginalExtension();
        $threeD->file_name = $fileName;
        $threeD->file_size = $fileSize;
      }

      if ($request->hasFile('thumbnail_path') && $request->file('thumbnail_path')->isValid()) {
        $thumbnail = $request->file('thumbnail_path');
        $thumbnailName = $thumbnail->getClientOriginalName();
        $thumbnailPath = "users/private/{$user->id}/3D/thumbnail/{$thumbnailName}";

        Storage::put($thumbnailPath, file_get_contents($thumbnail));
        $threeD->thumbnail_path = $thumbnailPath;
      }


      $uniqueString = generateUniqueString('ThreeD', 'unique_link', 32);
      $threeD->unique_link = $uniqueString;


      addNotification($user->id, $user->id, "Your 3D file has been successfully uploaded.", null, null, '15', '/collection');


      $threeD->save();

      DB::commit();

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => '3D file uploaded successfully.', 'toast' => true, 'data' => $threeD]);
    } catch (\Exception $e) {
      Log::info('Error while uploading 3D file: ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while uploading 3D file. Please try again later.', 'toast' => true]);
    }
  }

  // public function uploadThreeDFile(UploadThreeDRequest $request)
  // {
  //     $request->validate([
  //         'fileName' => 'required|string',
  //         'index' => 'required|integer',
  //         'totalChunks' => 'required|integer',
  //         'chunk' => 'required|file',
  //         'thumbnail_path' => 'nullable|file' 
  //     ]);

  //     $fileName = $request->input('fileName');
  //     $chunkIndex = $request->input('index');
  //     $totalChunks = $request->input('totalChunks');
  //     $chunk = $request->file('chunk');

  //     $user = $request->attributes->get('user');

  //     $thumbnailFilePath = null;
  //     if ($request->hasFile('thumbnail_path')) {
  //         $thumbnailFile = $request->file('thumbnail_path');
  //         $thumbnailFileName = $thumbnailFile->getClientOriginalName();
  //         $thumbnailFilePath = "users/private/{$user->id}/FileManager/3D/thumbnail/{$thumbnailFileName}";

  //         Storage::put($thumbnailFilePath, file_get_contents($thumbnailFile));
  //     }

  //     $uploadDir = storage_path("app/users/private/{$user->id}/FileManager/3D/{$fileName}");
  //     if (!is_dir($uploadDir)) {
  //         mkdir($uploadDir, 0777, true);
  //     }

  //     $chunkPath = $uploadDir . '/' . $chunkIndex;
  //     $chunk->move($uploadDir, $chunkIndex);

  //     $uploadedChunks = array_diff(scandir($uploadDir), ['.', '..']);
  //     if (count($uploadedChunks) == $totalChunks) {
  //         $finalFilePath = storage_path("app/users/private/{$user->id}/FileManager/3D/{$fileName}");
  //         $fp = fopen($finalFilePath, 'wb');

  //         for ($i = 0; $i < $totalChunks; $i++) {
  //             $chunkFile = $uploadDir . '/' . $i;
  //             $chunkData = file_get_contents($chunkFile);
  //             fwrite($fp, $chunkData);
  //             unlink($chunkFile); 
  //         }

  //         fclose($fp);
  //         rmdir($uploadDir);

  //         $fileFormat = pathinfo($fileName, PATHINFO_EXTENSION);

  //         // Save file information in the database
  //         $threeD = new ThreeD();
  //         $threeD->user_id = $user->id;
  //         $threeD->file_path = "users/private/{$user->id}/FileManager/3D/{$fileName}";
  //         $threeD->thumbnail_path = $thumbnailFilePath;
  //         $threeD->file_format = $fileFormat;
  //         $threeD->file_name = pathinfo($fileName, PATHINFO_FILENAME);
  //         $threeD->save();

  //         return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => '3D file uploaded successfully.', 'toast' => true], ['threeD' => $threeD,'filePath' => $finalFilePath]);
  //     }
  // }



  public function deleteThreeDFile(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $three_d_id = $request->three_d_id;

      $three_d = ThreeD::where('user_id', $user->id)->where('id', $three_d_id)->first();

      if (!$three_d) {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => '3D not found.', 'toast' => true]);
      }

      if ($three_d->file_path) {
        Storage::delete($three_d->file_path);
      }
      if ($three_d->thumbnail_path) {
        Storage::delete($three_d->thumbnail_path);
      }

      $three_d->delete();

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => '3D deleted successfully.', 'toast' => true]);
    } catch (\Exception $e) {
      Log::error('Error while deleting 3D: ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error deleting 3D.', 'toast' => true]);
    }
  }
  public function getThreeDFile(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $id = $request->input('three_d_id');
      $THREE_D_URL = env('THREE_D_URL');
      if ($id) {
        $three_d = ThreeD::where('user_id', $user->id)->where('id', $id)->first();


        if (!$three_d) {
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Please specify the ID of the 3D file you want to access.', 'toast' => true]);
        } else {
          // $three_d->thumbnail_path = getFileTemporaryURL($three_d->thumbnail_path);
          $three_d->thumbnail_path = $three_d->thumbnail_path ? getFileTemporaryURL($three_d->thumbnail_path) : null;
          $three_d->file_path = getFileTemporaryURL($three_d->file_path);
          $three_d->shareable_link = "{$THREE_D_URL}/three_d/{$three_d->unique_link}";
        }
      } else {
        $three_d = ThreeD::where('user_id', $user->id)->orderByDesc('id')->get();
      }

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => '3D retrieved successfully.', 'toast' => true, 'data' => $three_d]);
    } catch (\Exception $e) {
      Log::info('Error while retrieving 3D: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error retrieving channel.', 'toast' => true]);
    }
  }
  // public function getThreeDFile(Request $request)
  // {
  //   try {
  //     $user = $request->attributes->get('user');
  //     $id = $request->input('three_d_id');

  //     if ($id) {
  //       $three_d = ThreeD::where('user_id', $user->id)->where('id', $id)->first();

  //       if (!$three_d) {
  //         return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => '3D file not found for the specified ID.', 'toast' => true,]);
  //       }

  //       $three_d->thumbnail_path = $three_d->thumbnail_path ? getFileTemporaryURL($three_d->thumbnail_path) : null;

  //       $three_d->file_path = getFileTemporaryURL($three_d->file_path);

  //       return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => '3D file retrieved successfully.', 'toast' => true,], ['three_d' => $three_d]);
  //     } else {
  //       $three_d_files = ThreeD::where('user_id', $user->id)->orderByDesc('id')->get();

  //       $three_d_files->each(function ($three_d) {
  //         $three_d->thumbnail_path = $three_d->thumbnail_path ? getFileTemporaryURL($three_d->thumbnail_path) : null;
  //         $three_d->file_path = getFileTemporaryURL($three_d->file_path);
  //       });

  //       return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => '3D files retrieved successfully.', 'toast' => true,], ['three_d' => $three_d_files]);
  //     }
  //   } catch (\Exception $e) {
  //     return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'An error occurred while retrieving the 3D file(s).', 'toast' => true,]);
  //   }
  // }

  public function getUserThreeD(Request $request)
  {
    try {
      $user = $request->attributes->get('user');

      $three_d = ThreeD::where('user_id', $user->id)
        ->orderBy('created_at', 'desc')
        ->get(['id', 'file_name', 'file_size', 'thumbnail_path', 'created_at']);

      $response = $three_d->map(function ($item) {
        if ($item->thumbnail_path) {
          $thumbnail_path = getFileTemporaryURL($item->thumbnail_path);
        } else {
          $thumbnail_path = url("/assets/images/three-d/thmbnail-default-image.png");
        }
        return [
          'id' => $item->id,
          'file_name' => $item->file_name,
          'thumbnail_path' => $thumbnail_path,
          'file_size' => convertBytes($item->file_size),
          'date' => $item->created_at->format('Y-m-d H:i:s')
        ];
      });

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => '3D retrieved successfully.', 'toast' => true, 'data' => $response]);
    } catch (\Exception $e) {
      Log::info('Error while retrieving 3D: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error retrieving 3D data.', 'toast' => true]);
    }
  }
  public function getThreeDFileByUniqueLink(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $uniqueLink = $request->unique_link;
      $THREE_D_URL = env('THREE_D_URL');

      if ($uniqueLink) {
        $three_d = ThreeD::where('user_id', $user->id)->where('unique_link', $uniqueLink)->first();

        if (!$three_d) {
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Invalid unique link or 3D file not found.', 'toast' => true]);
        }

        $three_d->thumbnail_path = getFileTemporaryURL($three_d->thumbnail_path);
        $three_d->file_path = getFileTemporaryURL($three_d->file_path);
        $three_d->shareable_link = "{$THREE_D_URL}/three_d/{$three_d->unique_link}";

        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => '3D file retrieved successfully.', 'toast' => true, 'data' => $three_d]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Please specify a valid unique link.', 'toast' => true]);
      }
    } catch (\Exception $e) {
      Log::info('Error while retrieving 3D: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error retrieving 3D file. Please try again later.', 'toast' => true]);
    }
  }

  public function addThreeDExport(Request $request)
  {

    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $export = new ThreeDExport();
      $export->user_id = $user->id;

      $export->format = $request->format;
      $export->scope = $request->scope;
      $export->rotation = $request->rotation;
      $export->save();

      DB::commit();
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Export data added successfully.', 'toast' => true, 'data' => $export]);
    } catch (\Exception $e) {
      Log::info('export register API error : ' . $e->getMessage());
      DB::rollBack();
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }


  public function getThreeDExport(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $userId = $user->id;

      $exports = ThreeDExport::where('user_id', $userId)->orderByDesc('id')->get();

      if ($exports->isEmpty()) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'No export data found.', 'toast' => true]);
      }

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Export data fetched successfully.', 'data' => $exports, 'toast' => true]);
    } catch (\Exception $e) {
      Log::info('File add error: ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error fetching the export data.', 'toast' => true]);
    }
  }
}
