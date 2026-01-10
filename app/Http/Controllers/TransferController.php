<?php

namespace App\Http\Controllers;

use App\Http\Requests\{TransferRequest};
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;

class TransferController extends Controller
{
    use ResponseTrait;
    public function __construct(
        private WalletService $walletService
    ) {}

    public function transfer(TransferRequest $request) {
        try {
        $key = $request->header('Idempotency-Key');
        $transactions = $this->walletService->transfer(
            $request->from_wallet_id,
            $request->to_wallet_id,
            $request->amount,
            $key
        );
       return $this->apiResponse( true, "Transfer Has been done successfully", $transactions, 200);
    }catch (\App\Exceptions\InsufficientBalanceException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Insufficient balance',
                'message' => $e->getMessage(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Transfer failed',
                'message' => $e,
            ], 500);
        }
    }
}
