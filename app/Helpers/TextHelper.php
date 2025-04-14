<?php

use App\Http\Resources\GeneralResponse;
use App\Models\Country;
use App\Models\TimeZone;

if (!function_exists('hashPass')) {
  function hashPass($string)
  {
    return hash('sha512', $string . config("app.enc_key"));
  }
}
if (!function_exists('generateUniqueString')) {
  function generateUniqueString($model, $column, $length = 7, $case = false)
  {
    $str = Str::random($length);
    $modelClass = "\\App\\Models\\" . $model;
    $model = new $modelClass;
    while ($model::where($column, $str)->exists()) {
      $str = Str::random($length);
    }
    if ($case == "lower")
      $str = strtolower($str);
    else if ($case == "upper")
      $str = strtoupper($str);
    return $str;
  }
}
if (!function_exists('generateResponse')) {
  function generateResponse(array $response, $data = array())
  {
    if (!empty($data)) {
      $response['data'] = $data;
    }
    return new GeneralResponse($response);
  }
}
if (!function_exists('convertArrayElemantNullToText')) {
  function convertArrayElemantNullToText($array, $text = "")
  {
    foreach ($array as $key => $value) {
      if ($array[$key] == 'null') {
        $array[$key] = "";
      }
    }
    return $array;
  }
}
if (!function_exists('maskEmail')) {

  function maskEmail($email)
  {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      return $email; // Return original email if not valid
    }

    list($username, $domain) = explode('@', $email);
    $maskLength = max(4 - strlen($username), 3); // Ensure mask length is non-negative

    // Mask only if username length is less than or equal to 4
    if (strlen($username) <= 4) {
      $username = str_repeat('*', strlen($username));
    } else {
      $username = substr($username, 0, 3) . str_repeat('*', $maskLength);
    }

    return $username . '@' . $domain;
  }
}
if (!function_exists('addMonthsTodate')) {

  function addMonthsTodate($date, $months)
  {
    // Convert the date string to a timestamp
    $timestamp = strtotime($date);

    // Add the desired number of months to the timestamp
    $timestamp = strtotime($months . ' month', $timestamp);

    // Format the new date with the desired format
    return date('Y-m-d', $timestamp);
  }
}

if (!function_exists('generatePastelColor')) {
  function generatePastelColor()
  {
    // Set the range for pastel values (higher = lighter)
    $min = 180;
    $max = 255;

    // Generate random values for red, green, and blue
    $red = mt_rand($min, $max);
    $green = mt_rand($min, $max);
    $blue = mt_rand($min, $max);

    // Return the color in RGB format
    return "rgb($red, $green, $blue)";
  }
}
if (!function_exists('generateLinearGradient')) {
  function generateLinearGradient()
  {
    $color1 = generatePastelColor();
    $color2 = generatePastelColor();
    $color3 = generatePastelColor();
    // Generate linear gradient string
    return "linear-gradient(to top right, $color1, $color2, $color3)"; // "linear-gradient(to right top, rgb(247, 247, 172), rgb(242, 165, 207), rgb(252, 178, 202))"  rgb(244, 235, 217)
  }
}
if (!function_exists('hashText')) {
  function hashText($string)
  {
    return hash('sha512', $string . config('app.enc_key'));
  }
}
if (!function_exists('getCountryByPhonecode')) {
  function getCountryByPhonecode($phonecode, $column = "")
  {
    $countryQuery = Country::query();
    $country = $countryQuery->where('phonecode', $phonecode)->first();
    if ($country && $country->toArray()) {
      $country = $country->toArray();
      if ($column) {
        return isset($country[$column]) ? $country[$column] : $country;
      }
      return $country;
    } else {
      return null;
    }
  }
}
if (!function_exists('getCurrentTime')) {
  function getCurrentTime($array = false)
  {
    $time = time();
    $date = date("Y-m-d H:i:s A", $time);
    if ($array) {
      return ["time" => $time, "date" => $date];
    }
    return " " . $time . " <=> " . $date . " ";
  }
}
if (!function_exists('generateUniqueSlug')) {
  function generateUniqueSlug($model, $column, $str)
  {
    $slug = str_replace(" ", "_", strtolower($str));
    $modelClass = "\\App\\Models\\" . $model;
    $model = new $modelClass;
    $count = $model::where($column, $str)->count();
    if ($count) {
      $slug .= "_" . $count;
    }
    return strtolower($slug);
  }
}
if (!function_exists('convertBytes')) {
  function convertBytes($bytes)
  {
    $kilobyte = 1024;
    $megabyte = $kilobyte * 1024;
    $gigabyte = $megabyte * 1024;

    if ($bytes >= $gigabyte) {
      return number_format($bytes / $gigabyte, 2) . ' GB';
    } elseif ($bytes >= $megabyte) {
      return number_format($bytes / $megabyte, 2) . ' MB';
    } elseif ($bytes >= $kilobyte) {
      return number_format($bytes / $kilobyte, 2) . ' KB';
    } else {
      return $bytes . ' bytes';
    }
  }
}
if (!function_exists('convertTimeZone')) {
  function convertTimeZone($time, $fromTZ, $toTz, $return_datetime_format = 'Y-m-d H:i:s')
  {
    try {
      $date = new DateTime($time, new DateTimeZone($fromTZ));
      $date->setTimezone(new DateTimeZone($toTz));
      $time = $date->format($return_datetime_format);
      return $time;
    } catch (Exception $e) {
      return null;
    }
  }
}
if (!function_exists('getTimeZone')) {
  function getTimeZone($searchTimeZone, $output_column = "php_tz", $input_column = "javascript_tz")
  {
    $timeZone = "UTC";
    $timeZoneQuery = TimeZone::query()->where($input_column, $searchTimeZone);
    $timeZoneRow = $timeZoneQuery->first();
    if ($timeZoneRow) {
      if ($timeZoneRow->$output_column)
        $timeZone = $timeZoneRow->$output_column;
    }
    return $timeZone;
  }
}
