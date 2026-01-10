<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\{Wallet};
class TransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_wallet_id' => ['required', 'integer', 'exists:wallets,id'],
            'to_wallet_id' => ['required', 'integer', 'exists:wallets,id', 'different:from_wallet_id'],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999999.99',
            ],
            'description' => ['sometimes', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_wallet_id.required' => 'Source wallet ID is required',
            'from_wallet_id.exists' => 'Source wallet does not exist',
            'to_wallet_id.required' => 'Destination wallet ID is required',
            'to_wallet_id.exists' => 'Destination wallet does not exist',
            'to_wallet_id.different' => 'Cannot transfer to the same wallet',
            'amount.required' => 'Amount is required',
            'amount.min' => 'Minimum transfer amount is 0.01',
            'amount.max' => 'Maximum transfer amount is 999,999,999.99',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Check same currency (if required by business rules)
            if ($this->from_wallet_id && $this->to_wallet_id) {
                $fromWallet = Wallet::find($this->from_wallet_id);
                $toWallet = Wallet::find($this->to_wallet_id);

                if ($fromWallet && $toWallet && $fromWallet->currency !== $toWallet->currency) {
                    $validator->errors()->add(
                        'currency',
                        'Transfers are only allowed between wallets with the same currency'
                    );
                }
            }
        });
    }
}
