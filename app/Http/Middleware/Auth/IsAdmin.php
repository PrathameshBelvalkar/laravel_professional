<?php

namespace App\Http\Middleware\Auth;


use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next): Response
  {
    if ($request->hasHeader('authToken')) {
      $authToken = $request->header('authToken');
      try {
        $decoded = JWT::decode($authToken, new Key(config('app.enc_key'), 'HS256'));
      } catch (\Exception $e) {
        return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Token is invalid or broken', "redirect" => true, 'toast' => true]);
      }
      $email = $decoded->email;
      $username = $decoded->username;
      $cloudUserId = $decoded->cloudUserId;
      $user = User::where('email', $email)
        ->where('username', $username)
        ->where('id', $cloudUserId)
        ->first();
      if ($user && ($user->role_id == "2" || $user->role_id == "1" || $user->role_id == "6")) {
        $request->attributes->add(['user' => $user]);
        return $next($request);
      } else {
        return response()->json(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'Unauthorized access', 'toast' => true]);
      }
    }
    return response()->json(['type' => 'error', 'code' => 401, 'status' => false, 'message' => 'Unauthorized', "redirect" => true, 'toast' => true]);
  }
}
