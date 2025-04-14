<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MStoreCategoryRequest extends FormRequest
{
    public function authorize()
    {
        // Allow all users to make this request, change as needed
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'image_path' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Ensure the image is valid
        ];
    }
}
