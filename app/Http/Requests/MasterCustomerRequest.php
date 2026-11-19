<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Support\Database\MasterDataUniqueFields;
use Illuminate\Validation\Rule;

class MasterCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Extended authorize to allow power_user role in addition to admin,
        // matching the route-level permission granted in a previous session.
        $user = auth()->user();
        return $user && ($user->isAdmin() || $user->canManageMasterData());
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
                // Rule::unique('mysql.master_customers', 'company_name')->ignore($ignoreId),
                //  Shared registry rule (app-level rule; DB has only a non-unique index on company_name).
                MasterDataUniqueFields::rule('company_name', $ignoreId),
            ],
            // Added unique rule for tax_id — it has a UNIQUE constraint in the DB
            // but was previously only validated as nullable string, causing a 500 on duplicate.
            'tax_id' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                // Rule::unique('mysql.master_customers', 'tax_id')->ignore($ignoreId)->whereNotNull('tax_id'),
                // Shared registry rule for the DB UNIQUE key master_customers_tax_id_unique.
                MasterDataUniqueFields::rule('tax_id', $ignoreId)->whereNotNull('tax_id'),
            ],
            'is_enable' => 'sometimes|required|in:0,1',
            // Used by syncNames only
            'master_customer_id' => 'sometimes|required|exists:mysql.master_customers,id',
        ];
    }

    // User-friendly duplicate messages shown under the Company Name / Tax ID inputs.
    public function messages(): array
    {
        return MasterDataUniqueFields::messages();
    }
}
