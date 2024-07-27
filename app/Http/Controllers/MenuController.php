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
use phpDocumentor\Reflection\Types\This;

use function Pest\Laravel\json;

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
        $payload = $this->getToken($request);

        $menuPath = $request->path();

        if (empty($menuPath)) {
            return $this->NotFound('path not found.');
        }
        $menuPath = str_replace('api', '', $menuPath);

        if ($request->amount) {
            $data = [
                'user_id' => $this->getUser($payload->userMobileNo),
                'amount' => $request->amount,
                'channelName' => $payload->channelName,
                'dialogId' => $payload->dialogId
            ];
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

        $menuData = MenuUtility::getUrlFromJson($menuPath);

        if (!isset($menuData)) {
            return $this->NotFound('No data found', 'Menu not found.');
        }

        $newToken = $this->generateToken($request, $payload);

        return  $this->Ok($menuData)->header('Authorization', $newToken);
    }


    function makePayment($data)
    {
        try {
            $walletBalance = $this->checkBalance($data);
            if (!$walletBalance) {
                return $this->error('insufficient balance');
            }
            $token = $this->getToken($data);
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
            $token = $this->getToken($data);
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

            $newPayloadObject = (object) $data;
            $newToken = $this->generateToken($newPayloadObject);

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

    function getToken($request)
    {
        $authorizationHeader = $request->header('Authorization');
        if (!$authorizationHeader || !str_starts_with($authorizationHeader, 'Bearer')) {
            return $this->Unauthorized('Authorization token is missing or invalid.');
        }
        $token = str_replace('Bearer ', '', $authorizationHeader);
        $decodedToken = JWT::decode($token, new Key(config('services.ussd.appSecret'), 'HS256'));

        return $decodedToken;
    }

    function generateToken($payload)
    {
        $newPayload = [
            'userMobileNo' => $this->getPhone($payload->user_id),
            'dialogId' => $payload->dialogId,
            'channelName' => $payload->channelName,
            'timeStamp' => time(),
            'exp' => time() + 3600
        ];
        $newToken = JWT::encode($newPayload, config('services.ussd.appSecret'), 'HS256');
        return $newToken;
    }


    function getUser($userMobileNo)
    {
        $user = DB::table('users')->where('formattedPhone', $userMobileNo)->first();
        return $user->id ?? null;
    }
    function getPhone($userId)
    {
        $user = DB::table('users')->where('id', $userId)->first();
        return $user->formattedPhone ?? null;
    }
}
