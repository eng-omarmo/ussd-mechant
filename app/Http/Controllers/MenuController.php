<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use App\Helpers\MenuUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class MenuController extends Controller
{

    use ApiResponse;

    /**
     * Get the menu data based on the provided path.
     *
     * @param Request $request
     * @param string $menuPath
     * @return \Illuminate\Http\JsonResponse
     */
    public function readMenu(Request $request, $menuPath)
    { 

        $data = [];
        $token = $request->bearerToken();

        if (!$token) {
            return $this->Unauthorized();
        }
      
        $payload = $request->all();

        $menuPath = $request->path();

        if (empty($menuPath)) {
            return $this->NotFound('path not found.');
        }
        $menuPath = str_replace('api', '', $menuPath);

        if ($request->method() == 'POST') {
            $token = $this->getToken($token, config('services.ussd.ussdSecret'));
   
            if ($token) {
                return $this->Unauthorized('forbidden');
            }
            $token = $this->getToken($token, config('services.ussd.appSecret'));

            if ($request->amount) {
                $user_id = $this->getUser($payload['userMobileNo']);
                $data = ['user_id' => $user_id, 'amount' => $request->amount];
                $data = array_merge($data, $payload);
            }

            if ($menuPath == '/merchant/customer/get-last-transaction') {

                return $this->getLastTransaction($data);
            }
            if ($menuPath == '/merchant/customer/make-payment') {
                return $this->makePayment($data);
            }
            if ($menuPath == '/merchant/customer/make-pay-bill') {
                return $this->makeBill($data);
            }
        }
        $menuData = MenuUtility::getUrlFromJson($menuPath);

        if (!isset($menuData)) {
            return $this->NotFound('No data found', 'Menu not found.');
        }

        $newToken = $this->generateToken($payload);

        return  $this->Ok($menuData)->header('Authorization', $newToken);
    }


    function makePayment($data)
    {
        try {

            $token = $this->generateToken($data);
            $walletBalance = $this->checkBalance($data);
            if (!$walletBalance) {
                return $this->error('insufficient balance');
            }

            $response = Http::withToken($token)->post(config('services.url.sendMoney'), $data)->json();
            if ($response->failed()) {
                return $this->error($response->json());
            }
            return $this->ok('Payment success', $response)->header('Authorization', $token);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }

        return $this->ok('Payment success');
    }

    function makeBill($data)
    {
        try {
            $walletBalance = $this->checkBalance($data);
            $token = $this->generateToken($data);
            if (!$walletBalance) {
                return $this->error('insufficient balance', $token);
            }

            $response = Http::withToken($token)->post(config('services.url.sendMoney'), [
                'userMobileNo' => $data['userMobileNo'],
                'amount' => $data['amount'],
                'description' => $data['description'] ?? 'no description',
            ])->json();

            if ($response->failed()) {
                return $this->error($response->json());
            }
            return $this->ok('success made a bill', $response);
        } catch (Throwable $th) {
            return $this->error($th->getMessage());
        }
    }

    function getLastTransaction($data)
    {
        try {
            if (!$data['user_id']) {
                return $this->error('user not found');
            }
            $transactions = DB::table('transactions')->where('user_id', $data['user_id'])->orderBy('id', 'desc')->first();

            $newToken = $this->generateToken($data);

            return $this->ok($transactions, 'last transaction found')->header('Authorization', $newToken);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    function checkBalance($data)
    {
        $wallet = Db::table('wallets')->where('user_id', $data['user_id'])->first();
        if ($wallet->balance < $data['amount']) {
            return false;
        }
        return true;
    }

    function getToken($token, $jwtSecret)
    {
        $decodedToken = JWT::decode($token, new Key($jwtSecret, 'HS256'));
        return $decodedToken;
    }

    function generateToken($payload)
    {
        $newToken = JWT::encode($payload, config('services.ussd.appSecret'), 'HS256');

        return $newToken;
    }


    function getUser($userMobileNo)
    {
        $user = DB::table('users')->where('formattedPhone', $userMobileNo)->first();
        return $user->id ?? null;
    }
}
