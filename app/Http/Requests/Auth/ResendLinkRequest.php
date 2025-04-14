<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\RequestWrapper;

class ResendLinkRequest extends RequestWrapper
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
            'username' => ['required', 'string'],
        ];
    }
    public function messages()
    {
        return [
            'username.required' => 'The username field is required.',
        ];
    }
}
