<?php

namespace App\Http\Controllers\API\V1\SiteSetting;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\SiteSetting\HandleSiteSettingsRequest;
use App\Models\Public\SiteSetting;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Storage;


class SiteSettingController extends Controller
{

  public function addSitesetting(HandleSiteSettingsRequest $request)
  {
    $data = [];
    $user = $request->attributes->get('user');

    if ($user->role_id == 2) {
      // Handling file uploads
      $fileFields = [
        'sidebar_logo' => 'Sidebar Logo',
        'favicon_logo' => 'Favicon Logo',
        'public_page_logo' => 'Public Logo',
        'client_home_page_logo' => 'Client',
        'auth_logo' => 'Auth Logo',
        'blog_header_image' => 'Blog Header Image',
        'meta_image' => 'Meta Image',
        'logo_dark' => 'Dark logo'
      ];

      foreach ($fileFields as $key => $name) {
        if ($request->hasFile($key)) {
          $data[] = [
            "field_name" => $name,
            "field_key" => $key,
            "field_value" => $request->file($key)
          ];
        }
      }

      // Handling non-file fields
      $textFields = [
        'meta_title' => 'Meta Title',
        'meta_description' => 'Meta Description',
        'meta_url' => 'Meta URL'
      ];

      foreach ($textFields as $key => $name) {

        if ($request->filled($key)) {
          $data[] = [
            "field_name" => $name,
            "field_key" => $key,
            "field_value" => $request->input($key)
          ];
        }
      }

      if (empty($data)) {
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => false, 'message' => 'You did not select anything', 'toast' => true]);
      }

      $response = $this->handleSiteSettings($data, $user);
      return $response;
    } else {
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => false, 'message' => 'You do not have permission to perform this action', 'toast' => true]);
    }
  }

  public function handleSiteSettings(array $data, $user)
  {
    DB::beginTransaction();
    try {
      $updatedSettings = [];

      foreach ($data as $item) {
        $existingSetting = SiteSetting::where('field_key', $item['field_key'])->first();
        $filePath = "";
        $userFolderIMG = "users/private/SiteSetting";
        Storage::makeDirectory($userFolderIMG);

        if (in_array($item['field_key'], ['meta_title', 'meta_description', 'meta_url'])) {
          // Handle text fields
          $fieldValue = $item['field_value'];
          if ($existingSetting) {
            $existingSetting->update([
              'field_value' => $fieldValue,
              'updated_at' => date("Y-m-d H:i:s")
            ]);
            $updatedSettings[] = $existingSetting;
          } else {
            $siteSetting = new SiteSetting();
            $siteSetting->field_name = $item['field_name'];
            $siteSetting->field_key = $item['field_key'];
            $siteSetting->field_value = $fieldValue;
            $siteSetting->save();
            $updatedSettings[] = $siteSetting;
          }
        } else {
          // Handle file fields
          if (isset($item["field_value"])) {
            $uploadFile = $item["field_value"];
            $fileName = $item["field_key"] . '.' . $uploadFile->getClientOriginalExtension();
            $filePath = "users/private/SiteSetting/{$fileName}";

            if (!$uploadFile->isValid()) {
              throw new \Exception('Invalid file data');
            }

            if ($existingSetting && !empty($existingSetting->field_value)) {
              $existingFilePath = storage_path("app/" . $existingSetting->field_value);
              if (file_exists($existingFilePath)) {
                unlink($existingFilePath);
              }
            }

            $uploadSuccess = $uploadFile->move(storage_path('app/users/private/SiteSetting'), $fileName);

            if (!$uploadSuccess) {
              throw new \Exception('Failed to upload file');
            }
          }

          if ($existingSetting) {
            $existingSetting->update([
              'field_value' => $filePath,
              'updated_at' => date("Y-m-d H:i:s")
            ]);
            $updatedSettings[] = $existingSetting;
          } else {
            $siteSetting = new SiteSetting();
            $siteSetting->field_name = $item['field_name'];
            $siteSetting->field_key = $item['field_key'];
            $siteSetting->field_value = $filePath;
            $siteSetting->save();
            $updatedSettings[] = $siteSetting;
          }
        }
      }
      DB::commit();

      return $updatedSettings;
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Upload error in file ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while updating site settings', 'toast' => true]);
    }
  }


  public function getSidebarMenu()
  {
    try {
      $menuData = MenuItem::pluck('menudata')->first();
      $menuData = json_decode($menuData, true);
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Menudata fetched', 'toast' => true], ['menu' => $menuData]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::error('Getting Menu error' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Error while Fetching menus', 'toast' => true]);
    }
  }
}
