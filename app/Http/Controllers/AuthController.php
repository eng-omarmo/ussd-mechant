<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    //
    use ApiResponse;

    function generateToken(Request $request)
    {
        $request->validate([
            'userMobileNo' => 'required',
            'dialogId' => 'nullable|string',
            'channelName' => 'nullable|string',
        ]);

        try {
            $token = JWT::encode($request->only('userMobileNo', 'dialogId', 'channelName'), config('services.ussd.appSecret'), 'HS256');
            return $this->Ok($token);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
