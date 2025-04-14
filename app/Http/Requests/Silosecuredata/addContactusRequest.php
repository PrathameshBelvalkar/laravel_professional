<?php

namespace App\Http\Requests\Silosecuredata;

use App\Rules\Uppercase;
use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class addContactusRequest extends RequestWrapper
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
            'first_name' => ['string', 'min:3', 'max:255'],
            'last_name' => ['string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users', 'regex:/^\w.+@[a-zA-Z_]+?\.[a-zA-Z]{2,3}$/'],
            'phone' => ['required', 'numeric'],
            'services' => ['required', 'string'],
            'note' => ['required', 'string','max:500'],
        ];
    }
}
