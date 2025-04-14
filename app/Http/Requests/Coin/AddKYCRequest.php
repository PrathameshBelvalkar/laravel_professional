<?php

namespace App\Http\Requests\Coin;

use App\Rules\Uppercase;
use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class AddKYCRequest extends RequestWrapper
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
            'first_name' => ['string', 'min:3', 'max:50'],
            'last_name' => ['string', 'min:3', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:user_kyc',
                'regex:/^\w.+@[a-zA-Z_]+?\.[a-zA-Z]{2,3}$/',
            ],
            'phone_no' => ['required', 'unique:user_kyc', 'string', 'max:15'],
            'address_1' => ['required', 'string', 'max:255'],
            // 'address_2' => ['nullable', 'string', 'max:255'],
            // 'country' => 'nullable|string|exists:countries,phonecode',
            'city' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:10'],
            'dob' => ['required', 'date'],
            'doc_front' => [
                'required',
                'file',
                'mimes:jpg,png,jpeg',
                'max:10240',
            ],
            'doc_back' => [
                'required',
                'file',
                'mimes:jpg,png,jpeg',
                'max:10240',
            ],
        ];
    }

    public function messages()
    {
        return [
            'first_name.required' => 'The first name field is required.',
            'first_name.min' =>
                'The first name must be at least :min characters.',
            'first_name.max' => 'The username must not exceed :max characters.',
            'last_name.required' => 'The last name field is required.',
            'last_name.min' =>
                'The last name must be at least :min characters.',
            'last_name.max' => 'The last name must not exceed :max characters.',
            'zip_code.max' => 'The zipcode must not exceed :max numbers.',
            'phone_no.max' => 'The phone number must not exceed :max numbers.',
            'doc_front.required' => 'The front document image is required',
            'doc_back.required' => 'The back document image is required',
        ];
    }
}
