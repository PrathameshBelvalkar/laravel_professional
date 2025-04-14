<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertNullStringsToNull
{
    public function handle($request, Closure $next)
    {
        $requestData = $request->all();

        foreach ($requestData as $key => $value) {
            if ($value === 'null') {
                $requestData[$key] = null;
            }
        }

        $request->merge($requestData);

        return $next($request);
    }
}
