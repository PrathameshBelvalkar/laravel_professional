<?php
use App\Models\UserProfile;
use Illuminate\Support\Facades\Log;

if (!function_exists('loginUser')) {
    function loginUser($username, $password)
    {
        try {
            $post_data['username'] = $username;
            $post_data['password'] = $password;
            $url = config("app.sso_base_url") . "Login";
            $ssoLoginResponse = makeCURLCall($url, $post_data, null, true, config("app.bearerToken"));
            return $ssoLoginResponse;
        } catch (Exception $e) {
            Log::info("Login helper ssoLogin " . $e->getMessage());
            return array("status" => false, "message" => "Error while processing");
        }
    }
}
if (!function_exists('check2FA')) {
    function check2FA($user)
    {
        $twoFA = false;
        $userprofile = UserProfile::where('user_id', $user->id)->first();
        if ($userprofile && $userprofile->two_fact_auth != "0") {
            if ($userprofile->two_fact_auth == "1" && $userprofile->two_fact_email_verified == "1") {
                $twoFA = true;
            } else if ($userprofile->two_fact_auth == "2" && $userprofile->two_fact_phone_verified == "1") {
                $twoFA = true;
            } else if ($userprofile->two_fact_auth == "3" && $userprofile->two_fact_phone_verified == "1" && $userprofile->two_fact_email_verified == "1") {
                $twoFA = true;
            }
        }
        return $twoFA;
    }
}
if (!function_exists('registerUser')) {
    function registerUser($request)
    {
        try {
            $post_data['username'] = $request->username;
            if (isset($request->refer_code))
                $post_data['refer_code'] = $request->refer_code;
            $post_data['password'] = $request->password;
            $post_data['email_address'] = $request->email;
            $post_data['country'] = getCountryByPhonecode($request->country_code, "id") ? $request->country_code . "@" . getCountryByPhonecode($request->country_code, "id") : $request->country_code . "@231";
            $post_data['phone_number'] = $request->phone_no;
            $post_data['confirmpassword'] = $request->password;
            $post_data['chkTermsAndConditions'] = "on";
            $post_data['no_otp'] = true;
            $url = config("app.sso_base_url") . "Register";
            $ssoRegisterResponse = makeCURLCall($url, $post_data, null, true, config(("app.bearerToken")));
            return $ssoRegisterResponse;
        } catch (Exception $e) {
            Log::info("Login helper ssoRegister " . $e->getMessage());
            return array("status" => false, "message" => "Error while processing", "cloud_res" => ["status" => false, "message" => $e->getMessage()]);
        }
    }
}
if (!function_exists('checkUserIsRegistered')) {
    function checkUserIsRegistered($username)
    {
        $post_data['username'] = $username;
        $url = config("app.sso_base_url") . "isRegisteredUser";
        $isRegisteredUserResponse = makeCURLCall($url, $post_data, null, true, config("app.bearerToken"));
        return $isRegisteredUserResponse;

    }
}
if (!function_exists('verifyEmailOTP')) {
    function verifyEmailOTP($username, $otp, $email)
    {
        $post_data['username'] = $username;
        $post_data['otp'] = $otp;
        $post_data['email'] = $email;
        $url = config("app.sso_base_url") . "VerifyEmailOTP";
        $verifyEmailOTPResponse = makeCURLCall($url, $post_data, null, true, config("app.bearerToken"));
        return $verifyEmailOTPResponse;
    }
}
if (!function_exists('verifySMSOTP')) {
    function verifySMSOTP($username, $otp)
    {
        $post_data['username'] = $username;
        $post_data['otp'] = $otp;
        $url = config("app.sso_base_url") . "VerifyMobileOTP";
        $verifySMSOTPResponse = makeCURLCall($url, $post_data, null, true, config("app.bearerToken"));
        return $verifySMSOTPResponse;
    }
}
if (!function_exists('resendEmailOTP')) {
    function resendEmailOTP($username, $email)
    {
        $post_data['username'] = $username;
        $post_data['email'] = $email;
        $url = config("app.sso_base_url") . "ResendEmailOTP";
        $verifySMSOTPResponse = makeCURLCall($url, $post_data, null, true, config("app.bearerToken"));
        return $verifySMSOTPResponse;
    }
}
if (!function_exists('resendSMSOTP')) {
    function resendSMSOTP($username, $phone_number, $country)
    {
        $post_data['username'] = $username;
        $post_data['phone_number'] = $phone_number;
        $post_data['country'] = $country;
        $url = config("app.sso_base_url") . "ResendMobileOTP";
        $verifySMSOTPResponse = makeCURLCall($url, $post_data, null, true, config("app.bearerToken"));
        return $verifySMSOTPResponse;
    }
}
if (!function_exists('sendForgotPasswordOTP')) {
    function sendForgotPasswordOTP($username)
    {
        $post_data['username'] = $username;
        $url = config("app.sso_base_url") . "SendForgotOtp";
        $sendForgotPasswordOTPResponse = makeCURLCall($url, $post_data, null, true, config("app.bearerToken"));
        return $sendForgotPasswordOTPResponse;
    }
}
if (!function_exists('verifyForgotPasswordOTP')) {
    function verifyForgotPasswordOTP($username, $otp)
    {
        $post_data['username'] = $username;
        $post_data['otp'] = $otp;
        $url = config("app.sso_base_url") . "SendForgotVerifyOtp";
        $verifyForgotPasswordOTPResponse = makeCURLCall($url, $post_data, null, true, config("app.bearerToken"));
        return $verifyForgotPasswordOTPResponse;
    }
}
if (!function_exists('resetForgotPassword')) {
    function resetForgotPassword($userId, $newPassword, $confirmPassword)
    {
        $post_data['userId'] = $userId;
        $post_data['newPassword'] = $newPassword;
        $post_data['confirmPassword'] = $confirmPassword;
        $url = config("app.sso_base_url") . "ForgotPassword";
        $resetForgotPasswordRes = makeCURLCall($url, $post_data, null, true, config("app.bearerToken"));
        return $resetForgotPasswordRes;
    }
}
if (!function_exists('changePassword')) {
    function changePassword($sso_user_id, $oldPassword, $newPassword)
    {
        $post_data['cloud_user_id'] = $sso_user_id;
        $post_data['newPassword'] = $newPassword;
        $post_data['oldPassword'] = $oldPassword;
        $url = config("app.sso_base_url") . "changePassword";
        $changePasswordRes = makeCURLCall($url, $post_data, null, true, config("app.bearerToken"));
        return $changePasswordRes;
    }
}