<?php

namespace App\Http\Middleware\Auth;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ResetPassword
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $verifyToken = $request->verify_token;
        $verifyCode = $request->verify_code;
        $password = $request->password;
        $confirmPassword = $request->password_confirmation;

        if ($verifyToken != null && $verifyCode != null && $verifyToken != "" && $verifyCode != "") {
            if ($password != $confirmPassword) {
                return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Password and confirm password not match', 'toast' => true]);
            } else {
                return $next($request);
            }

        } else {
            return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Token is invalid or broken', 'toast' => true]);
        }

    }
}
