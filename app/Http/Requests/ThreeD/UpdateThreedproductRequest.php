<?php

namespace App\Http\Requests\ThreeD;


use App\Http\Requests\RequestWrapper;

class UpdateThreedproductRequest extends RequestWrapper
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
            'model_name' => 'nullable|string|max:255',
            'price' => 'nullable|numeric',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'model_thumbnail' => 'nullable|file|mimes:jpeg,jpg,png,gif|max:10240',
        ];
    }
}
