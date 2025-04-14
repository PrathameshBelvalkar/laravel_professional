<?php

namespace App\Http\Middleware;

use Closure;

class CheckStorageLimit
{

    public function handle($request, Closure $next)
    {
        $user = $request->attributes->get('user');

        $files = $request->allFiles();
        $userStorageLimitExceeded = checkUserStorageLimit($files, $user);
        if ($userStorageLimitExceeded) {
            return generateResponse(['type' => 'error', 'code' => 200, 'status' => false, 'message' => 'storage limit exceeded, please upgrade your storage plan', 'toast' => true]);
        }
        return $next($request);
    }
}
