<?php

namespace App\Http\Requests\ThreeD;


use App\Http\Requests\RequestWrapper;


class AddThreedproductRequest extends RequestWrapper
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
            'category_id' => 'required|exists:three_d_categories,id',
            'model_name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'model_thumbnail' => 'required|file|mimes:jpeg,jpg,png,gif|max:10240',

        ];
    }
}
