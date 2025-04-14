<?php

namespace App\Http\Middleware\Auth;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Captcha
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secretKey = config('app.captcha_secret_key');

        if ((isset($request->g_recaptcha_response) && !empty($request->g_recaptcha_response)) || (!empty($request->env) && $request->env=="sandbox")) {
                if(empty($request->env)){

                    // Verify the reCAPTCHA API response 
                    $verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secretKey . '&response=' . $request->g_recaptcha_response);
                        
                    $responseData = json_decode($verifyResponse);
                    $captchaVerification = ($responseData->success)? 'true':'false';
                }else if(!empty($request->env) && $request->env=="sandbox"){
                    $captchaVerification = 'true';
                }

            // If the reCAPTCHA API response is valid 
            if ($captchaVerification) {
                return $next($request);
            } else {
                return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Captcha is not valid', 'toast' => true, "response" => $responseData]);
            }
        } else {
            return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Captcha is required', 'toast' => true]);
        }

    }
}
