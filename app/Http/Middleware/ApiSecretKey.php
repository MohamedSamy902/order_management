<?php

namespace App\Http\Middleware;

use Closure;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;

class ApiSecretKey
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $secretKey = $request->header('X-SECRET-KEY') ?? $request->header('x-secret-key');

        if (!$secretKey || $secretKey !== config('app.api_secret_key')) {
            return ApiResponse::unauthorized('Invalid API key');
        }

        return $next($request);
    }
}
