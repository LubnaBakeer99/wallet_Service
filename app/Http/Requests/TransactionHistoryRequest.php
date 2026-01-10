<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'in:deposit,withdraw,transfer_in,transfer_out,all'],
            'start_date' => ['sometimes', 'date', 'before_or_equal:end_date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Type must be one of: deposit, withdraw, transfer_in, transfer_out, all',
            'start_date.date' => 'Start date must be a valid date',
            'end_date.date' => 'End date must be a valid date',
            'start_date.before_or_equal' => 'Start date must be before or equal to end date',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'page.integer' => 'Page must be a number'
        ];
    }

    protected function prepareForValidation()
{
    // If no dates provided, default to last 30 days
    if (!$this->has('start_date') && !$this->has('end_date')) {
        $this->merge([
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
        ]);
    }

    // If only start date provided, default end date to today
    if ($this->has('start_date') && !$this->has('end_date')) {
        $this->merge(['end_date' => now()->format('Y-m-d')]);
    }

    // If only end date provided, default start date to 30 days before
    if ($this->has('end_date') && !$this->has('start_date')) {
        $this->merge(['start_date' => now()->subDays(30)->format('Y-m-d')]);
    }

    // Default pagination
    if (!$this->has('page')) {
        $this->merge(['page' => 1]);
    }

    if (!$this->has('per_page')) {
        $this->merge(['per_page' => 15]);
    }

    // Default type
    if (!$this->has('type')) {
        $this->merge(['type' => 'all']);
    }
}
}
