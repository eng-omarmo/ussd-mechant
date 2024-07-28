<?php

namespace App\Http\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Traits\ApiResponse;
use App\Helpers\MenuUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class UssdMenuController extends Controller
{
    use ApiResponse;



    public function index(Request $request)
    {
        try {
            $menuData = MenuUtility::getUrlFromJson('/merchant/customer');

            if (!isset($menuData)) {
                return $this->NotFound('No data found', 'Menu not found.');
            }
            $token = $this->generateToken($request->only('userMobileNo', 'dialogId', 'channelName'));

            return  $this->success($menuData)->header('Authorization', $token);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function payMenu(Request $request)
    {
        try {
            $menuData = MenuUtility::getUrlFromJson('/merchant/customer/pay');

            if (!isset($menuData)) {
                return $this->NotFound('No data found', 'Menu not found.');
            }
            $token = $this->generateToken($request->only('userMobileNo', 'dialogId', 'channelName'));


            return  $this->success($menuData)->header('Authorization', $token);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    public function billMenu(Request $request)
    {
        try {
            $menuData = MenuUtility::getUrlFromJson('/merchant/customer/pay-bill');

            if (!isset($menuData)) {
                return $this->NotFound('No data found', 'Menu not found.');
            }
            $token = $this->generateToken($request->only('userMobileNo', 'dialogId', 'channelName'));

            return  $this->success($menuData)->header('Authorization', $token);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    function getLastTransaction(Request $request)
    {
        try {
            $menuData = MenuUtility::getUrlFromJson('/merchant/customer/last-transaction');

            if (!isset($menuData)) {
                return $this->NotFound('No data found', 'Menu not found.');
            }

            $token = $this->generateToken($request->only('userMobileNo', 'dialogId', 'channelName'));

            return  $this->success($menuData)->header('Authorization', $token);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    function customerCareMenu(Request $request)
    {
        try {
            $menuData = MenuUtility::getUrlFromJson('/merchant/customer/customer-care');
            if (!isset($menuData)) {
                return $this->NotFound('No data found', 'Menu not found.');
            }
            $token = $this->generateToken($request->only('userMobileNo', 'dialogId', 'channelName'));

            return  $this->success($menuData)->header('Authorization', $token);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    function getToken($token, $jwtSecret)
    {
        try {
            $tokenData = JWT::decode($token, new Key($jwtSecret, 'HS256'));
            return $tokenData;
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function getLastTransactionDetails(Request $request)
    {

        $userId = $this->getUser($request->userMobileNo);
        if (!$userId) {
            return $this->error('user not found');
        }

        $transactions = DB::table('transactions')->where('user_id', $userId)->orderBy('id', 'desc')->first();
        $token = $this->generateToken($request->only('userMobileNo', 'dialogId', 'channelName'));
        return  $this->Ok('success', $transactions)->header('Authorization', $token);
    }

    public function makePayment(Request $request)
    {
        try {
            if (!$request->amount) {
                return $this->error('Amount not Provided');
            }

            $userId = $this->getUser($request->userMobileNo);

            if (!$userId) {
                return $this->error('user not found');
            }
            $wallet = Db::table('wallets')->where('user_id', $userId)->first();
            if (!$wallet || $wallet->balance < $request->amount) {
                return $this->error('Insufficient Balance');
            }
            $resposnse = Http::withToken($request->bearerToken())->get(config('services.url.sendMoney'))->json();
            if ($resposnse->failed()) {
                return $this->error($resposnse->message);
            }
            return $this->Ok($resposnse->message)->header('Authorization', $request->bearerToken());
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }


    function getUser($userMobileNo)
    {
        $user = DB::table('users')->where('formattedPhone', $userMobileNo)->first();
        return $user->id ?? null;
    }

    function generateToken($data)
    {

        $token = JWT::encode($data, config('services.ussd.appSecret'), 'HS256');
        return $token;
    }
}
