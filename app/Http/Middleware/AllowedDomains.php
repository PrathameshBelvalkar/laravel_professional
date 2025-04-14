<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class AllowedDomains
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedDomains = Config::get('app.allowed_domains', []);

        if (in_array($request->ip(), $allowedDomains) || in_array($request->getHost(), $allowedDomains)) {
            // The request is from an allowed domain or IP
            return $next($request);
        } else {
            // The request is not from an allowed domain or IP
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }
}
