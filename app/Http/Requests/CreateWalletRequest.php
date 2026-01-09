<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateWalletRequest extends FormRequest
{

    public function rules(): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'string', 'size:3', 'uppercase'],
        ];
    }

    public function messages(): array
    {
        return [
            'currency.uppercase' => 'Currency must be in uppercase (e.g., USD, EUR)',
        ];
    }
}
