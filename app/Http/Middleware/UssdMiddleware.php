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
        $jwtSecret = $isBegin ? config('services.ussd.appSecret') : config('services.ussd.ussdSecret'); 

        try {
            $tokenData = JWT::decode($token, new Key($jwtSecret, 'HS256'));
            $request->merge((array) $tokenData);

            // Generate a new token for subsequent requests if this is the initial request
            if ($isBegin) {
                $privateSecret = env('ussdSecret');
                $newPayload = [
                    'userMobileNo' => $tokenData->userMobileNo,
                    'dialogId' => $tokenData->dialogId,
                    'channelName' => $tokenData->channelName,
                    'timeStamp' => time(),
                    'exp' => time() + 3600 
                ];
                $newToken = JWT::encode($newPayload, $privateSecret, 'HS256');
                Log::info('New token generated: ' . $newToken);
                $request->headers->set('Authorization', 'Bearer ' . $newToken);
            }
        } catch (\Exception $e) {
            return response()->json(['replyMsg' => 'Service is not available currently, please try again later', 'error' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}