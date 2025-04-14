<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;
class VerifyLinkRequest extends RequestWrapper
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
            'verify_token' => ['required'],
            'verify_code' => ['required'],
            'verification_type' => ['required', Rule::in(['account', 'link'])], 
            'password' => ['required_if:verification_type,account'],
        ];
    }
    public function messages()
    {
        return [
            'verify_token.required' => 'The verify token is required.',
            'verify_code.required' => 'The verify code is required.',
            'verification_type.required' => 'The verification type is required.',
            'password.required_if' => 'The password is required when the verification type is account.',
        ];
    }
}
