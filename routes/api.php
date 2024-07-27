<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');




// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/generate-token', [AuthController::class, 'generateToken'])->name('generateToken');
});

// Menu routes
Route::prefix('merchant')->group(function () {
    Route::match(['get', 'post'], '/{menuPath}', [MenuController::class, 'readMenu'])->where('menuPath', '.*');
});
