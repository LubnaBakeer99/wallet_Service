<?php

namespace App\Services;

use App\Models\{Wallet,Transaction};
use App\Exceptions\InsufficientBalanceException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Enums\TransactionTypes;
class WalletService
{
    /**
     * Create a new wallet
     */
    public function createWallet(array $data): Wallet
    {
        return Wallet::create([
            'owner_name' => $data['owner_name'],
            'currency' => strtoupper($data['currency']),
            'balance' => 0
        ]);
    }

    public function getWallet(int $walletId): Wallet
    {
        return Wallet::findOrFail($walletId);
    }
    function index($request){
        $query = Wallet::query();
        if ($request->has('owner_name')) {
            $query->byOwner($request->owner_name);
        }

        if ($request->has('currency')) {
            $query->byCurrency($request->currency);
        }
        $perPage = $request->get('per_page', 15);
        $wallets = $query->paginate($perPage);
        return [ 'data' => $wallets->items(),
                'meta' => [
                'total' => $wallets->total(),
                'per_page' => $wallets->perPage(),
                'current_page' => $wallets->currentPage(),
                'last_page' => $wallets->lastPage(),
            ]];
    }

    function deposit(Wallet $wallet,  $amount,  $idempotencyKey)  {
        return DB::transaction(function () use ($wallet, $amount, $idempotencyKey) {

            //1- Lock wallet row to prevent race conditions
            $lockedWallet = Wallet::where('id', $wallet->id)
                ->lockForUpdate()
                ->first();
            //2- Idempotency check
            $existing = Transaction::where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                    return $existing;
            }
            //3- Add funds
            $balanceBefore = $lockedWallet->balance;
            $lockedWallet->increment('balance', $amount);
            $lockedWallet->refresh();
            //4- Record transaction
            return Transaction::create([
                'wallet_id' => $lockedWallet->id,
                'type' => TransactionTypes::DEPOSIT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $lockedWallet->balance,
                'idempotency_key' => $idempotencyKey,
            ]);
        });

    }

    public function withdraw(Wallet $wallet, $amount, $key){
        return DB::transaction(function () use ($wallet, $amount, $key) {
        //1- Lock wallet row to prevent race conditions
        $lockedWallet = Wallet::whereKey($wallet->id)
            ->lockForUpdate()
            ->firstOrFail();

        //2- Check if this withdrawal was already processed (idempotency)
        $existingTransaction = Transaction::where('wallet_id', $wallet->id)
            ->where('idempotency_key', $key)
            ->first();
        if ($existingTransaction) {
             return $existingTransaction; // Idempotent retry → return the same transaction
        }
        //3- Ensure sufficient balance
        if ($wallet->balance < $amount) {
             throw new InsufficientBalanceException();
        }
        //4- Apply balance change
        $balanceBefore = $lockedWallet->balance;
        $lockedWallet->decrement('balance', $amount);
        $lockedWallet->refresh();
        //5- Create and return the transaction
        return Transaction::create([
            'wallet_id'        => $lockedWallet->id,
            'type'             => TransactionTypes::WITHDRAW,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $lockedWallet->balance,
            'idempotency_key' => $key,
        ]);
    });
    }

    public function transfer(
        $fromWalletId,
        $toWalletId,
        $amount,
        $key){
        if ($fromWalletId === $toWalletId) {
            throw new \Exception('Self-transfer is not allowed');
        }
        return DB::transaction(function () use (
            $fromWalletId,
            $toWalletId,
            $amount,
            $key
        ) {

            // 1- Check idempotency (same key = same transfer)
            $existing = Transaction::where('idempotency_key', $key)->get();
            if ($existing->isNotEmpty()) {
                return $existing->all();
            }

            // 2- Lock wallets in consistent order (avoid deadlocks)
            $wallets = Wallet::whereIn('id', [$fromWalletId, $toWalletId])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $fromWallet = $wallets[$fromWalletId];
            $toWallet   = $wallets[$toWalletId];

            //3- Balance check
            if ($fromWallet->balance < $amount) {
                throw  new InsufficientBalanceException();
            }
            //4- Check same currency
            if ($fromWallet->currency !== $toWallet->currency) {
                throw new \Exception('Wallets must have the same currency');
            }
            // Get balances before
            $fromBalanceBefore = $fromWallet->balance;
            $toBalanceBefore = $toWallet->balance;
            //5- Apply balance changes
            $fromWallet->decrement('balance', $amount);
            $toWallet->increment('balance', $amount);

            // Get balances after
            $fromBalanceAfter = $fromWallet->fresh()->balance;
            $toBalanceAfter = $toWallet->fresh()->balance;

            //6- Create transactions
            $out = Transaction::create([
                'wallet_id'       => $fromWallet->id,
                'type'            => TransactionTypes::TRANSFER_OUT,
                'amount'          => $amount,
                'idempotency_key' => $key,
                'balance_before' => $fromBalanceBefore,
                'balance_after' => $fromBalanceAfter,
                'related_wallet_id' => $toWallet->id,
            ]);

            $in = Transaction::create([
                'wallet_id'       => $toWallet->id,
                'type'            => TransactionTypes::TRANSFER_IN,
                'amount'          => $amount,
                'idempotency_key' => $key,
                'balance_before' => $toBalanceBefore,
                'balance_after' => $toBalanceAfter,
                'related_wallet_id' => $fromWallet->id,
            ]);
            return [$out, $in];
        });
    }

      public function getTransactionHistory(Wallet $wallet, array $filters = []){
        // Start with wallet's transactions, newest first
        $query = $wallet->transactions()->with('relatedWallet')->latest('created_at');

        // Apply type filter
        if (isset($filters['type']) && $filters['type'] !== 'all') {
            $query->Type($filters['type']);
        }

        // Apply date range filter
        if (isset($filters['start_date'])) {
            $from = Carbon::parse($filters['start_date'])->startOfDay();
            $from = Carbon::parse($filters['end_date'])->startOfDay();
            //$query->where('created_at', '>=', $from);
             $query->BetweenDates( $filters['start_date'],$filters['end_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        // Apply pagination
        $perPage = $filters['per_page'] ?? 15;
        $page = $filters['page'] ?? 1;

        return $query->paginate($perPage, ['*'], 'page', $page);
    }



}
