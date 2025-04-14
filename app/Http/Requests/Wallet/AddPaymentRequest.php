<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class AddPaymentRequest extends RequestWrapper
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
            'purpose' => ['required', Rule::in(['1', '2', '3', '4'])],
            'mode' => ['required', Rule::in(['1', '2', '3', '4'])],
            'payment_response' => ['required'],
        ];
    }
    public function messages()
    {
        return [
            'purpose.in' => 'Invalid payment purpose.',
            'mode.in' => 'Invalid payment mode.',
        ];
    }
}
