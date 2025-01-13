<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTokenIsValid
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->header('AccessToken') !== env('API_ACCESS_KEY')) {
            abort(
                response()->json(
                    [
                        'api_status' => '401',
                        'message' => 'UnAuthorized API Access',
                    ],
                    401
                )
            );
        }
        return $next($request);
    }
}