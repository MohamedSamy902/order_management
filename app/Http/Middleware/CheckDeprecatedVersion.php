<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDeprecatedVersion
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $version = $request->attributes->get('api_version');
        $versionConfig = config("api.versions.{$version}");

        // Process request
        $response = $next($request);

        // Add deprecation headers if version is deprecated
        if ($versionConfig && $versionConfig['deprecated']) {
            if (config('api.deprecation_headers.deprecation')) {
                $response->header('Deprecation', 'true');
            }

            if (config('api.deprecation_headers.sunset') && $versionConfig['sunset_date']) {
                $response->header('Sunset', $versionConfig['sunset_date']);
            }

            if (config('api.deprecation_headers.link')) {
                $defaultVersion = config('api.default_version');
                $response->header('Link', "</api/{$defaultVersion}>; rel=\"successor-version\"");
            }

            // Add warning header
            $message = "This API version is deprecated.";
            if ($versionConfig['sunset_date']) {
                $message .= " It will be sunset on {$versionConfig['sunset_date']}.";
            }
            $response->header('Warning', "299 - \"{$message}\"");
        }

        return $response;
    }
}
