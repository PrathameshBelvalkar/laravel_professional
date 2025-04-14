<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\RequestWrapper;


class AddPackageRequest extends RequestWrapper
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
            'name' => ['required','string'],
            'key' => ['required','numeric'],
        ];
    }
    public function messages()
    {
        return [
            'name.required' => 'Name is required',
            'key.required' => 'Key is required',
            'key.numeric' => 'Key must be a number',
        ];
    }
}
