<?php

namespace App\Http\Requests\Clients;

use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'mobile' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:1000'],
            'currency' => ['required', 'string', 'size:3', 'exists:currencies,code'],
            'status' => ['sometimes', 'in:'.Client::STATUS_ACTIVE.','.Client::STATUS_ARCHIVED],
        ];
    }
}
