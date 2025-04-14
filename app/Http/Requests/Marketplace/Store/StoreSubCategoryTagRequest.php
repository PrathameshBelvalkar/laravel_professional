<?php

namespace App\Http\Requests\Marketplace\Store;

use App\Http\Requests\RequestWrapper;

class StoreSubCategoryTagRequest extends RequestWrapper
{
    /**
     * Determine if the user is authorized to make this request.
     *
  
     */
   

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'categoryId' => 'nullable|integer|exists:marketplace_category,id',
            'subCategoryId' => 'required|integer|exists:marketplace_sub_category,id',
            'tag' => 'nullable|string|max:500',
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
            'categoryId.exists' => 'The selected category ID is invalid.',
            'subCategoryId.required' => 'Subcategory ID is required.',
            'subCategoryId.exists' => 'The selected subcategory ID is invalid.',
            'tag.string' => 'Tag must be a string.',
            'tag.max' => 'Tag cannot exceed 500 characters.',
        ];
    }

   
}
