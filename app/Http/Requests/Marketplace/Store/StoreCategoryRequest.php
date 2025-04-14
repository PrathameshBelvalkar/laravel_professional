<?php

namespace App\Http\Requests\Marketplace\Store;

use App\Http\Requests\RequestWrapper;

class StoreCategoryRequest extends RequestWrapper
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'categoryName' => 'required|string|max:255',
            'categoryThumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:150000',
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'categoryName.required' => 'Category name is required.',
            'categoryName.string' => 'Category name must be a string.',
            'categoryName.max' => 'Category name cannot exceed 255 characters.',
            'categoryThumbnail.image' => 'Category thumbnail must be an image.',
            'categoryThumbnail.mimes' => 'Category thumbnail must be a file of type: jpg, jpeg, png.',
            'categoryThumbnail.max' => 'Category thumbnail size cannot exceed 150000 KB.',
        ];
    }

   
}
