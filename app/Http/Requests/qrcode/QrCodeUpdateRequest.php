<?php

namespace App\Http\Requests\qrcode;

use App\Http\Requests\RequestWrapper;

class QrCodeUpdateRequest extends RequestWrapper
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
            "files" => ['required', 'file', 'mimes:jpg,png', 'max:10240'],
            "qrcode_img_name" => ['string', 'min:1', 'max:255'],
            "qrcode_data" => ['json'],       
        ];
    }

    public function messages()
    {
        return [
            'files.required' => 'The qrcode img is not generated is required.',
            'qrcode_img_name.min' => 'The qr-code image name must be at least :min characters.',
            'qrcode_img_name.max' => 'The qr-code image name must not exceed :max characters.',
            'qrcode_data.json' => 'The qr-code data is not in correct format.',
        ];
    }
}
