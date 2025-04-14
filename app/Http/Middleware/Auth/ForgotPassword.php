<?php

namespace App\Http\Middleware\Auth;

use App\Models\VerificationLog;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;

class ForgotPassword
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $max_verify_email_requests = config("app.max_verify_email_requests");
        if ($request->username == null | $request->username == "") {
            return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Username is required', 'toast' => true]);
        } else {
            $user = User::where('username', $request->username)
                ->orWhere('email', $request->username)
                ->first();
            if ($user) {
                $verificationLogCount = VerificationLog::where('user_id', $user->id)
                    ->where('verification_purpose', 2)
                    ->whereDate('created_at', Carbon::today())
                    ->count();
                if ($verificationLogCount >= $max_verify_email_requests) {
                    return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'You have exceeded the maximum number of attempts for today', 'toast' => true]);
                } else {
                    $request->attributes->add(['user' => $user]);
                    return $next($request);
                }
            } else {
                return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'User not found with this username', 'toast' => true]);
            }
        }
    }
}
