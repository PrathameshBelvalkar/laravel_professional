<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class Request2FAOTP extends RequestWrapper
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
            'two_fact_auth' => ['required', Rule::in(['0', '1', '2', '3'])],
            'two_fact_email' => ['required_if:two_fact_auth,1,3', 'email', 'max:255', 'regex:/^\w.+@[a-zA-Z_]+?\.[a-zA-Z]{2,3}$/'],
            'two_fact_phone' => ['required_if:two_fact_auth,2,3', 'numeric'],
            'phonecode' => ['required_if:two_fact_auth,2,3', 'numeric'],
        ];
    }
    public function messages()
    {
        return [
            'two_fact_auth.required' => 'The 2FA field is required.',
            'two_fact_auth.in' => 'Invalid auth type.',
            'two_fact_email.required_if' => 'The email field is required for auth type 1,3.',
            'two_fact_email.email' => 'Invalid email format.',
            'two_fact_email.max' => 'The email must not exceed :max characters.',
            'two_fact_phone.required_if' => 'The phone field is required for auth type 2,3.',
            'country_id.required_if' => 'The country_id field is required for auth type 2,3.',
        ];
    }
}
