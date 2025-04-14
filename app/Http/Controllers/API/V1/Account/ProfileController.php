<?php

namespace App\Http\Controllers\API\V1\Account;

use App\Http\Requests\Account\Verify2FAOTP;
use App\Models\Country;
use App\Models\Subscription\AffiliateMaster;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\Account\SetProfileRequest;
use App\Http\Requests\Account\ChangePasswordRequest;
use App\Http\Requests\Account\Request2FAOTP;
use App\Models\VerificationLog;
use Carbon\Carbon;
use App\Mail\SendMail;
use App\Models\CommunityUserProfile;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
  public function getProfile(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      if ($request->has("user_id")) {
        $user = User::where("id", $request->user_id)->first();
      }
      $keys = generateUserKeys($user->id);
      $fields = isset($request->fields) ? $request->fields : null;
      if ($fields) {
        $fields = explode(",", $fields);
      }

      if (!$user->profile)
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'No data found', 'toast' => false]);

      $user_profile = getProfile($user->id, $fields);
      $refer_data = null;
      $affliate = AffiliateMaster::where("status", "1")->orderBy("updated_at", "desc")->first();
      if ($affliate && $affliate->toArray()) {
        $refer_link = config("app.account_url") . "register/" . $keys['public_key'];
        $socialLinks[] = array('name' => "facebook", "url" => "https://www.facebook.com/sharer/sharer.php?u=$refer_link");
        $socialLinks[] = array('name' => "twitter", "url" => "https://twitter.com/intent/tweet?url=$refer_link");
        $socialLinks[] = array('name' => "linkedin", "url" => "https://www.linkedin.com/shareArticle?mini=true&url=$refer_link&source=" . config("app.parent_domain"));
        $refer_data = ['name' => $affliate->name, "description" => $affliate->description, "refer_link" => $refer_link, 'socials' => $socialLinks];
      }
      $token_value = getTokenMetricsValues();

      $communityProfile = CommunityUserProfile::where('user_id', $user->id)->first();
      if ($communityProfile) {
        if ($communityProfile->profile_image_path) {
          $profileImagePath = $communityProfile->profile_image_path;
          $profileImageTemporaryUrl = Storage::temporaryUrl($profileImagePath, now()->addMinutes(60));
          $communityProfile->profile_image_temporary_url = $profileImageTemporaryUrl;
        } else {
          $communityProfile->profile_image_path = null;
        }
      }
      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User profile data retrieved successfully', 'toast' => true, 'data' => ["profile" => $user_profile, "account_tokens" => $user->account_tokens, "refer_data" => $refer_data, "token_value" => $token_value, "communityProfile" => $communityProfile]]);
    } catch (\Exception $e) {
      Log::info('profile Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true, 'data' => ["account_tokens" => 0]]);
    }
  }
  public function setProfile(SetProfileRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');

      if ($user->profile) {
        $userprofile = UserProfile::where('user_id', $user->id)->first();
        $tempNotificationArr = json_decode($userprofile->notifications, true);
      } else {
        $userprofile = new UserProfile();
        $userprofile->user_id = $user->id;
        $tempNotificationArr = [];
      }

      if ($request->has('first_name')) {
        $user->first_name = $request->first_name;
        $userprofile->first_name = $request->first_name;
      }
      if ($request->has('last_name')) {
        $user->last_name = $request->last_name;
        $userprofile->last_name = $request->last_name;
      }
      if ($request->has('address_1')) {
        $userprofile->address_1 = $request->address_1;
      }
      if ($request->has('address_2')) {
        $userprofile->address_2 = $request->address_2;
      }
      if (isset($request->country_value)) {
        $userprofile->country = $request->country_value;
      }
      if (isset($request->state)) {
        $userprofile->state = $request->state;
      }
      if ($request->has('dob')) {
        $userprofile->dob = $request->dob;
      }
      if ($request->has('pin_code') && (int) $request->pin_code) {
        $userprofile->pin_code = (int) $request->pin_code;
      }
      if ($request->has('phone_number')) {
        $userprofile->phone_number = $request->phone_number;
      }
      if ($request->has('profileIso')) {
        $userprofile->profileIso = $request->profileIso;
        $countryData = Country::where("phonecode", $userprofile->profileIso)->select(['id'])->first();
        if ($countryData && $countryData->toArray()) {
          $userprofile->country = $countryData->id;
        }
      }
      if (isset($request->reset_img)) {
        $userprofile->profile_image_path = null;
      }
      if ($request->hasFile('original_file') && $request->hasFile('crop_file')) {

        $uploadCropFile = $request->file('crop_file');
        $cropFileName = $user->id . '.' . $uploadCropFile->getClientOriginalExtension();
        $cropFilePath = "users/private/{$user->id}/UserProfile/{$cropFileName}";
        Storage::put($cropFilePath, file_get_contents($uploadCropFile));
        $userprofile->profile_image_path = $cropFilePath;

        $uploadOriginalFile = $request->file('original_file');
        $originalFileName = $user->id . '_original.' . $uploadOriginalFile->getClientOriginalExtension();
        $originalFilePath = "users/private/{$user->id}/UserProfile/{$originalFileName}";
        Storage::put($originalFilePath, file_get_contents($uploadOriginalFile));
      }
      if (isset($request->city)) {
        $userprofile->city = $request->city;
      }
      if (isset($request->about_me)) {
        $userprofile->about_me = $request->about_me;
      }
      $temp = null;
      if (isset($request->notifications)) {
        $temp = explode("__", $request->notifications);
        if (count($temp) > 1) {
          $tempNotificationArr[$temp[0]] = $temp[1];
          $userprofile->notifications = json_encode($tempNotificationArr);
        }
      }
      $userprofile->save();
      $user->save();
      DB::commit();

      $profile_pic = (isset($userprofile->profile_image_path) && $userprofile->profile_image_path) ?
        getFileTemporaryURL($userprofile->profile_image_path, 1440) : null;

      return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User profile updated', 'toast' => true, 'data' => ["profile_pic" => $profile_pic, "profile" => $userprofile->toArray()]]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::info('profile Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function changePassword(ChangePasswordRequest $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');
      $changePasswordRes = changePassword($user->sso_user_id, $request->old_password, $request->new_password);
      if ($changePasswordRes['status']) {
        $user->update(['password' => hashText($request->new_password)]);
        DB::commit();
        return generateResponse(['type' => 'success', "status" => true, 'code' => 200, 'message' => 'Password changed successfully', 'toast' => true], [...$changePasswordRes]);
      } else {
        return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => $changePasswordRes['message'], 'toast' => true], [...$changePasswordRes]);
      }
    } catch (\Exception $e) {
      DB::rollback();
      Log::info('profile changePassword Error : ' . $e->getMessage());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function getProfileImg(Request $request)
  {
    try {
      $user = $request->attributes->get('user');
      $type = "original";
      if (isset($request->type)) {
        $type = $request->type;
      }
      $file = UserProfile::where('user_id', $user->id)->first();

      if ($file) {
        if ($file->profile_image_path) {
          if ($type == 'crop') {
            $filePath = storage_path('app/' . $file->profile_image_path);
            if (!file_exists($filePath)) {
              return new JsonResponse([
                'code' => 204,
                'message' => "Profile image not found",
              ], 204);
            } else {
              return response()->file($filePath);
            }
          } else {
            $tempFilePath = explode("/", $file->profile_image_path);
            if ($tempFilePath) {
              $tempFile = $tempFilePath[count($tempFilePath) - 1];
              if ($tempFile) {
                $tempFileArr = explode(".", $tempFile);
                if ($tempFileArr && is_array($tempFileArr)) {
                  $tempFileExt = isset($tempFileArr[1]) ? $tempFileArr[1] : null;
                  if ($tempFileExt) {
                    $fileName = $user->id . '_original.' . $tempFileExt;
                    $fileName = "users/private/{$user->id}/UserProfile/{$fileName}";
                    $filePath = storage_path('app/' . $fileName);
                    if (!file_exists($filePath)) {
                      $tempReturn = false;
                    } else {
                      return response()->file($filePath);
                    }
                  }
                }
              }
            }
            return new JsonResponse([
              'code' => 204,
              'message' => "Profile image not found",
            ], 204);
          }
        } else {
          return new JsonResponse([
            'code' => 204,
            'message' => "Profile image not found",
          ], 204);
        }
      }
      return new JsonResponse([
        'code' => 204,
        'message' => "Profile image not found",
      ], 204);
    } catch (\Exception $e) {
      Log::info('File add error : ' . $e->getMessage());
      return new JsonResponse([
        'code' => 204,
        'message' => "Profile image not found",
      ], 204);
    }
  }
  public function getOnlyProfileImg(Request $request)
  {
    try {
      $user = $request->attributes->get('user');

      $file = UserProfile::where('user_id', $user->id)->first();

      if ($file) {
        if ($file->profile_image_path) {
          $filePath = storage_path('app/' . $file->profile_image_path);


          if (!file_exists($filePath)) {
            return new JsonResponse([
              'code' => 404,
              'message' => 'File not found',
            ], 404);
          } else {
            return response()->file($filePath);
          }
        } else {
          return new JsonResponse([
            'code' => 404,
            'message' => 'File data not found',
          ], 404);
        }
      }
      return new JsonResponse([
        'code' => 404,
        'message' => 'File fetching problem',
      ], 404);
    } catch (\Exception $e) {
      Log::info('File add error : ' . $e->getMessage());
      return new JsonResponse([
        'code' => 404,
        'message' => $e->getMessage(),
      ], 404);
    }
  }
  public function set2FAProfile(Request2FAOTP $request)
  {
    $max_verify_2fa_requests = config("app.max_verify_2fa_requests");
    DB::beginTransaction();
    try {
      $user = $request->attributes->get('user');

      if ($user->profile) {
        $userprofile = UserProfile::where('user_id', $user->id)->first();
      } else {
        $userprofile = new UserProfile();
        $userprofile->user_id = $user->id;
      }



      if ($request->two_fact_auth == '0') {
        $userprofile->two_fact_auth = "0";
        $userprofile->two_fact_email_otp = null;
        $userprofile->two_fact_phone_otp = null;
        $userprofile->two_fact_email_otp_time = null;
        $userprofile->two_fact_phone_otp_time = null;

        $userprofile->save();

        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Two-Factor Authentication switched to off', 'toast' => true, 'data' => ["profile" => $userprofile->toArray()]]);
      } else {
        $email = null;
        $phone_no = null;
        $verificationLogCount = VerificationLog::where('user_id', $user->id)
          ->where('verification_purpose', "3")
          ->whereDate('created_at', Carbon::today())
          ->count();

        if ($verificationLogCount >= $max_verify_2fa_requests) {
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You have exceeded the maximum number of attempts for today', 'toast' => true]);
        }
        $lastRequestTime = VerificationLog::where('user_id', $user->id)
          ->where('verification_purpose', "3")
          ->orderBy('created_at', 'desc')
          ->value('created_at');

        if ($lastRequestTime && $lastRequestTime->diffInSeconds(now()) < 60) {
          return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You can request OTP only once per minute. Please wait.', 'toast' => true]);
        }

        $email_otp = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
        $phone_otp = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);


        $userprofile->two_fact_auth = $request->two_fact_auth;
        $userprofile->two_fact_email = $request->two_fact_email;
        $userprofile->two_fact_email_otp = $request->two_fact_auth == '1' || $request->two_fact_auth == '3' ? $email_otp : null;
        $email_otp;
        $userprofile->two_fact_phone_otp = $request->two_fact_auth == '2' || $request->two_fact_auth == '3' ? $phone_otp : null;
        $userprofile->two_fact_email_otp_time = $request->two_fact_auth == '1' || $request->two_fact_auth == '3' ? now() : null;;
        $userprofile->two_fact_phone_otp_time = $request->two_fact_auth == '2' || $request->two_fact_auth == '3' ? now() : null;
        $userprofile->two_fact_email_verified = "0";
        $userprofile->two_fact_phone_verified = "0";
        if ($request->two_fact_auth == '2' || $request->two_fact_auth == '3')
          $userprofile->two_fact_phone = $request->two_fact_phone;
        $userprofile->save();

        $verification_logs = new VerificationLog();

        $verification_logs->user_id = $user->id;
        if ($request->two_fact_auth == '1') {
          $verification_logs->sent_to = $request->two_fact_email;
        } else if ($request->two_fact_auth == '2') {

          $verification_logs->sent_to = $request->phonecode . $request->two_fact_phone;
        } else if ($request->two_fact_auth == '3') {
          $verification_logs->sent_to = $request->two_fact_email . "&&" . $request->phonecode . $request->two_fact_phone;
        }

        $verification_logs->verification_purpose = '3';
        if ($request->two_fact_auth == '1' || $request->two_fact_auth == '3') {
          $email = $request->two_fact_email;
          $emailData['subject'] = "Two-Factor Authentication Email OTP";
          $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
          $emailData['title'] = "Two-Factor Authentication Email OTP";
          $emailData['otp'] = $email_otp;
          $emailData['view'] = 'mail-templates.two_factor_auth';
          $emailData['username'] = $user->username;
          $emailData['projectName'] = config('app.app_name');
          $emailData['supportMail'] = config('app.support_mail');
          Mail::to($request->two_fact_email)->send(new SendMail($emailData, $emailData['view']));
        }
        if ($request->two_fact_auth == '2' || $request->two_fact_auth == '3') {
          $country = Country::where("phonecode", $request->phonecode)->first();
          $userprofile->country_id = $country->id;
          $userprofile->save();
          $message = "Use $phone_otp for to switch on 2FA to your " . config("app.app_name") . " account";
          $fromNumber = config('app.twilio_number');
          $account_sid = config('app.twilio_account_sid');
          $auth_token = config('app.twilio_auth_token');
          $phone_no = $phone_number = "+$country->phonecode" . $request->two_fact_phone;

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
          } else {
            DB::rollback();
            if ($request->two_fact_auth == "2")
              return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true], ['sms_response' => $sms_response]);
          }
        }

        $verification_logs->save();
        DB::commit();
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Two factor request OTP sent successfully', 'toast' => true, 'data' => ["email" => $email, "phone_no" => $phone_no]]);
      }
    } catch (\Exception $e) {
      DB::rollback();
      Log::info('profile Error : ' . $e->getMessage() . " " . $e->getLine());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
  public function verify2FAOTP(Verify2FAOTP $request)
  {
    DB::beginTransaction();
    $verifiedType = "";
    try {
      $user = $request->attributes->get('user');
      $otp = $request->input('otp');

      $userprofile = UserProfile::where('user_id', $user->id)->first();

      if (!$userprofile) {
        return generateResponse(['type' => 'error', 'code' => 404, 'status' => false, 'message' => 'User profile not found', 'toast' => true]);
      }
      if ($userprofile->two_fact_auth == '1') {
        if (!isset($request->two_fact_email)) {
          return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Provide email address', 'toast' => true]);
        }
        if ($userprofile->two_fact_email == $request->two_fact_email && $userprofile->two_fact_email_otp == $otp) {
          $userprofile->two_fact_email_verified = "1";
          $userprofile->save();
        } else {
          $userprofile->two_fact_email_verified = "0";
          $userprofile->save();
          DB::commit();
          return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Incorrect Email OTP or email', 'toast' => true]);
        }
      }
      if ($userprofile->two_fact_auth == '2') {
        if (!isset($request->two_fact_phone) && !isset($request->phonecode)) {
          return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Provide phone number with code', 'toast' => true]);
        }
        $country = Country::where("phonecode", $request->phonecode)->first();
        if ($userprofile->two_fact_phone == $request->two_fact_phone && $userprofile->two_fact_phone_otp == $otp && $userprofile->country_id == $country->id) {
          $userOtpTime = UserProfile::where('user_id', $user->id)
            ->value('two_fact_phone_otp_time');
          $userOtpTime = Carbon::createFromFormat('Y-m-d H:i:s', $userOtpTime);

          if ($userOtpTime && $userOtpTime->diffInSeconds(now()) > 300) {
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'OTP Expires', 'toast' => true]);
          }
          $userprofile->two_fact_phone_verified = "1";
          $userprofile->save();
        } else {
          $userprofile->two_fact_phone_verified = "0";
          $userprofile->save();
          DB::commit();
          return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Incorrect Phone OTP or phone', 'toast' => true]);
        }
      }
      if ($userprofile->two_fact_auth == '3') {
        if (isset($request->two_fact_phone) && isset($request->phonecode)) {
          $country = Country::where("phonecode", $request->phonecode)->first();
          if ($userprofile->two_fact_phone == $request->two_fact_phone && $userprofile->two_fact_phone_otp == $otp && $userprofile->country_id == $country->id) {
            $userOtpTime = UserProfile::where('user_id', $user->id)
              ->value('two_fact_phone_otp_time');
            $userOtpTime = Carbon::createFromFormat('Y-m-d H:i:s', $userOtpTime);

            if ($userOtpTime && $userOtpTime->diffInSeconds(now()) > 300) {
              return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'OTP Expires', 'toast' => true]);
            }
            $verifiedType = "mobile";
            $userprofile->two_fact_phone_verified = "1";
            $userprofile->save();
          } else {
            $userprofile->two_fact_phone_verified = "0";
            $userprofile->save();
            DB::commit();
            return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Incorrect Phone OTP or phone', 'toast' => true]);
          }
        } else if (isset($request->two_fact_email)) {
          if ($userprofile->two_fact_email == $request->two_fact_email && $userprofile->two_fact_email_otp == $otp) {
            $verifiedType = "email";
            $userprofile->two_fact_email_verified = "1";
            $userprofile->save();
          } else {
            $userprofile->two_fact_email_verified = "0";
            $userprofile->save();
            DB::commit();
            return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Incorrect Email OTP or email', 'toast' => true]);
          }
        } else {
          return generateResponse(['type' => 'error', 'code' => 400, 'status' => false, 'message' => 'Provide contact', 'toast' => true]);
        }
      }
      if ($userprofile->two_fact_auth == "1" && $userprofile->two_fact_email_verified) {
        $userprofile->two_fact_email_otp = null;
        $userprofile->two_fact_phone_otp = null;
        $userprofile->two_fact_email_otp_time = null;
        $userprofile->two_fact_phone_otp_time = null;
        $userprofile->save();
        DB::commit();
        $twoFaData = get2FAData($user->profile, $user->email);
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Verified OTP and 2FA switched on', 'toast' => true], ['twoFaData' => $twoFaData]);
      }
      if ($userprofile->two_fact_auth == "2" && $userprofile->two_fact_phone_verified) {
        $userprofile->two_fact_email_otp = null;
        $userprofile->two_fact_phone_otp = null;
        $userprofile->two_fact_email_otp_time = null;
        $userprofile->two_fact_phone_otp_time = null;
        $userprofile->save();
        DB::commit();
        $twoFaData = get2FAData($user->profile, $user->email);
        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Verified OTP and 2FA switched on', 'toast' => true], ['twoFaData' => $twoFaData]);
      }
      if ($userprofile->two_fact_auth == "3") {
        if ($userprofile->two_fact_phone_verified && $userprofile->two_fact_email_verified) {
          $userprofile->two_fact_email_otp = null;
          $userprofile->two_fact_phone_otp = null;
          $userprofile->two_fact_email_otp_time = null;
          $userprofile->two_fact_phone_otp_time = null;
          $userprofile->save();
          DB::commit();
          $twoFaData = get2FAData($user->profile, $user->email);
          return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Verified OTP and 2FA switched on', 'toast' => true], ['twoFaData' => $twoFaData, '2fa' => true, "verifiedType" => $verifiedType]);
        } else {
          DB::commit();
          return generateResponse(['type' => 'info', 'code' => 200, 'status' => true, 'message' => 'Verified OTP and other contact need to be verified', 'toast' => true], ['2fa' => false, "verifiedType" => $verifiedType]);
        }
      }
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    } catch (\Exception $e) {
      DB::rollback();
      Log::info('profile Error : ' . $e->getMessage() . " " . $e->getLine());
      return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
    }
  }
}
