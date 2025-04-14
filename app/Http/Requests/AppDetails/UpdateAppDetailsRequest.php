<?php

namespace App\Http\Requests\AppDetails;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Requests\RequestWrapper;

class UpdateAppDetailsRequest extends RequestWrapper
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
            'app_id' => 'required|integer|exists:app_details,app_id',
            'about_app' => 'nullable|string',
            'app_features.*'=>'nullable|array',
            'app_screenshots'=>'nullable|array|max:5',
            'app_screenshots.*'=>'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'app_logo'=>'nullable',
            'app_logo'=>'image|mimes:png,jpg,jpeg,gif,svg|max:2048',
        ];
    }
}
