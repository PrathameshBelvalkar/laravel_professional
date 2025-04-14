<?php

namespace App\Http\Requests\Wallet;

use App\Http\Requests\RequestWrapper;


class BuyRequest extends RequestWrapper
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_id' => ['required', 'numeric'],
        ];
    }
    public function messages()
    {
        return [
            'payment_id.required' => 'The payment id field is required.',
            'payment_id.numeric' => 'The payment id must be numeric.'
        ];
    }
}
