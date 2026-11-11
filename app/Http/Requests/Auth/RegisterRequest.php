<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

// RegisterRequest validates registration input with dynamic user table resolution
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        $instance = new $userModel();
        $table = $instance->getTable();
        $connection = $instance->getConnectionName();
        $tableRef = $connection ? "{$connection}.{$table}" : $table;

        return [
            'username' => "required|string|max:190|unique:{$tableRef},username",
            'email' => "required|string|email|max:255|unique:{$tableRef},email",
            'name' => 'nullable|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'This username is already taken.',
            'email.unique' => 'This email address is already registered.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
        ];
    }
}

