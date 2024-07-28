<?php

namespace App\Http\Middleware;


use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UssdMiddleware
{
    public function handle(Request $request, Closure $next, $isBegin = null)
    {
        $authorizationHeader = $request->header('Authorization');

        if (!$authorizationHeader || !str_starts_with($authorizationHeader, 'Bearer ')) {
            return response()->json(['replyMsg' => 'Service is not available currently, please try again later'], 401);
        }

        $token = str_replace('Bearer ', '', $authorizationHeader);

        try {

            $jwtSecret = $isBegin ? config('services.ussd.appSecret') : config('services.ussd.ussdSecret');
            $tokenData = JWT::decode($token, new Key($jwtSecret, 'HS256'));
            $request->merge((array) $tokenData);
        } catch (\Exception $e) {
            return response()->json(['replyMsg' => 'Service is not available currently, please try again later', 'error' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}
