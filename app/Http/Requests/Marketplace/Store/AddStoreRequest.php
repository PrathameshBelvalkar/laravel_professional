<?php

namespace App\Http\Requests\Marketplace\Store;

use App\Http\Requests\RequestWrapper;
use Illuminate\Validation\Rule;

class AddStoreRequest extends RequestWrapper
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('marketplace_stores', 'name')->whereNull('deleted_at'),
            ],
            'product_type' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'qr_code_image' => ['nullable', 'file', 'mimes:jpg,png', 'max:10240'], // 10 MB max
            'qr_code_image_ext' => ['nullable', 'string', 'max:5'],
            'theme' => ['nullable', 'integer', 'in:1,2,3,4'],
            'thumbnail_path' => ['nullable', 'file', 'mimes:jpg,png,jpeg', 'max:10240'], // Updated for file upload
            'image_path' => ['nullable', 'file', 'mimes:jpg,png,jpeg', 'max:10240'], // Updated for file upload
            'banner_path' => ['nullable', 'file', 'mimes:jpg,png,jpeg', 'max:10240'], // Updated for file upload
            'store_logo' => ['nullable', 'file', 'mimes:jpg,png,jpeg', 'max:10240'],
            'is_disabled' => ['nullable', 'string', 'in:Y,N'],
            'category_id' => ['nullable', 'string', 'max:100'],
            'product_limit' => ['required', 'string', 'max:50'],
            'created_datetime' => ['nullable', 'date_format:Y-m-d H:i:s'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'The store name is required.',
            'name.unique' => 'A store with this name already exists.',
            'store.required' => 'The store name is required.',
            'store.unique' => 'A store with this name already exists.',
            'qr_code_image.mimes' => 'The QR code image must be a file of type: jpg, png.',
            'qr_code_image.max' => 'The QR code image may not be greater than 10 MB.',
            'thumbnail_path.mimes' => 'The thumbnail path must be a file of type: jpg, png, jpeg.',
            'thumbnail_path.max' => 'The thumbnail path may not be greater than 10 MB.',
            'image_path.mimes' => 'The image path must be a file of type: jpg, png, jpeg.',
            'image_path.max' => 'The image path may not be greater than 10 MB.',
            'banner_path.mimes' => 'The banner path must be a file of type: jpg, png, jpeg.',
            'banner_path.max' => 'The banner path may not be greater than 10 MB.',
            'is_disabled.in' => 'The is disabled field must be either Y or N.',
            'theme.in' => 'The theme must be one of the following values: 1, 2, 3, 4.',
        ];
    }
}
