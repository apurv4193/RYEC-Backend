<?php

namespace App\Http\Middleware;

use Closure;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class VerifyJWTToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
           $user = JWTAuth::parseToken()->authenticate();
           if (!$user) {
                $outputArray['status'] = 0;
                $outputArray['message'] = 'Failed to validating token.';
                $statusCode = 404;
                return response()->json($outputArray, $statusCode);
           }
        } catch (JWTException $e) {
            if ($e instanceof \Tymon\JWTAuth\Exceptions\TokenExpiredException) 
            {
                $outputArray['status'] = 0;
                $outputArray['message'] = 'Token Expired.';
                $statusCode = $e->getStatusCode();
                return response()->json($outputArray, $statusCode);
            } 
            elseif ($e instanceof \Tymon\JWTAuth\Exceptions\TokenInvalidException) 
            {
                $outputArray['status'] = 0;
                $outputArray['message'] = 'Invalid Token.';
                $statusCode = $e->getStatusCode();
                return response()->json($outputArray, $statusCode);
            } 
            else 
            {
                $outputArray['status'] = 0;
                $outputArray['message'] = 'Token is required.';
                $statusCode = $e->getStatusCode();
                return response()->json($outputArray, $statusCode);
            }
        }
        return $next($request);
    }
}
