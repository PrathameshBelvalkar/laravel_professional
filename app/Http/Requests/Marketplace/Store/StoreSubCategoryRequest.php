<?php

namespace App\Http\Requests\Marketplace\Store;

use App\Http\Requests\RequestWrapper;

class StoreSubCategoryRequest extends RequestWrapper
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
   
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'subCategoryName' => 'required|string|max:255',
            'parentCategoryId' => 'required|exists:marketplace_category,id|integer|min:0',
            'subCategoryThumbnail' => 'nullable|image|mimes:jpg,jpeg,png|max:15000',
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
            'subCategoryName.required' => 'Subcategory name is required.',
            'subCategoryName.string' => 'Subcategory name must be a string.',
            'subCategoryName.max' => 'Subcategory name cannot exceed 255 characters.',
            'parentCategoryId.required' => 'Parent category ID is required.',
            'parentCategoryId.exists' => 'The selected parent category ID is invalid.',
            'parentCategoryId.integer' => 'The parent category ID must be an integer.',
            'parentCategoryId.min' => 'The parent category ID must be at least 0.',
            'subCategoryThumbnail.image' => 'Subcategory thumbnail must be an image.',
            'subCategoryThumbnail.mimes' => 'Subcategory thumbnail must be a file of type: jpg, jpeg, png.',
            'subCategoryThumbnail.max' => 'Subcategory thumbnail size cannot exceed 15000 KB.',
        ];
    }


}
