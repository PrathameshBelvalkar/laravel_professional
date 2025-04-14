<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Requests\Auth\ResendEmailOtpRequest;
use App\Http\Requests\Auth\ResendLinkRequest;
use App\Http\Requests\Auth\ResendSMSOtpRequest;
use App\Http\Requests\Auth\ResetForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SSORegisterUserRequest;
use App\Http\Requests\Auth\VerifyEmailOtpRequest;
use App\Http\Requests\Auth\VerifyLogin2FAOtpRequest;
use App\Http\Requests\Auth\ResendLogin2FAOTP;
use App\Http\Requests\Auth\VerifySmsOtpRequest;
use App\Models\LoginAttempt;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\VerificationLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Auth\LoginUserRequest;
use App\Http\Requests\Auth\VerifyLinkRequest;
use App\Http\Requests\Auth\RegisterUserRequest;


class LoginController extends Controller
{
    public function register(RegisterUserRequest $request)
    {
        try {
            $ssoRegisterResponse = registerUser($request);
            if ($ssoRegisterResponse && isset($ssoRegisterResponse['cloud_res']['status']) && $ssoRegisterResponse['cloud_res']['status']) {
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => $ssoRegisterResponse['cloud_res']['message'], 'toast' => true]);
            } else {
                $errorMessage = isset($ssoRegisterResponse['cloud_res']['message']) ? $ssoRegisterResponse['cloud_res']['message'] : "Error while registering on " . config('app.app_name');
                $ssoErrorMessage = json_encode($ssoRegisterResponse);
                Log::info('ssoRegisterResponse Error : <<<>>>' . $ssoErrorMessage . "<<<>>>");
                $errorMessage = strip_tags($errorMessage);
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => $errorMessage, 'toast' => true], ["cloud_res" => $ssoRegisterResponse]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Register Error : ' . $e->getMessage() . " @" . $e->getLine() . " \nin " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function ssoRegister(SSORegisterUserRequest $request)
    {
        DB::beginTransaction();
        try {
            $useData = array('username', 'first_name', 'last_name', 'email', 'password', 'password_confirmation', 'register_type', 'sso_user_id');
            $data = $request->only($useData);
            $authToken = $request->header('authToken');

            if (isset($request->password)) {
                $data['password'] = hashText($request->password);
            }
            $sso_user_id = null;
            if (isset($request->sso_user_id)) {
                $sso_user_id = $data['sso_user_id'] = $request->sso_user_id;
            }
            $user = User::create($data);

            if ($user) {
                $userprofile = new UserProfile();
                if (isset($request->phone_number)) {
                    $userprofile->phone_number = $request->phone_number;
                }
                if (isset($request->country)) {
                    $requestCountry = explode("@", $request->country);
                    if ($requestCountry[0]) {

                        $countryphonecode = getCountryByPhonecode($requestCountry[0], "phonecode");
                        if ($countryphonecode)
                            $userprofile->profileIso = $countryphonecode;

                        $countryId = getCountryByPhonecode($requestCountry[0], "id");
                        if ($countryId) {
                            $userprofile->country = $countryId;
                            $userprofile->country_id = $countryId;
                        }
                    }
                }
                $userprofile->user_id = $user->id;
                $userprofile->cover_img = generateLinearGradient();
                generateUserKeys($user->id);
                if (isset($request->refer_code)) {
                    addAffiliateRewardRequest($request->refer_code, $user->id);
                }
                $userprofile->save();
                $emailData['subject'] = config("app.app_name") . " account verification";
                $emailData['title'] = "Email verification";
                $emailData['linkTitle'] = "Verify";
                $emailData['message'] = "Please click the below verify link to verify your email address.";
                $emailData['view'] = 'mail-templates.register';

                [$emailResponse, $additionalData] = sendVerificationEmail($user, 1, $emailData, $sso_user_id);
                $title = $description = "Welcome to " . config('app.app_name');
                addNotification($user->id, $user->id, $title, $description, null, "1", "#");


                //SiloMail send welcomwe email
                $silomail_email = User::where('username', 'mail')->first();
                $silocloud_email = User::where('username', 'silocloud')->first();
                $is_draft = 0;
                $mail_data['logo'] = asset('assets/images/mail_public/Welcome.png');
                $mail_data['app_url'] = config('app.url');
                $mail_data['mail_url'] = config('app.mail_url');
                $mail_data['gif'] = asset('assets/images/mail-video/mail.gif');
                $mail_data['mail_image'] = asset('assets/images/socialMedia/mail.png');
                $email1 = [
                    'id' => $silocloud_email->id,
                    'recipients' => $request->username . '@silocloud.io',
                    'cc' => null,
                    'bcc' => null,
                    'subject' => "Welcome to the SiloCloud Family!",
                    'username' => 'SiloCloud',
                    'message' => view('mail-templates.welcome1', compact('mail_data'))->render(),
                ];

                $email2 = [
                    'id' => $silomail_email->id,
                    'recipients' => $request->username . '@silocloud.io',
                    'cc' => null,
                    'bcc' => null,
                    'subject' => "Welcome to the SiloMail!",
                    'username' => 'mail',
                    'message' => view('mail-templates.welcome2', compact('mail_data'))->render()

                ];
                $emails = [$email1, $email2];
                foreach ($emails as $email) {
                    $mailData = new \stdClass();
                    $mailData->recipients = $email['recipients'];
                    $mailData->cc = $email['cc'];
                    $mailData->bcc = $email['bcc'];
                    $mailData->subject = $email['subject'];
                    $mailData->message = $email['message'];

                    $mail = new \stdClass();
                    $mail->username = $email['username'];
                    $mail->id = $email['id'];
                    $isRecipients = $user->id;
                    $mail_sent = saveOrUpdateMail($mailData, $mail, $is_draft, null, $isRecipients, null, null, null, null, null);
                    createMailReply($mail, $mailData, null, $isRecipients, null, $mail_sent ? $mail_sent->id : null, null, null);
                    addNotification($user->id, $user->id, "New Email from $mail->username !", "Check your inbox for the latest message", $mail_sent ? $mail_sent->id : null, "9", "https://mail.silocloud.io/", null, $authToken);
                }
                DB::commit();
                sendWelcomeMail($request->username, $request->email);
                return generateResponse($emailResponse, $additionalData);
            } else {
                DB::rollBack();
                $message = "Error while processing";
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, "errors" => $message, 'message' => $message, 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('SSO Register Error : ' . $e->getFile() . ' @line_no ' . $e->getLine() . ' : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function login(LoginUserRequest $request)
    {

        $max_login_attempts = config('app.max_login_attempts');
        try {
            $user = User::where('username', $request->username)
                ->orWhere('email', $request->username)->with("profile")
                ->first();
            if ($user) {
                // if ($user->verify_email) {
                $role = $user->role->toArray();
                $users_max_login_attempts = 0;
                $user_login_attempts = LoginAttempt::where("user_id", $user->id)->first();
                if ($user_login_attempts) {
                    $today = Carbon::today()->toDateString();
                    $isUpdatedToday = $user_login_attempts->updated_at->toDateString() === $today;
                    if ($isUpdatedToday) {
                        $users_max_login_attempts = $user_login_attempts->count;
                    }
                }

                if ($max_login_attempts > $users_max_login_attempts) {

                    $hashPassword = hashText($request->password);
                    // if ($hashPassword == $user->password) { // temporary password check for api disabled
                    if ($hashPassword) {
                        if ($user_login_attempts) {
                            $user_login_attempts->count = 0;
                            $user_login_attempts->save();
                        }
                        $ssoResult = loginUser($user->username, $request->password);
                        if ($ssoResult) {
                            if ($ssoResult['status']) {
                                $login = true;
                                if (check2FA($user))
                                    $login = false;

                                $ssoTokenURL = $ssoResult['token_url'];
                                $user->verify_email = "1";
                                $user->save();
                                $temp_profile_pic_url = (isset($user->profile->profile_image_path) && $user->profile->profile_image_path) ?
                                    getFileTemporaryURL($user->profile->profile_image_path, 1440) : null;

                                $twoFaData = null;
                                if (isset($user->profile) && $user->profile) {
                                    $twoFaData = get2FAData($user->profile, $user->email);
                                }

                                if ($login) {
                                    $is_influencer = false;
                                    if ($user->is_influencer == "1") {
                                        $is_influencer = true;
                                    }
                                    $authToken = generateAuthToken($user);
                                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User login successful', 'toast' => true], ["twoFaData" => $twoFaData, "profile_pic" => $temp_profile_pic_url, "ssoTokenURL" => $ssoTokenURL, "two_fa" => false, 'authToken' => $authToken, "username" => $user->username, "user_id" => $user->id, 'email' => $user->email, 'role' => ["id" => $role['id'], "name" => $role['name'], "key" => $role['key'], 'is_influencer' => $is_influencer]]);
                                } else {
                                    $userprofile = UserProfile::where('user_id', $user->id)->first();
                                    return send2FAOTP($userprofile, $user, "");
                                }
                            } else {
                                $tempMessage = $ssoResult['message'];
                                $verifyRequired = "0";
                                $ssoResultDataUsername = "";
                                $phone_number = null;
                                $country = null;
                                $email_address = null;
                                if (isset($ssoResult['data'][0])) {
                                    $ssoResultData = $ssoResult['data'][0];

                                    $phone_number = isset($ssoResultData['phone_number']) ? $ssoResultData['phone_number'] : null;
                                    $country = isset($ssoResultData['country']) ? $ssoResultData['country'] : null;
                                    $email_address = isset($ssoResultData['email_address']) ? $ssoResultData['email_address'] : null;

                                    $ssoResultDataUsername = $ssoResultData['username'];
                                    $tempMessage = $ssoResult['message'];
                                    if (isset($ssoResultData['verified']) && !$ssoResultData['verified']) {
                                        $tempMessage = "Verify your account";
                                        $verifyRequired = "1";
                                    }
                                }
                                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => $tempMessage, 'toast' => true, "data" => ['verifyRequired' => $verifyRequired, "username" => $ssoResultDataUsername, "phone_number" => $phone_number, "email" => $email_address, "country" => $country]]);
                            }
                        } else {
                            $login = true;
                            if (check2FA($user))
                                $login = false;

                            if ($login) {
                                $is_influencer = false;
                                if ($user->is_influencer == "1") {
                                    $is_influencer = true;
                                }
                                $authToken = generateAuthToken($user);
                                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User login successful', 'toast' => true], ["two_fa" => false, 'authToken' => $authToken, "username" => $user->username, 'email' => $user->email, 'role' => ["id" => $role['id'], "name" => $role['name'], "key" => $role['key'], 'is_influencer' => $is_influencer]]);
                            } else {
                                $userprofile = UserProfile::where('user_id', $user->id)->first();
                                return send2FAOTP($userprofile, $user);
                            }
                        }
                    } else {
                        if ($user_login_attempts) {
                            $today = Carbon::today()->toDateString();
                            $isUpdatedToday = $user_login_attempts->updated_at->toDateString() === $today;
                            if ($isUpdatedToday) {
                                $user_login_attempts->count = $user_login_attempts->count + 1;
                            } else {
                                $user_login_attempts->count = 1;
                            }
                        } else {
                            $user_login_attempts = new LoginAttempt();
                            $user_login_attempts->user_id = $user->id;
                            $user_login_attempts->count = 1;
                        }
                        $user_login_attempts->save();
                        $login_attemps_remaining = $max_login_attempts - $user_login_attempts->count;
                        $msg = "You have entered incorrect password";
                        if ($login_attemps_remaining < 3) {
                            $msg = "You have entered incorrect password, Attempts remaining: " . $login_attemps_remaining;
                            if ($login_attemps_remaining == 0) {
                                sendLoginFailActivity($user);
                                addNotification($user->id, $user->id, "Login attempt failed", "Login attempt failed", $user->id, "1", "#");
                                $msg = "You have entered incorrect password, Login Attempts exceeded for today";
                            }
                        }
                        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => $msg, 'toast' => true, "data" => ['verifyRequired' => "0", "username" => $user->username]]);
                    }
                } else {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "Max failed login attempts exceeded for today", 'toast' => true, 'data' => ['max_login_attempts' => $max_login_attempts, "users_max_login_attempts" => $users_max_login_attempts]]);
                }
                // } else {
                //     return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Please verify your account', 'toast' => true, "data" => ['verifyRequired' => "1","username" => $user->username]]);
                // }
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User not found', 'toast' => true, "data" => ['verifyRequired' => "0"]]);
            }
        } catch (\Exception $e) {
            Log::info('Login Error : ' . $e->getMessage() . " " . $e->getLine() . " " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true, "data" => ['verifyRequired' => "0"]]);
        }
    }
    public function isRegisteredUser(Request $request)
    {
        try {
            if (!isset($request->username)) {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Provide username', 'toast' => true]);
            }
            $username = $request->username;
            $userExists = checkUserIsRegistered($username);
            if ($userExists['status']) {
                $email_address = $userExists['email_address'];
                $user_country_array = $userExists['user_country_array'];
                $user_clean_phone = $userExists['user_clean_phone'];
                $resendEmailOtpRes = resendEmailOTP($username, $email_address);
                $resendSmsOtpRes = resendSMSOTP($username, $user_clean_phone, $user_country_array);

                return generateResponse(['type' => 'success', "status" => true, 'code' => 200, 'message' => 'User is registered', 'toast' => true], [...$userExists]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => $userExists['message'], 'toast' => true], [...$userExists]);
            }
        } catch (\Exception $e) {
            Log::info('isRegisteredUser Error : ' . $e->getMessage() . " @" . $e->getLine() . " \nin " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function verifyEmailOtp(VerifyEmailOtpRequest $request)
    {
        try {
            $username = $request->username;
            $otp = $request->otp;
            $email = $request->email;
            $verifyEmailOtpRes = verifyEmailOTP($username, $otp, $email);
            if ($verifyEmailOtpRes['status']) {
                $user = User::where('username', $username)->first();
                if ($user && $user->toArray()) {
                    $user->verify_email = "1";
                    $user->save();
                }
                return generateResponse(['type' => 'success', "status" => true, 'code' => 200, 'message' => $verifyEmailOtpRes['message'], 'toast' => true]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => $verifyEmailOtpRes['message'], 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('verifyEmailOtp Error : ' . $e->getMessage() . " @" . $e->getLine() . " \nin " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function verifySmsOtp(VerifySmsOtpRequest $request)
    {
        try {
            $username = $request->username;
            $otp = $request->otp;
            $verifySMSOtpRes = verifySMSOTP($username, $otp);
            if ($verifySMSOtpRes['status']) {
                $user = User::where('username', $username)->first();
                if ($user && $user->toArray()) {
                    $user->verify_email = "1";
                    $user->save();
                }
                return generateResponse(['type' => 'success', "status" => true, 'code' => 200, 'message' => $verifySMSOtpRes['message'], 'toast' => true]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => $verifySMSOtpRes['message'], 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('verifySmsOtp Error : ' . $e->getMessage() . " @" . $e->getLine() . " \nin " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function resendEmailOtp(ResendEmailOtpRequest $request)
    {
        try {
            $username = $request->username;
            $email = $request->email;
            $resendEmailOtpRes = resendEmailOTP($username, $email);
            if ($resendEmailOtpRes['status']) {
                return generateResponse(['type' => 'success', "status" => true, 'code' => 200, 'message' => $resendEmailOtpRes['message'], 'toast' => true]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => $resendEmailOtpRes['message'], 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('resendEmailOtp Error : ' . $e->getMessage() . " @" . $e->getLine() . " \nin " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function resendSmsOtp(ResendSMSOtpRequest $request)
    {
        try {
            $username = $request->username;
            $phone_number = $request->phone_number;
            $country = $request->country;
            $resendSmsOtpRes = resendSMSOTP($username, $phone_number, $country);
            if ($resendSmsOtpRes['status']) {
                return generateResponse(['type' => 'success', "status" => true, 'code' => 200, 'message' => $resendSmsOtpRes['message'], 'toast' => true]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => $resendSmsOtpRes['message'], 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('resendSmsOtp Error : ' . $e->getMessage() . " @" . $e->getLine() . " \nin " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function verifyLink(VerifyLinkRequest $request)
    {
        $verifyToken = $request->verify_token;
        $verifyCode = $request->verify_code;
        $verifyAccountResponse = verifyAccountToken($verifyToken, $verifyCode);
        if ($verifyAccountResponse['status']) {
            return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Link verified successfully', 'toast' => true]);
        } else {
            return generateResponse($verifyAccountResponse);
        }
    }
    public function verifyAccount(VerifyLinkRequest $request)
    {
        DB::beginTransaction();
        try {
            $verifyToken = $request->verify_token;
            $verifyCode = $request->verify_code;
            $verifyAccountResponse = verifyAccountToken($verifyToken, $verifyCode);

            if ($verifyAccountResponse['status']) {
                $userRow = $verifyAccountResponse['user'];
                // Check if the provided password matches the hashed password
                if (Hash::check($request->password, $userRow->password)) {
                    $userRow->verify_email = "1";
                    $userRow->verify_token = null;
                    $userRow->verify_code = null;
                    $userRow->save();

                    $authToken = generateAuthToken($userRow);
                    $role = $userRow->role->toArray();
                    DB::commit();
                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User verified successfully', 'toast' => true], ['authToken' => $authToken, "username" => $userRow->username, 'email' => $userRow->email, 'role' => ["id" => $role['id'], "name" => $role['name'], "key" => $role['key'],]]);
                } else {
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => "You have entered incorrect password", 'toast' => true]);
                }
            } else {
                return generateResponse($verifyAccountResponse);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Login Error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function sendforgotPasswordOTP(Request $request)
    {
        try {
            if (!isset($request->username)) {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Provide username', 'toast' => true]);
            }
            $username = $request->username;
            $forgotPasswordRes = sendForgotPasswordOTP($username);
            if ($forgotPasswordRes['status']) {
                return generateResponse(['type' => 'success', "status" => true, 'code' => 200, 'message' => $forgotPasswordRes['message'], 'toast' => true], [...$forgotPasswordRes]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => $forgotPasswordRes['message'], 'toast' => true]);
            }
        } catch (\Exception $e) {
            Log::info('forgotPassword Error : ' . $e->getMessage() . " @" . $e->getLine() . " \nin " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function resetForgotPassword(ResetForgotPasswordRequest $request)
    {
        try {
            DB::beginTransaction();
            $username = $request->username;
            $otp = $request->otp;
            $verifyForgotPasswordRes = verifyForgotPasswordOTP($username, $otp);
            if (!$verifyForgotPasswordRes['status']) {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => $verifyForgotPasswordRes['message'], 'toast' => true]);
            }
            $userId = $verifyForgotPasswordRes['user_id'];
            $confirmPassword = $newPassword = $request->password;
            $resetForgotPasswordRes = resetForgotPassword($userId, $newPassword, $confirmPassword);

            if ($resetForgotPasswordRes['status']) {
                $userRow = User::where('sso_user_id', $userId)->first();
                if ($userRow && $userRow->toArray()) {
                    $role = $userRow->role->toArray();
                    $authToken = generateAuthToken($userRow);
                    $userRow->password = hashText($newPassword);
                    $userRow->save();
                    DB::commit();
                    return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Pasword reset successfully', 'toast' => true], ['authToken' => $authToken, "username" => $userRow->username, 'email' => $userRow->email, 'role' => ["id" => $role['id'], "name" => $role['name'], "key" => $role['key']]]);
                }
                return generateResponse(['type' => 'success', "status" => true, 'code' => 200, 'message' => $resetForgotPasswordRes['message'], 'toast' => true]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => $resetForgotPasswordRes['message'], 'toast' => true]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('resetForgotPassword Error : ' . $e->getMessage() . " @" . $e->getLine() . " \nin " . $e->getFile());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
    public function forgotPassword(Request $request)
    {
        $user = $request->attributes->get('user');
        $verificationLogCount = VerificationLog::where('user_id', $user->id)
            ->where('verification_purpose', "2")
            ->whereDate('created_at', Carbon::today())
            ->count();
        $emailData['subject'] = "Forgot Password";
        $emailData['title'] = "Forgot Password ";
        $emailData['linkTitle'] = "Reset Password";
        $emailData['message'] = "You've requested to reset your password. Please follow the link below to reset your password.";
        $emailData['view'] = 'mail-templates.forgot-password';
        $emailData['verificationLogCount'] = $verificationLogCount;
        [$emailResponse, $additionalData] = sendVerificationEmail($user, 2, $emailData);
        if ($emailResponse['status']) {
            $user->forgot_verify = "1";
            $user->save();
        }
        return generateResponse($emailResponse, $additionalData);
    }
    public function resetPassword(ResetPasswordRequest $request)
    {
        DB::beginTransaction();
        try {
            $verifyToken = $request->verify_token;
            $verifyCode = $request->verify_code;
            $password = $request->password;

            $verifyAccountResponse = verifyAccountToken($verifyToken, $verifyCode);
            if ($verifyAccountResponse['status']) {
                $userRow = $verifyAccountResponse['user'];
                $userRow->password = Hash::make($password);
                $userRow->verify_token = null;
                $userRow->verify_code = null;
                $userRow->verify_email = "1";
                $userRow->forgot_verify = "0";
                $userRow->save();

                $authToken = generateAuthToken($userRow);
                $role = $userRow->role->toArray();
                DB::commit();
                return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'Pasword reset successfully', 'toast' => true], ['authToken' => $authToken, "username" => $userRow->username, 'email' => $userRow->email, 'role' => ["id" => $role['id'], "name" => $role['name'], "key" => $role['key'],]]);
            } else {
                return generateResponse($verifyAccountResponse);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Login Error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }

    public function resend2FAOtp(ResendLogin2FAOTP $request)
    {
        DB::beginTransaction();
        try {
            $user = User::where('username', $request->username)
                ->orWhere('email', $request->username)
                ->first();
            if ($user) {
                $userprofile = UserProfile::where('user_id', $user->id)->first();
                if ($userprofile && $userprofile->two_fact_auth != "0") {
                    if (($userprofile->two_fact_email_verified == "1" || $userprofile->two_fact_phone_verified == "1"))
                        return send2FAOTP($userprofile, $user);
                    else
                        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => '2FA contact is not verified', 'toast' => true, "data" => ['verifyRequired' => "0"]]);
                } else
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => '2FA is off for that username', 'toast' => true, "data" => ['verifyRequired' => "0"]]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User not found', 'toast' => true, "data" => ['verifyRequired' => "0"]]);
            }
        } catch (\Exception $e) {
            Log::info('Login Error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true, "data" => ['verifyRequired' => "0"]]);
        }
    }
    public function verifyLogin2FAOtp(VerifyLogin2FAOtpRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::where('username', $request->username)
                ->orWhere('email', $request->username)->with('profile')
                ->first();
            if ($user) {
                $userprofile = UserProfile::where('user_id', $user->id)->first();
                $otp = $request->otp;
                if ($userprofile && $userprofile->two_fact_auth != "0") {
                    $res = verify2FAOtp($otp, $userprofile, $user, "login");
                    if ($res['status']) {
                        $role = $user->role->toArray();
                        $authToken = generateAuthToken($user);
                        DB::commit();

                        $ssoTokenURL = config("app.sso_url") . "token_login/" . $authToken . "/cloud-user-interface";

                        $temp_profile_pic_url = (isset($user->profile->profile_image_path) && $user->profile->profile_image_path) ?
                            getFileTemporaryURL($user->profile->profile_image_path, 1440) : null;

                        $twoFaData = null;
                        if (isset($user->profile) && $user->profile) {
                            $twoFaData = get2FAData($user->profile, $user->email);
                        }
                        return generateResponse(['type' => 'success', 'code' => 200, 'status' => true, 'message' => 'User login successful', 'toast' => true], ["ssoTokenURL" => $ssoTokenURL, "twoFaData" => $twoFaData, 'profile_pic' => $temp_profile_pic_url, 'authToken' => $authToken, "username" => $user->username, 'email' => $user->email, 'role' => ["id" => $role['id'], "name" => $role['name'], "key" => $role['key'],]]);
                    } else {
                        return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => $res['message'], 'toast' => true, "data" => ['verifyRequired' => "0"]]);
                    }
                } else
                    return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => '2FA is off for that username', 'toast' => true, "data" => ['verifyRequired' => "0"]]);
            } else {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User not found', 'toast' => true, "data" => ['verifyRequired' => "0"]]);
            }
        } catch (\Exception $e) {
            Log::info('Login Error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true, "data" => ['verifyRequired' => "0"]]);
        }
    }
    public function resendLink(ResendLinkRequest $request)
    {
        $max_verify_email_requests = config("app.max_verify_email_requests");
        DB::beginTransaction();
        try {
            $username = $request->username;
            $user = User::where('username', $username)->orWhere('email', $username)->first();
            if (!$user)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User not found', 'toast' => true]);

            if ($user->verify_email)
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User is already verified', 'toast' => true]);
            $verificationLogCount = VerificationLog::where('user_id', $user->id)
                ->where('verification_purpose', "1")
                ->whereDate('created_at', Carbon::today())
                ->count();
            if ($verificationLogCount >= $max_verify_email_requests) {
                return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You have exceeded the maximum number of attempts for today', 'toast' => true]);
            }

            $emailData['subject'] = config("app.app_name") . "  account verification";
            $emailData['title'] = "Email verification";
            $emailData['linkTitle'] = "Verify Account";
            $emailData['message'] = "Please click the below link to verify your email address.";
            $emailData['view'] = 'mail-templates.register';
            $emailData['verificationLogCount'] = $verificationLogCount;
            [$emailResponse, $additionalData] = sendVerificationEmail($user, 1, $emailData);

            DB::commit();
            return generateResponse($emailResponse, $additionalData);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::info('Login Error : ' . $e->getMessage());
            return generateResponse(['type' => 'error', 'code' => 500, 'status' => false, 'message' => 'Error while processing', 'toast' => true]);
        }
    }
}
