<?php

use Illuminate\Support\Facades\Storage;

if (!function_exists('getFileTemporaryURL')) {
  function getFileTemporaryURL($path, $minutes = null)
{
    try {
        $fileName = basename($path);
        $encodedFileName = urlencode($fileName);
        
        $directoryPath = dirname($path);
        $encodedPath = $directoryPath . '/' . $encodedFileName;

        $expireTime = now()->addMinutes(config('app.file_temporary_url_expiration_time'));
        if ($minutes && is_numeric($minutes) && $minutes > 1) {
            $expireTime = now()->addMinutes($minutes);
        }
        $fileURL = Storage::disk('local')->temporaryUrl(
            $encodedPath,
            $expireTime
        );
        return $fileURL;
    } catch (Exception $e) {
        return null;
    }
}
}
if (!function_exists('displayString')) {
 function displayString($length, $string)
{
    return strlen($string) > $length ? substr($string, 0, $length) . '...' : $string;
}
}
if (!function_exists('productIsFileExists')) {
function productIsFileExists($imagePath = '', $placeholderImagePath = '')
{
    $defaultImage = 'assets/cloud/images/marketplace/product_default_images.png';

    $publicPath = public_path($imagePath);
    if (!empty($imagePath) && file_exists($publicPath)) {
        return asset($imagePath);
    }

    $publicPlaceholderPath = public_path($placeholderImagePath);
    if (!empty($placeholderImagePath) && file_exists($publicPlaceholderPath)) {
        return asset($placeholderImagePath);
    }

    return asset($defaultImage);
}
}

if (!function_exists('displayCharacter')) {
  function displayCharacter($text, $limit)
  {
      $sentences = preg_split('/(?<=[.?!])\s+/', $text);
      $string = implode(" ", array_slice($sentences, 0, $limit));
      return $string;
  }
}

if (!function_exists('is_assoc')) {
  function is_assoc(array $array) {
      return (bool)count(array_filter(array_keys($array), 'is_string'));
  }
}