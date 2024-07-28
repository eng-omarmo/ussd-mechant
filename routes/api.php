<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\UssdMenuController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/generate-token', [AuthController::class, 'generateToken'])->name('generateToken');
});


// Route::prefix('merchant')->group(function () {
//     Route::match(['get', 'post'], '/{menuPath}', [MenuController::class, 'readMenu'])
//         ->where('menuPath', '.*')
//         ->middleware('auth.ussd:'); 
// });



Route::prefix('merchant')->group(function () {
    
    route::prefix('customer')->group(function () {
        Route::get('/', [UssdMenuController::class, 'index']);
        Route::get('/pay', [UssdMenuController::class, 'payMenu']);
        Route::get('/pay-bill', [UssdMenuController::class, 'billMenu']);
        Route::get('/customer-care', [UssdMenuController::class, 'customerCareMenu']);
        Route::get('/last-transaction', [UssdMenuController::class, 'getLastTransaction']);
        Route::post('/make-payment', [UssdMenuController::class, 'makePayment'])->middleware('auth.ussd:isBegin=true');
        Route::post('/get-last-transaction', [UssdMenuController::class, 'getLastTransactionDetails'])->middleware('auth.ussd:isBegin=true');

    });


});
