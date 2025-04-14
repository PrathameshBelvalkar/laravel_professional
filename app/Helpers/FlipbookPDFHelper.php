<?php

namespace App\Helpers;

use App\Models\Flipbook\FlipbookPublication;
use App\Models\Subscription\UserServiceSubscription;
use Illuminate\Support\Facades\Log;

class FlipbookPDFHelper
{
  public static function generateThumbnail($pdfPath, $thumbnailPath, $page = 1)
  {
    $ghostscriptPath = '"C:\Program Files\gs\gs10.03.1\bin\gswin64c.exe"'; // Path to Ghostscript executable

    // Log the paths for debugging
    Log::info('Generating thumbnail:', ['pdfPath' => $pdfPath, 'thumbnailPath' => $thumbnailPath]);

    // Ensure paths are enclosed in quotes to handle spaces
    $pdfPath = escapeshellarg($pdfPath);
    $thumbnailPath = escapeshellarg($thumbnailPath);

    $command = "$ghostscriptPath -dNOPAUSE -dBATCH -sDEVICE=pngalpha -dFirstPage=$page -dLastPage=$page -sOutputFile=$thumbnailPath $pdfPath";

    exec($command, $output, $returnVar);

    // Log the command output for debugging
    Log::info('Ghostscript output:', ['output' => $output, 'returnVar' => $returnVar]);

    if ($returnVar !== 0) {
      throw new \Exception("Failed to generate thumbnail: " . implode("\n", $output));
    }

    return $thumbnailPath;
  }
  public static function getUserSellCount($user_id)
  {
    $return = array("count" => 0, "status" => false, "remaining_count" => 0);
    $sellCount = FlipbookPublication::where("user_id", $user_id)->where("status", "2")->count();
    $free_flipbook_sell_count = config("app.free_flipbook_sell_count");

    $servicePlan = UserServiceSubscription::where("user_id", $user_id)->where('status', "1")->where("service_id", "8")->first();
    if ($servicePlan) {
      $service_plan_data = $servicePlan->service_plan_data ? json_decode($servicePlan->service_plan_data, true) : [];
      if ($service_plan_data && isset($service_plan_data['features'])) {
        $features = $service_plan_data['features'] ? json_decode($service_plan_data['features'], true) : [];
        if (isset($features['flipbooks']['value'])) {
          $free_flipbook_sell_count += $features['flipbooks']['value'];
        }
      }

    }
    if ($sellCount >= $free_flipbook_sell_count) {
      $return = ["count" => $sellCount, "status" => false, "remaining_count" => 0];
    } else {
      $return = ["count" => $sellCount, "status" => true, "remaining_count" => $free_flipbook_sell_count - $sellCount];
    }


    return $return;
  }
}
