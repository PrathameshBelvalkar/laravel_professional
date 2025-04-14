<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class Verify2FAOTP extends RequestWrapper
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
            'otp' => ['required', "numeric"],
        ];
    }
    public function messages()
    {
        return [
            'otp.required' => 'The OTP field is required.',
            'otp.numeric' => 'The OTP field should be numeric.',
        ];
    }
}
