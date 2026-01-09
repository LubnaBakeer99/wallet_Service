<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->enum('type', [
                'deposit',
                'withdraw',
                'transfer_in',
                'transfer_out'
            ]);
            $table->decimal('amount', 18, 2);
            $table->string('idempotency_key');
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->timestamps();
            $table->unique(['wallet_id', 'idempotency_key']);
            // For transfers
            $table->foreignId('related_wallet_id')->nullable()->constrained('wallets');

            // Indexes for performance
            $table->index(['wallet_id']);
            $table->index('idempotency_key');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
