<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasRole('administrator'));
    }

    public function rules(): array
    {
        // id from route for unique-ignore on update, null on create
        $ignoreId = $this->route('id');

        return [
            'company_name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('mysql.master_customers', 'company_name')->ignore($ignoreId),
            ],
            'tax_id' => 'sometimes|nullable|string|max:50',
            'is_enable' => 'sometimes|required|in:0,1',
            // Used by syncNames only
            'master_customer_id' => 'sometimes|required|exists:mysql.master_customers,id',
        ];
    }
}
