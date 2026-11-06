<?php

namespace App\Http\Requests;

// Gemini : GAP-10 — Form request validating MasterCustomer updates and sync actions
use Illuminate\Foundation\Http\FormRequest;

class MasterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasRole('administrator'));
    }

    public function rules(): array
    {
        return [
            'company_name'       => 'sometimes|required|string|max:255',
            'tax_id'             => 'sometimes|nullable|string|max:50',
            'is_enable'          => 'sometimes|required|in:0,1',
            'master_customer_id' => 'sometimes|required|exists:mysql.master_customers,id',
        ];
    }
}

