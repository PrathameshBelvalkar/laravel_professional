<?php

use Carbon\Carbon;
use App\Mail\SendMail;
use App\Models\Country;
use App\Models\UserProfile;
use App\Models\VerificationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

if (!function_exists('send2FAOTP')) {
  function send2FAOTP($userprofile, $user, $now = "")
  {
    try {
      $checkRes = checkMsgLimitTime($user, "3");
      if ($checkRes)
        return $checkRes;
      $otp = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
      $email = $sms = false;
      $phone_code = "";
      if ($userprofile->two_fact_auth == "1" || $userprofile->two_fact_auth == "3") {
        $emailData['subject'] = "Two-Factor Authentication Email OTP";
        $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
        $emailData['title'] = "Two-Factor Authentication Email OTP";
        $emailData['otp'] = $otp;
        $emailData['view'] = 'mail-templates.two_factor_auth';
        $emailData['username'] = $user->username;
        $emailData['projectName'] = config('app.app_name');
        $emailData['supportMail'] = config('app.support_mail');
        Mail::to($userprofile->two_fact_email)->send(new SendMail($emailData, $emailData['view']));
        $userprofile->two_fact_email_otp = $otp;
        $userprofile->two_fact_email_otp_time = now();
        $email = true;
      }
      if ($userprofile->two_fact_auth == "2" || $userprofile->two_fact_auth == "3") {
        $country = Country::where("id", $userprofile->country_id)->first();
        $userprofile->country_id = $country->id;
        $phone_code = $country->phonecode;
        $userprofile->save();
        $message = "Use $otp for login to your " . config("app.app_name") . " account";
        $fromNumber = config('app.twilio_number');
        $account_sid = config('app.twilio_account_sid');
        $auth_token = config('app.twilio_auth_token');
        $phone_number = "+$country->phonecode" . $userprofile->two_fact_phone;

        $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";
        $fields = array(
          'From' => $fromNumber,
          'Body' => $message,
          'To' => $phone_number,
        );
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $temp_sms_response = curl_exec($ch);
        $sms_response = json_decode($temp_sms_response);
        if (isset($sms_response->sid)) {
          $sms = true;
          $userprofile->two_fact_phone_otp = $otp;
          $userprofile->two_fact_phone_otp_time = now();
        } else {
          DB::rollback();
          if ($userprofile->two_fact_auth == "2")
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while sending OTP", 'toast' => true, "data" => ['verifyRequired' => "0"]]);
        }
      }
      if ($sms || $email) {
        $sent_to = "";
        if ($userprofile->two_fact_auth == '1') {
          $sent_to = $userprofile->two_fact_email;
        } else if ($userprofile->two_fact_auth == '2') {
          $sent_to = $phone_code . $userprofile->two_fact_phone;
        } else if ($userprofile->two_fact_auth == '3') {
          $sent_to = $userprofile->two_fact_email . "&&" . $phone_code . $userprofile->two_fact_phone;
        }
        $userprofile->save();
        DB::commit();
        addVerificationLog($sent_to, $user, "3");
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => '2FA OTP sent to your contact', 'toast' => true], ["two_fact_auth" => $userprofile->two_fact_auth, "two_fa" => true, "email" => $userprofile->two_fact_email, "phone" => $userprofile->two_fact_phone]);
      } else
        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true, "data" => ['verifyRequired' => "0"]]);
    } catch (\Exception $e) {
      Log::info('Login with 2FA Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Error while processing", 'toast' => true, "data" => ['verifyRequired' => "0"]]);
    }
  }
}

if (!function_exists('checkMsgLimitTime')) {
  function checkMsgLimitTime($user, $verification_purpose)
  {
    $max_verify_2fa_requests = config("app.max_verify_2fa_requests");
    $verificationLogCount = VerificationLog::where('user_id', $user->id)
      ->where('verification_purpose', $verification_purpose)
      ->whereDate('created_at', Carbon::today())
      ->count();
    if ($verificationLogCount >= $max_verify_2fa_requests) {
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You have exceeded the maximum number of attempts for today to request OTP', 'toast' => true]);
    }
    $lastRequestTime = VerificationLog::where('user_id', $user->id)
      ->where('verification_purpose', $verification_purpose)
      ->orderBy('created_at', 'desc')
      ->value('created_at');
    if ($lastRequestTime && $lastRequestTime->diffInSeconds(now()) < 60) {
      return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You can request OTP only once per minute. Please wait.', 'toast' => true]);
    }
  }
}
if (!function_exists('addVerificationLog')) {
  function addVerificationLog($sent_to, $user, $verification_purpose)
  {
    $verification_log = new VerificationLog();

    $verification_log->user_id = $user->id;
    $verification_log->sent_to = $sent_to;
    $verification_log->verification_purpose = $verification_purpose;
    $verification_log->save();
    return $verification_log;
  }
}
if (!function_exists('verify2FAOtp')) {
  function verify2FAOtp($otp, $userprofile, $user, $type = "login")
  {
    $arr = ["status" => false, "message" => "in proecess"];
    switch ($userprofile->two_fact_auth) {
      case "1":
        if ($userprofile->two_fact_email_otp == $otp) {
          $arr = ["status" => true, "message" => "OTP verified"];
        } else {
          $arr = ["status" => false, "message" => "Wrong OTP"];
        }
        break;
      case "2":
        if ($userprofile->two_fact_phone_otp == $otp) {
          $userOtpTime = UserProfile::where('user_id', $user->id)
            ->value('two_fact_phone_otp_time');
          $userOtpTime = Carbon::createFromFormat('Y-m-d H:i:s', $userOtpTime);

          if ($userOtpTime && $userOtpTime->diffInSeconds(now()) > 300) {
            $arr = ["status" => false, "message" => "OTP expired"];
          } else {
            $arr = ["status" => true, "message" => "OTP verified"];
          }
        } else {
          $arr = ["status" => false, "message" => "Wrong OTP"];
        }
        break;
      case "3":
        if ($userprofile->two_fact_email_otp == $otp || $userprofile->two_fact_phone_otp == $otp) {
          $arr = ["status" => true, "message" => "OTP verified"];
        } else {
          $arr = ["status" => false, "message" => "Wrong OTP"];
        }
        break;
      default:
        $arr = ["status" => false, "message" => "Something went wrong"];
    }
    if ($arr['status'] == true) {
      $userprofile->two_fact_email_otp = null;
      $userprofile->two_fact_email_otp_time = null;
      $userprofile->two_fact_phone_otp = null;
      $userprofile->two_fact_phone_otp_time = null;
      $userprofile->save();

      $user_verification_log = VerificationLog::where('user_id', $user->id)
        ->where('verification_purpose', "3")
        ->whereDate('created_at', Carbon::today())
        ->delete();
    }
    return $arr;
  }
}
if (!function_exists('get2FAStatus')) {
  function get2FAStatus($user)
  {
    $return = false;
    $userprofile = UserProfile::where('user_id', $user->id)->first();
    if ($userprofile) {
      if ($userprofile->two_fact_auth != "0") {
        switch ($userprofile->two_fact_auth) {
          case "1":
            if ($userprofile->two_fact_email_verified) {
              $return = true;
            }
            break;
          case "2":
            if ($userprofile->two_fact_phone_verified) {
              $return = true;
            }
            break;
          case "3":
            if ($userprofile->two_fact_phone_verified && $userprofile->two_fact_email_verified) {
              $return = true;
            }
            break;
          default:
            $return = false;
        }
      }
    }
    return $return;
  }
}
if (!function_exists('get2FAData')) {
  function get2FAData($userprofile, $email = null)
  {
    $data['type'] = "0";
    $data['email'] = $email;
    $countryData = Country::where("phonecode", $userprofile->profileIso)->select(['phonecode', "shortname", 'id'])->first();
    if ($countryData && $countryData->toArray()) {
      $data['country_id'] = $countryData->id;
      $data['country_code'] = $countryData->phonecode;
      $data['country_iso'] = $countryData->shortname;
    } else {
      $data['country_id'] = null;
      $data['country_code'] = null;
      $data['country_iso'] = null;
    }
    switch ($userprofile->two_fact_auth) {
      case "1":
        if ($userprofile->two_fact_email_verified) {
          $data['type'] = "1";
          $data['email'] = $userprofile->two_fact_email;
        }
        break;
      case "2":
        if ($userprofile->two_fact_phone_verified) {
          $data['type'] = "2";
          $data['phone_no'] = $userprofile->two_fact_phone;
          $twoFACountry = Country::where("id", $userprofile->country_id)->select(['phonecode', "shortname"])->first();
          if ($twoFACountry && $twoFACountry->toArray()) {
            $data['country_id'] = $userprofile->country_id;
            $data['country_code'] = $twoFACountry->phonecode;
            $data['country_iso'] = $twoFACountry->shortname;
          } else {
            $data['country_id'] = null;
            $data['country_code'] = null;
            $data['country_iso'] = null;
          }
        }
        break;
      case "3":
        if ($userprofile->two_fact_phone_verified && $userprofile->two_fact_email_verified) {
          $data['type'] = "3";
          $data['email'] = $userprofile->two_fact_email;
          $twoFACountry = Country::where("id", $userprofile->country_id)->select(['phonecode', "shortname"])->first();
          if ($twoFACountry && $twoFACountry->toArray()) {
            $data['country_id'] = $userprofile->country_id;
            $data['country_code'] = $twoFACountry->phonecode;
            $data['country_iso'] = $twoFACountry->shortname;
          } else {
            $data['country_id'] = null;
            $data['country_code'] = null;
            $data['country_iso'] = null;
          }
        }
        break;
      default:
    }
    return json_encode($data);
  }
}
