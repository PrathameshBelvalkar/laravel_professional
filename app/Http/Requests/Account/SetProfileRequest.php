<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\RequestWrapper;

class SetProfileRequest extends RequestWrapper
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function rules(): array
    {
        return [
            'whatsapp_number' => ['numeric'],
            'first_name' => ['max:255'],
            'last_name' => ['max:255'],
            'pin_code' => ["max:11"],
            'address_1' => ['max:255'],
            'about_me' => ['max:255'],
            'zip_code' => ['numeric'],
            'dob' => ['date', 'max:11'],
            'crop_file' => 'image|mimes:jpeg,jpg,png,bmp,gif,svg',
            'original_file' => 'image|mimes:jpeg,jpg,png,bmp,gif,svg',
        ];
    }
}
