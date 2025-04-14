<?php

use App\Mail\SendMail;
use App\Models\Country;
use App\Models\Subscription\AffiliateMaster;
use App\Models\Subscription\AffiliateReward;
use App\Models\UserProfile;
use App\Models\VerificationLog;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

if (!function_exists('sendVerificationEmail')) {
    function sendVerificationEmail($userRow, $verificationType, $data, $sso_id = null)
    {
        $verifyCode = Str::random(40) . hashPass($userRow->id);
        $payload = [
            'exp' => time() + (int) config('app.verify_token_exp'),
            'iat' => $now = time(),
            'jti' => md5(($now) . mt_rand()),
            'username' => $userRow->username,
            'email' => $userRow->email,
            'cloudUserId' => $userRow->id,
            'verificationType' => $verificationType, // 1 -> account verification 2 -> forgot verification
        ];

        $verifyToken = JWT::encode($payload, config('app.enc_key'), 'HS256');
        $verifyTime = date('Y-m-d H:i:s');
        $verifyTimePlusHour = strtotime('+24 hour', strtotime($verifyTime));
        $verifyTimePlusHourFormatted = date('l, Y-m-d H:i:s', $verifyTimePlusHour);
        $data['verifyLink'] = config("app.account_url") . "verify-account/$verifyToken/$verifyCode";
        if ($verificationType == 2)
            $data['verifyLink'] = config("app.account_url") . "reset-password/$verifyToken/$verifyCode";
        $data['username'] = $userRow->username;
        $data['subMessage'] = "Link will be valid till $verifyTimePlusHourFormatted";
        $data['projectName'] = config('app.app_name');
        $data['supportMail'] = config('app.support_mail');
        $data['logoUrl'] = asset('assets/images/logo/logo-dark.png');

        try {
            if (!$sso_id) {

                Mail::to($userRow->email)->send(new SendMail($data, $data['view']));
                $userRow->verify_code = $verifyCode;
                $userRow->verify_token = $verifyToken;
                $userRow->verify_link_time = $verifyTime;
                $userRow->save();

                $verificationLogData = ['user_id' => $userRow->id, 'sent_to' => $userRow->email, 'verification_purpose' => $verificationType];
                VerificationLog::create($verificationLogData);

                $verificationLogCount = VerificationLog::where('user_id', $userRow->id)
                    ->where('verification_purpose', $verificationType)
                    ->whereDate('created_at', Carbon::today())
                    ->count();


                $attemps_remaining = config("app.max_verify_email_requests") - $verificationLogCount;
                $msg = "Verification mail link sent successfully to your email address(" . maskEmail($userRow->email) . ")";

                if ($attemps_remaining < 3) {
                    $msg = "Verification mail link sent successfully to your email address(" . maskEmail($userRow->email) . "), attempts remaining : " . $attemps_remaining;
                }
                if ($verificationType == 2) {
                    $msg = "Reset password link sent successfully to your email address(" . maskEmail($userRow->email) . ")";

                    if ($attemps_remaining < 3) {
                        $msg = "Reset password link sent successfully to your email address(" . maskEmail($userRow->email) . "), attempts remaining : " . $attemps_remaining;
                    }
                }
                return ([['type' => 'success', 'code' => 200, 'status' => true, 'message' => $msg, 'toast' => true], ['count' => $verificationLogCount, 'attemps_remaining' => $attemps_remaining]]);
            } else {
                return ([['type' => 'success', 'code' => 200, 'status' => true, 'message' => "Registration Successfully", 'toast' => true], []]);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            $message = "Something went wrong during mail sending";
            return ([['type' => 'error', 'code' => 500, 'status' => false, "errors" => $message, 'message' => $message, 'toast' => true], array()]);
        }
    }
}
if (!function_exists('verifyAccountToken')) {
    function verifyAccountToken($verifyToken, $verifyCode)
    {
        try {
            $decoded = JWT::decode($verifyToken, new Key(config('app.enc_key'), 'HS256'));
        } catch (\Exception $e) {
            return (['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Token is invalid or broken', 'toast' => true]);
        }
        $email = $decoded->email;
        $username = $decoded->username;
        $cloudUserId = $decoded->cloudUserId;
        $verificationType = $decoded->verificationType;
        $userRow = User::where('email', $email)
            ->where('username', $username)
            ->where('id', $cloudUserId)
            ->first();

        if ($userRow) {
            if ($userRow->verify_email == "1" && $verificationType == "1") {
                return (['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Email is already verified', 'toast' => true]);
            } else if ($verificationType == "2" && $userRow->forgot_verify == "0") {
                return (['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Link is not valid', 'toast' => true]);
            }
            if ($userRow->verify_code != $verifyCode || $userRow->verify_token != $verifyToken) {
                return (['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Token/Code is invalid', 'toast' => true]);
            }
            $currentTime = strtotime(date("Y-m-d H:i:s"));
            $linkTime = strtotime($userRow->verify_link_time);
            $elapsedTimeInSecond = $currentTime - $linkTime;
            if ($elapsedTimeInSecond > config("app.verify_token_exp") || !$userRow->verify_link_time) {
                return (['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Link is expired', 'toast' => true]);
            } else {
                return (['status' => true, 'user' => $userRow]);
            }
        } else {
            return (['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'No user found, May be link is expired or broken', 'toast' => true]);
        }
    }
}
if (!function_exists('generateAuthToken')) {
    function generateAuthToken($user)
    {
        $role = $user->role->toArray();
        $payload = [
            'exp' => time() + (int) config('app.verify_token_exp'),
            'iat' => $now = time(),
            'jti' => md5(($now) . mt_rand()),
            'username' => $user->username,
            'email' => $user->email,
            'cloudUserId' => $user->id,
            'cloud_user_id' => $user->sso_user_id ? $user->sso_user_id : null,
            'role' => ["id" => $role['id'], "name" => $role['name'], "key" => $role['key']],
        ];

        return JWT::encode($payload, config('app.enc_key'), 'HS256');
    }
}
if (!function_exists('sendLoginFailActivity')) {
    function sendLoginFailActivity($user)
    {
        try {

            $user_profile = UserProfile::where('user_id', $user->id)->first();

            if ($user_profile) {

                $notification = json_decode($user_profile->notifications, true);

                if (array_key_exists("unusual_activity", $notification) && $notification['unusual_activity'] === "1") {
                    $emailData['subject'] = "Unusual Activity Alert";
                    $emailData['logoUrl'] = asset('assets/images/logo/logo-dark.png');
                    $emailData['title'] = "Multiple unsuccessful login attempts alert";
                    $emailData['view'] = 'mail-templates.login-attempt-alert';
                    $emailData['username'] = $user->username;
                    $emailData['projectName'] = config('app.app_name');
                    $emailData['supportMail'] = config('app.support_mail');
                    Mail::to($user->email)->send(new SendMail($emailData, $emailData['view']));
                }
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }
}
if (!function_exists('generateUserKeys')) {
    function generateUserKeys($user_id)
    {
        $keys = array("public_key" => null, "private_key" => null);
        $user = User::where("id", $user_id)->first();
        if ($user && $user->toArray()) {
            if (!$user->public_key) {
                $keys['public_key'] = generateUniqueString("User", "public_key", 32);
                $user->public_key = $keys['public_key'];
            } else {
                $keys['public_key'] = $user->public_key;
            }
            if (!$user->private_key) {
                $keys['private_key'] = generateUniqueString("User", "private_key", 32);
                $user->private_key = $keys['private_key'];
            } else {
                $keys['private_key'] = $user->private_key;
            }
            $user->save();
        }
        return $keys;
    }
}
if (!function_exists('addAffiliateRewardRequest')) {
    function addAffiliateRewardRequest($public_key, $new_user_id)
    {
        $user = User::where('public_key', $public_key)->first();
        $new_user = User::where('id', $new_user_id)->first();
        $affliate = AffiliateMaster::where("status", "1")->orderBy("updated_at", "desc")->first();
        if ($user && $user->toArray() && $new_user && $new_user->toArray() && $affliate && $affliate->toArray()) {
            $affiliate_request = new AffiliateReward();
            $affiliate_request->affiliate_master_id = $affliate->id;
            $affiliate_request->affiliate_id = $new_user->id;
            $affiliate_request->refered_id = $user->id;
            $affiliate_request->save();
        }
    }
}
if (!function_exists('getProfile')) {
    function getProfile($user_id, $fields)
    {
        $user = User::where("id", $user_id)->first();
        $user_profile = UserProfile::where('user_id', $user->id)->first();
        if ($user_profile && $user_profile->toArray() && !$user_profile->cover_img) {
            $user_profile['cover_img'] = generateLinearGradient();
            $user_profile->save();
        }
        $user_profile['phonecode'] = null;
        $user_profile = convertArrayElemantNullToText($user_profile->toArray());


        if (isset($user_profile['notifications'])) {
            $user_profile['notifications'] = json_decode($user_profile['notifications'], true);
        }
        if ($fields) {
            $user_profile = array_intersect_key($user_profile, array_flip($fields));
            if (!$user_profile)
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Enter valid inputs', 'toast' => false, 'data' => ["account_tokens" => $user->account_tokens]]);
        }
        if (!$user_profile['dob']) {
            $user_profile['dob'] = null;
        }
        $user_profile['profile_iso'] = null;
        if (isset($user_profile['country']) && $user_profile['country']) {
            $country = Country::where('id', $user_profile['country'])->first();
            if ($country) {
                $user_profile['country'] = $country->name ? $country->name : null;
                $user_profile['country_value'] = $country->id ? $country->id : null;
                $user_profile['profile_iso'] = $country->shortname ? ucwords($country->shortname) : null;
            }
        }
        unset($user_profile['profile_iso']);
        $user_profile['2fa_iso'] = null;
        if (isset($user_profile['country_id']) && $user_profile['country_id']) {
            $twofa_country = Country::where('id', $user_profile['country_id'])->first();
            if ($twofa_country) {
                $user_profile['phonecode'] = $twofa_country->phonecode ? $twofa_country->phonecode : null;
                $user_profile['2fa_iso'] = $twofa_country->shortname ? ucwords($twofa_country->shortname) : null;
            }
        }
        if (!$user_profile['2fa_iso']) {
            $country = Country::where('id', $user_profile['country'])->first();
            if ($country) {
                $user_profile['2fa_iso'] = $country->shortname ? ucwords($country->shortname) : null;
            }
        }
        unset($user_profile['two_fact_phone_otp']);
        unset($user_profile['two_fact_email_otp']);
        $user_profile['two_fact_auth_status'] = get2FAStatus($user);

        if ($user_profile['profile_image_path'] != NULL) {
            $user_profile['profile_image_path'] = getFileTemporaryURL($user_profile['profile_image_path']);
        }
        if ($user_profile['about_me'] && $user_profile['about_me'] != NULL) {
            $user_profile['bio'] = $user_profile['about_me'];
        }
        $user_profile['username'] = $user->username;
        $user_profile['email'] = $user->email;
        $user_profile['member'] = getSubscribedPackageDataByKey($user->id) ? getSubscribedPackageDataByKey($user->id) . " Member" : null;

        if (!$user_profile['cover_img']) {
            $user_profile['cover_img'] = generateLinearGradient();
        }
        $user_profile['connection_summary'] = getConnectionSummary($user, false);
        return $user_profile;
    }
}
if (!function_exists('getProfileByUsers')) {
    function getProfileByUsers($profile)
    {
        $tempProfile = [];
        $user_profile['phonecode'] = null;
        $user_profile = convertArrayElemantNullToText($profile);
        if (!$user_profile['dob']) {
            $tempProfile['dob'] = null;
        }
        $tempProfile['profile_image_path'] = getFileTemporaryURL($user_profile['profile_image_path']);
        if (isset($user_profile['about_me']) && $user_profile['about_me']) {
            $tempProfile['about_me'] = $user_profile['about_me'];
        }
        return $tempProfile;
    }
}
if (!function_exists('getUsersContact')) {
    function getUsersContact($user_id)
    {
        $contact['country_code'] = null;
        $contact['phone_no'] = null;

        $userExists = UserProfile::where("user_id", $user_id)->exists();

        if ($userExists) {
            $user_profile = UserProfile::where("user_id", $user_id)->first();
            $countryQuery = Country::query();
            if ($user_profile->country_id && $user_profile->two_fact_phone && $user_profile->two_fact_phone_verified) {
                $countryQuery->where("id", $user_profile->country_id);
                if ($countryQuery->count()) {
                    $country = $countryQuery->first();
                    $contact['country_code'] = $country->phonecode;
                    $contact['phone_no'] = $user_profile->two_fact_phone;
                }
            } else if ($user_profile->phone_number && $user_profile->profileIso) {
                $countryQuery->where("phonecode", $user_profile->profileIso);
                if ($countryQuery->count()) {
                    $country = $countryQuery->first();
                    $contact['country_code'] = $country->phonecode;
                    $contact['phone_no'] = $user_profile->phone_number;
                }
            }
        }
        return $contact;
    }
}
if (!function_exists('sendWelcomeMail')) {
    function sendWelcomeMail($username, $email)
    {
        $data['logoUrl'] = asset('assets/images/logo/logo-dark.png');
        $data['title'] = "Welcome to " . config('app.app_name');
        $data['username'] = ucfirst($username);
        $data['subject'] = $data['title'];
        $data['link'] = config("app.account_url");
        $data['linkTitle'] = "Login";
        $data['supportMail'] = config('app.support_mail');
        $data['projectName'] = config('app.app_name');
        $data['view'] = "mail-templates.welcome";
        Mail::to($email)->send(new SendMail($data, $data['view']));
    }
}


if (!function_exists('sendAccessGrantedNotification')) {
    function sendAccessGrantedNotification($user, $store, $accessString)
    {
        try {
            // Prepare email data
            $emailData = [
                'subject' => 'Access Granted',
                'logoUrl' => asset('assets/images/logo/logo-dark.png'),
                'title' => 'Your access has been updated',
                'view' => 'emails.grant_access_marketplace',
                'name' => $user->username,
                'merchant_name' => User::find(auth()->id())->first_name . ' ' . User::find(auth()->id())->last_name,
                'store_name' => $store->name,
                'access_names' => $accessString,
                'projectName' => config('app.name'),
                'supportMail' => config('mail.support_address')
            ];


            Mail::to($user->email)->send(new SendMail($emailData, $emailData['view']));
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }
    if (!function_exists('sendEmail')) {
        function sendEmail($to, $subject, $message)
        {
            try {
                Mail::raw($message, function ($mail) use ($to, $subject) {
                    $mail->to($to)
                        ->subject($subject);
                });
            } catch (\Exception $e) {
                Log::info($e->getMessage());
            }
        }
    }




    if (!function_exists('getUserDetails')) {
        function getUserDetails($user_id, array $fields = [])
        {
            $user = User::find($user_id);
            if (!$user) {
                // Return an empty array or handle error differently
                return [];
            }

            $userProfile = UserProfile::where('user_id', $user->id)->first();
            if ($userProfile && !$userProfile->cover_img) {
                $userProfile->cover_img = generateLinearGradient();
                $userProfile->save();
            }

            $userProfileArray = $userProfile ? $userProfile->toArray() : [];
            $userProfileArray['phonecode'] = null;

            // Remove or comment out this line if not needed
            // $userProfileArray = convertArrayElementNullToText($userProfileArray);

            if (isset($userProfileArray['notifications'])) {
                $userProfileArray['notifications'] = json_decode($userProfileArray['notifications'], true);
            }

            if ($fields) {
                $userProfileArray = array_intersect_key($userProfileArray, array_flip($fields));
                if (!$userProfileArray) {
                    return [];
                }
            }

            $userProfileArray['dob'] = $userProfileArray['dob'] ?? null;
            $userProfileArray['profile_iso'] = null;

            if (isset($userProfileArray['country']) && $userProfileArray['country']) {
                $country = Country::find($userProfileArray['country']);
                if ($country) {
                    $userProfileArray['country'] = $country->name ?? null;
                    $userProfileArray['country_value'] = $country->id ?? null;
                    $userProfileArray['profile_iso'] = $country->shortname ? ucwords($country->shortname) : null;
                }
            }

            $userProfileArray['2fa_iso'] = null;
            if (isset($userProfileArray['country_id']) && $userProfileArray['country_id']) {
                $twofaCountry = Country::find($userProfileArray['country_id']);
                if ($twofaCountry) {
                    $userProfileArray['phonecode'] = $twofaCountry->phonecode ?? null;
                    $userProfileArray['2fa_iso'] = $twofaCountry->shortname ? ucwords($twofaCountry->shortname) : null;
                }
            }

            if (!$userProfileArray['2fa_iso']) {
                $country = Country::find($userProfileArray['country']);
                if ($country) {
                    $userProfileArray['2fa_iso'] = $country->shortname ? ucwords($country->shortname) : null;
                }
            }

            unset($userProfileArray['two_fact_phone_otp'], $userProfileArray['two_fact_email_otp']);
            $userProfileArray['two_fact_auth_status'] = get2FAStatus($user);

            $userProfileArray['profile_image_path'] = getFileTemporaryURL($userProfileArray['profile_image_path']);
            $userProfileArray['bio'] = $userProfileArray['about_me'] ?? null;
            $userProfileArray['username'] = $user->username;
            $userProfileArray['email'] = $user->email;
            $userProfileArray['member'] = getSubscribedPackageDataByKey($user->id) ? getSubscribedPackageDataByKey($user->id) . " Member" : null;

            $userProfileArray['cover_img'] = $userProfileArray['cover_img'] ?? generateLinearGradient();
            $userProfileArray['connection_summary'] = getConnectionSummary($user, false);

            return $userProfileArray;
        }
    }
    if (!function_exists('sendTvMail')) {
        function sendTvMail($username, $email, $sender_name, $image_url)
        {
            $data['logoUrl'] = asset('assets/images/logo/logo-dark.png');
            $data['title'] = "Welcome To Silo TV Network";
            $data['username'] = ucfirst($username);
            $data['subject'] = $data['title'];
            $data['image_url'] = $image_url;
            $data['sender_name'] = $sender_name;
            $data['link'] = config("app.account_url");
            $data['linkTitle'] = "Login";
            $data['supportMail'] = config('app.support_mail');
            $data['projectName'] = config('app.app_name');
            $data['view'] = "mail-templates.streamdeck";
            Mail::to($email)->send(new SendMail($data, $data['view']));
        }
    }
    if (!function_exists('sendLiveMail')) {
        function sendLiveMail($username, $email, $sender_name, $image_url)
        {
            $data['logoUrl'] = asset('assets/images/logo/logo-dark.png');
            $data['title'] = "Hey " . $sender_name . " is Live";
            $data['username'] = ucfirst($username);
            $data['subject'] = $data['title'];
            $data['image_url'] = $image_url;
            $data['sender_name'] = $sender_name;
            $data['link'] = config("app.account_url");
            $data['linkTitle'] = "Login";
            $data['supportMail'] = config('app.support_mail');
            $data['projectName'] = config('app.app_name');
            $data['view'] = "mail-templates.streamdeck";
            Mail::to($email)->send(new SendMail($data, $data['view']));
        }
    }
    if (!function_exists('sendConnectionMails')) {
        function sendConnectionMails($usernames, $emails, $sender_name, $image_url, $live_stream)
        {
            if ($live_stream) {
                if (count($usernames) !== count($emails)) {
                    throw new Exception("The number of usernames and emails must be the same.");
                }
                foreach ($usernames as $index => $username) {
                    $email = $emails[$index];
                    sendLiveMail($username, $email, $sender_name, $image_url);
                }
            } else {
                if (count($usernames) !== count($emails)) {
                    throw new Exception("The number of usernames and emails must be the same.");
                }
                foreach ($usernames as $index => $username) {
                    $email = $emails[$index];
                    sendTvMail($username, $email, $sender_name, $image_url);
                }
            }
        }
    }
}
