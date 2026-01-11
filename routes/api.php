<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{WalletController,TransferController};
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('wallets/')->controller(WalletController::class)->group(function () {
    Route::post('/', 'store');
    Route::get('/{wallet}', 'show');
    Route::get('/', 'index');
    Route::get('/{wallet}/transactions', 'getTransactionHistory');
    Route::get('/{wallet}/balance' ,'showBalamce');
    Route::post('/{wallet}/deposit','deposit')->middleware('check-idempotency');
    Route::post('/{wallet}/withdraw','withdraw')->middleware('check-idempotency');

});


Route::prefix('transfer')->middleware('check-idempotency')->group(function () {
Route::post('/', [TransferController::class, 'transfer']);
});
