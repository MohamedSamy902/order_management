<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiVersion
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $version): Response
    {
        // Validate version exists
        if (!config("api.versions.{$version}")) {
            return response()->json([
                'success' => false,
                'message' => "API version '{$version}' not found",
                'available_versions' => array_keys(config('api.versions')),
            ], 404);
        }

        // Set version in request attributes
        $request->attributes->set('api_version', $version);

        // Process request
        $response = $next($request);

        // Add version header to response
        return $response->header('X-API-Version', $version);
    }
}
