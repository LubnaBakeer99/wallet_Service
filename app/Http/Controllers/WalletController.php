<?php

namespace App\Http\Controllers;

use App\Http\Requests\{CreateWalletRequest,DepositRequest,WithdrawRequest};
use App\Http\Requests\TransactionRequest;
use App\Http\Requests\TransactionHistoryRequest;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\ResponseTrait;
class WalletController extends Controller
{
    use ResponseTrait;
     public function __construct(
        private WalletService $walletService
    ) {}

    public function store(CreateWalletRequest $request): JsonResponse
    {
        $wallet = $this->walletService->createWallet($request->validated());
        return $this->apiResponse( true, $message='Wallet created successfully', $wallet, 201);
    }

    public function show(Wallet $wallet)
    {
         return $this->apiResponse( true, null, $wallet, 200);
    }


    public function index(Request $request): JsonResponse
    {
         $data = $this->walletService->index($request);
         return $this->apiResponse( true, null, $data, 200);

    }

    public function deposit(
        DepositRequest $request,
        Wallet $wallet
    ) {
        $key = $request->header('Idempotency-Key');
        $transaction =$this->walletService->deposit($wallet, $request->amount, $key);
        return $this->apiResponse( true, null, $transaction->load('wallet'), 200);
    }
    function withdraw(WithdrawRequest $request ,  Wallet $wallet){
       try {
            $idempotencyKey = $request->header('Idempotency-Key');
            $transaction = $this->walletService->withdraw(
                $wallet,
                $request->amount,
                $idempotencyKey
            );
            return $this->apiResponse( true,'Withdrawal successful', $transaction, 200);
            // Get updated balance
            $balance = $this->walletService->getBalance($transaction);
        } catch (\App\Exceptions\InsufficientBalanceException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Insufficient balance',
                'current_balance' => $wallet->balance,
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Withdrawal failed: ' . $e ,
            ], 500);
        }

    }


}
