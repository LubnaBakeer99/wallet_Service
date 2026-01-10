<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $wallet = $this->wallet;
        $relatedWallet = $this->relatedWallet ;

         return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => $this->amount,
            'uuid' =>$this->uuid,
            'related_wallet' =>[
              'related_wallet_id'=>   ($relatedWallet)? $this->related_wallet_id: null,
               'owner_name'      => ($relatedWallet)? $this->owner_name: null,
        ],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
